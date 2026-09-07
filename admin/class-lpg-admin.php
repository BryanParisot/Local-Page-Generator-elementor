<?php
/**
 * Administration du plugin Local Page Generator.
 *
 * @package Local_Page_Generator
 */

defined('ABSPATH') || exit;

class LPG_Admin
{
    /**
     * Service chargé de communiquer avec Elementor.
     *
     * @var LPG_Elementor
     */
    private $elementor;

    /**
     * Service chargé de détecter les variables dans un modèle Elementor.
     *
     * @var LPG_Template_Variables
     */
    private $template_variables;

    /**
     * Service chargé de lire les fichiers CSV temporaires.
     *
     * @var LPG_CSV_Importer
     */
    private $csv_importer;

    /**
     * Service chargé de créer les pages à partir des données validées.
     *
     * @var LPG_Page_Generator
     */
    private $page_generator;

    /**
     * Initialise la partie administration.
     *
     * @param LPG_Elementor          $elementor          Service Elementor.
     * @param LPG_Template_Variables $template_variables Analyseur de variables.
     * @param LPG_CSV_Importer|null   $csv_importer       Lecteur de CSV.
     * @param LPG_Page_Generator|null $page_generator     Générateur de pages.
     */
    public function __construct(
        $elementor,
        $template_variables,
        $csv_importer = null,
        $page_generator = null
    ) {
        $this->elementor          = $elementor;
        $this->template_variables = $template_variables;
        $this->csv_importer       = $csv_importer ?: new LPG_CSV_Importer();
        $this->page_generator     = $page_generator ?: new LPG_Page_Generator();
    }

    /**
     * Enregistre les hooks WordPress utilisés dans l'administration.
     *
     * @return void
     */
    public function register_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action(
            'admin_post_lpg_save_template',
            [$this, 'save_template']
        );

        add_action(
            'admin_post_lpg_download_template_csv',
            [$this, 'download_template_csv']
        );

        add_action(
            'admin_post_lpg_import_csv',
            [$this, 'import_csv']
        );

        add_action(
            'admin_post_lpg_generate_pages',
            [$this, 'generate_pages']
        );

    }

    /**
     * Ajoute le menu principal du plugin dans WordPress.
     *
     * @return void
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Local Page Generator', 'local-page-generator'),
            __('Page Generator', 'local-page-generator'),
            'manage_options',
            'local-page-generator',
            [$this, 'render_dashboard'],
            'dashicons-admin-page',
            58
        );
    }

    /**
     * Charge les styles uniquement sur la page du plugin.
     *
     * @param string $hook_suffix Identifiant de la page d'administration.
     *
     * @return void
     */
    public function enqueue_assets($hook_suffix)
    {
        if ('toplevel_page_local-page-generator' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            'lpg-admin',
            LPG_URL . 'assets/css/admin.css',
            [],
            LPG_VERSION
        );
    }

    /**
     * Affiche le dashboard du plugin.
     *
     * @return void
     */
    public function render_dashboard()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous n’avez pas l’autorisation d’accéder à cette page.',
                    'local-page-generator'
                )
            );
        }

        $templates            = $this->get_elementor_templates();
        $selected_template_id = absint(get_option('lpg_template_id', 0));
        $detected_variables   = [];
        $user_id              = get_current_user_id();
        $csv_preview          = get_transient('lpg_csv_preview_' . $user_id);
        $csv_import_errors    = get_transient('lpg_csv_errors_' . $user_id);
        $generation_result    = get_transient('lpg_generation_result_' . $user_id);

        if (!is_array($csv_preview)) {
            $csv_preview = [];
        }

        if (!is_array($csv_import_errors)) {
            $csv_import_errors = [];
        }

        if (!is_array($generation_result)) {
            $generation_result = [];
        }

        if ($selected_template_id) {
            $detected_variables = $this->template_variables->get_variables(
                $selected_template_id
            );
        }

        if (
            !empty($csv_preview)
            && absint($csv_preview['template_id'] ?? 0) !== $selected_template_id
        ) {
            $csv_preview = [];
        }

        if (
            !empty($generation_result)
            && absint($generation_result['template_id'] ?? 0) !== $selected_template_id
        ) {
            $generation_result = [];
        }

        $view_path = LPG_PATH . 'admin/views/dashboard.php';

        if (!file_exists($view_path)) {
            wp_die(
                esc_html__(
                    'Le fichier de l’interface d’administration est introuvable.',
                    'local-page-generator'
                )
            );
        }

        require $view_path;
    }

    /**
     * Enregistre le modèle Elementor choisi dans le dashboard.
     *
     * @return void
     */
    public function save_template()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous n’avez pas l’autorisation d’effectuer cette action.',
                    'local-page-generator'
                )
            );
        }

        check_admin_referer('lpg_save_template', 'lpg_template_nonce');

        $template_id = isset($_POST['template_id'])
            ? absint(wp_unslash($_POST['template_id']))
            : 0;

        if (!$this->is_valid_elementor_template($template_id)) {
            wp_die(
                esc_html__(
                    'Le modèle Elementor sélectionné est invalide.',
                    'local-page-generator'
                )
            );
        }

        update_option('lpg_template_id', $template_id);

        $user_id = get_current_user_id();
        delete_transient('lpg_csv_preview_' . $user_id);
        delete_transient('lpg_csv_errors_' . $user_id);
        delete_transient('lpg_generation_result_' . $user_id);

        $redirect_url = add_query_arg(
            [
                'page'           => 'local-page-generator',
                'template_saved' => 1,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Génère un CSV dont les colonnes correspondent au modèle sélectionné.
     *
     * Les deux premières colonnes sont réservées à WordPress :
     * - page_title : titre de la page ;
     * - slug : URL de la page.
     *
     * Les colonnes suivantes correspondent aux variables LPG détectées.
     *
     * @return void
     */
    public function download_template_csv()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous n’avez pas l’autorisation de télécharger ce fichier.',
                    'local-page-generator'
                )
            );
        }

        $template_id = isset($_GET['template_id'])
            ? absint(wp_unslash($_GET['template_id']))
            : 0;

        if (!$template_id) {
            wp_die(
                esc_html__(
                    'Aucun modèle Elementor n’a été indiqué.',
                    'local-page-generator'
                )
            );
        }

        check_admin_referer(
            'lpg_download_template_csv_' . $template_id
        );

        if (!$this->is_valid_elementor_template($template_id)) {
            wp_die(
                esc_html__(
                    'Le modèle Elementor demandé est invalide.',
                    'local-page-generator'
                )
            );
        }

        $selected_template_id = absint(get_option('lpg_template_id', 0));

        if ($template_id !== $selected_template_id) {
            wp_die(
                esc_html__(
                    'Ce modèle ne correspond pas au modèle actuellement sélectionné.',
                    'local-page-generator'
                )
            );
        }

        $variables = $this->template_variables->get_variables($template_id);

        if (empty($variables)) {
            wp_die(
                esc_html__(
                    'Aucune variable LPG n’a été détectée dans ce modèle.',
                    'local-page-generator'
                )
            );
        }

        $variables = array_values(
            array_unique(
                array_filter(
                    array_map('sanitize_key', $variables)
                )
            )
        );

        $columns = array_merge(
            ['page_title', 'slug'],
            $variables
        );

        $template_title = get_the_title($template_id);

        if (!$template_title) {
            $template_title = 'elementor-' . $template_id;
        }

        $filename = sanitize_file_name(
            'modele-csv-' . remove_accents($template_title) . '.csv'
        );

        /* Évite qu'un contenu déjà mis en tampon corrompe le fichier CSV. */
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        nocache_headers();
        status_header(200);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        $output = fopen('php://output', 'w');

        if (false === $output) {
            wp_die(
                esc_html__(
                    'Impossible de créer le fichier CSV.',
                    'local-page-generator'
                )
            );
        }

        /* BOM UTF-8 pour que Microsoft Excel reconnaisse les accents. */
        fwrite($output, "\xEF\xBB\xBF");

        /* Point-virgule : séparateur généralement attendu par Excel en français. */
        fputcsv(
            $output,
            $columns,
            ';',
            '"',
            ''
        );

        fclose($output);
        exit;
    }

    /**
     * Importe et valide un CSV sans créer de page WordPress.
     *
     * @return void
     */
    public function import_csv()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous n’avez pas l’autorisation d’importer ce fichier.',
                    'local-page-generator'
                )
            );
        }

        check_admin_referer('lpg_import_csv', 'lpg_csv_nonce');

        $errors      = [];
        $template_id = isset($_POST['template_id'])
            ? absint(wp_unslash($_POST['template_id']))
            : 0;
        $template    = $template_id ? get_post($template_id) : null;

        if (!$template instanceof WP_Post) {
            $errors[] = __('Le modèle Elementor sélectionné est introuvable.', 'local-page-generator');
        } elseif ('elementor_library' !== $template->post_type) {
            $errors[] = __('Le contenu sélectionné n’est pas un modèle Elementor.', 'local-page-generator');
        }

        $selected_template_id = absint(get_option('lpg_template_id', 0));

        if ($template_id && $template_id !== $selected_template_id) {
            $errors[] = __(
                'Le modèle envoyé ne correspond pas au modèle actuellement sélectionné.',
                'local-page-generator'
            );
        }

        $detected_variables = [];

        if (empty($errors)) {
            $detected_variables = $this->template_variables->get_variables($template_id);
            $detected_variables = array_values(
                array_unique(
                    array_filter(
                        array_map('sanitize_key', $detected_variables)
                    )
                )
            );

            if (empty($detected_variables)) {
                $errors[] = __(
                    'Aucune variable LPG n’a été détectée dans le modèle sélectionné.',
                    'local-page-generator'
                );
            }
        }

        $upload = isset($_FILES['lpg_csv_file']) && is_array($_FILES['lpg_csv_file'])
            ? $_FILES['lpg_csv_file']
            : [];

        if (empty($upload)) {
            $errors[] = __('Aucun fichier CSV n’a été envoyé.', 'local-page-generator');
        }

        $valid_upload_shape = empty($upload) || (
            isset($upload['name'], $upload['tmp_name'], $upload['size'], $upload['error'])
            && is_string($upload['name'])
            && is_string($upload['tmp_name'])
            && is_numeric($upload['size'])
            && is_numeric($upload['error'])
        );

        if (!$valid_upload_shape) {
            $errors[] = __('Les informations du fichier envoyé sont invalides.', 'local-page-generator');
        }

        $upload_error = isset($upload['error']) && is_numeric($upload['error'])
            ? absint($upload['error'])
            : UPLOAD_ERR_NO_FILE;

        if (!empty($upload) && UPLOAD_ERR_OK !== $upload_error) {
            $errors[] = $this->get_upload_error_message($upload_error);
        }

        $filename = isset($upload['name']) && is_string($upload['name'])
            ? sanitize_file_name(wp_unslash($upload['name']))
            : '';
        $filetype = wp_check_filetype(
            $filename,
            ['csv' => 'text/csv']
        );

        if (!empty($upload) && UPLOAD_ERR_OK === $upload_error && '' === $filename) {
            $errors[] = __('Le nom du fichier CSV est invalide.', 'local-page-generator');
        } elseif ('' !== $filename && 'csv' !== strtolower((string) $filetype['ext'])) {
            $errors[] = __('Le fichier envoyé doit avoir l’extension .csv.', 'local-page-generator');
        }

        $upload_size = isset($upload['size']) && is_numeric($upload['size'])
            ? absint($upload['size'])
            : 0;

        if (LPG_CSV_Importer::MAX_FILE_SIZE < $upload_size) {
            $errors[] = __('Le fichier CSV dépasse la taille maximale de 2 Mo.', 'local-page-generator');
        }

        $temporary_path = isset($upload['tmp_name']) && is_string($upload['tmp_name'])
            ? (string) wp_unslash($upload['tmp_name'])
            : '';

        $import_result = [
            'headers'   => [],
            'rows'      => [],
            'delimiter' => '',
            'errors'    => [],
        ];

        if (empty($errors)) {
            $import_result = $this->csv_importer->import($temporary_path);
            $errors        = array_merge($errors, $import_result['errors']);
        }

        $headers = isset($import_result['headers']) && is_array($import_result['headers'])
            ? $import_result['headers']
            : [];
        $rows = isset($import_result['rows']) && is_array($import_result['rows'])
            ? $import_result['rows']
            : [];

        if (!empty($headers)) {
            $expected_columns = array_values(
                array_unique(
                    array_merge(
                        ['page_title', 'slug'],
                        $detected_variables
                    )
                )
            );

            foreach (array_diff($expected_columns, $headers) as $missing_column) {
                $errors[] = sprintf(
                    __('La colonne « %s » est absente.', 'local-page-generator'),
                    $missing_column
                );
            }

            foreach (array_diff($headers, $expected_columns) as $unknown_column) {
                $errors[] = sprintf(
                    __('La colonne « %s » n’existe pas dans le modèle Elementor.', 'local-page-generator'),
                    $unknown_column
                );
            }
        }

        if (empty($rows)) {
            $errors[] = __('Le fichier CSV ne contient aucune ligne de données.', 'local-page-generator');
        }

        $seen_slugs = [];

        foreach ($rows as $row_index => &$row) {
            $line_number = $row_index + 2;
            $page_title  = isset($row['page_title']) ? trim((string) $row['page_title']) : '';
            $slug        = isset($row['slug']) ? trim((string) $row['slug']) : '';

            if ('' === $page_title) {
                $errors[] = sprintf(
                    __('La ligne %d ne contient pas de page_title.', 'local-page-generator'),
                    $line_number
                );
            }

            $sanitized_slug = sanitize_title($slug);

            if ('' === $sanitized_slug) {
                $errors[] = sprintf(
                    __('La ligne %d ne contient pas de slug valide.', 'local-page-generator'),
                    $line_number
                );
                continue;
            }

            $row['slug'] = $sanitized_slug;

            if (isset($seen_slugs[$sanitized_slug])) {
                $errors[] = sprintf(
                    __('Le slug « %s » est utilisé deux fois.', 'local-page-generator'),
                    $sanitized_slug
                );
            } else {
                $seen_slugs[$sanitized_slug] = true;
            }
        }
        unset($row);

        $errors  = array_values(array_unique(array_filter($errors)));
        $user_id = get_current_user_id();

        delete_transient('lpg_generation_result_' . $user_id);

        if (!empty($errors)) {
            delete_transient('lpg_csv_preview_' . $user_id);
            set_transient(
                'lpg_csv_errors_' . $user_id,
                $errors,
                30 * MINUTE_IN_SECONDS
            );

            $this->redirect_to_dashboard(['csv_error' => 1]);
        }

        $preview = [
            'template_id' => $template_id,
            'filename'    => $filename,
            'headers'     => $headers,
            'rows'        => $rows,
            'row_count'   => count($rows),
            'delimiter'   => (string) $import_result['delimiter'],
            'created_at'  => time(),
        ];

        delete_transient('lpg_csv_errors_' . $user_id);
        set_transient(
            'lpg_csv_preview_' . $user_id,
            $preview,
            30 * MINUTE_IN_SECONDS
        );

        $this->redirect_to_dashboard(['csv_imported' => 1]);
    }

    /**
     * Génère les pages à partir du dernier CSV validé de l'utilisateur.
     *
     * @return void
     */
    public function generate_pages()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous n’avez pas l’autorisation de générer des pages.',
                    'local-page-generator'
                )
            );
        }

        check_admin_referer('lpg_generate_pages', 'lpg_generate_nonce');

        $user_id     = get_current_user_id();
        $csv_preview = get_transient('lpg_csv_preview_' . $user_id);
        $template_id = isset($_POST['template_id']) && is_scalar($_POST['template_id'])
            ? absint(wp_unslash($_POST['template_id']))
            : 0;
        $post_status = isset($_POST['page_status']) && is_string($_POST['page_status'])
            ? sanitize_key(wp_unslash($_POST['page_status']))
            : 'draft';
        $errors      = [];

        if (!is_array($csv_preview) || empty($csv_preview)) {
            $errors[] = __(
                'L’aperçu CSV a expiré. Importez à nouveau le fichier.',
                'local-page-generator'
            );
            $csv_preview = [];
        }

        if (!$this->is_valid_elementor_template($template_id)) {
            $errors[] = __('Le modèle Elementor sélectionné est invalide.', 'local-page-generator');
        }

        $selected_template_id = absint(get_option('lpg_template_id', 0));

        if ($template_id !== $selected_template_id) {
            $errors[] = __(
                'Le modèle ne correspond pas au modèle actuellement sélectionné.',
                'local-page-generator'
            );
        }

        if (
            !empty($csv_preview)
            && absint($csv_preview['template_id'] ?? 0) !== $template_id
        ) {
            $errors[] = __(
                'Le fichier CSV validé ne correspond pas à ce modèle Elementor.',
                'local-page-generator'
            );
        }

        if (!in_array($post_status, ['draft', 'publish', 'pending'], true)) {
            $errors[] = __('Le statut demandé pour les pages est invalide.', 'local-page-generator');
        }

        if ('publish' === $post_status && !current_user_can('publish_pages')) {
            $errors[] = __('Vous n’avez pas l’autorisation de publier des pages.', 'local-page-generator');
        }

        $rows = isset($csv_preview['rows']) && is_array($csv_preview['rows'])
            ? $csv_preview['rows']
            : [];
        $headers = isset($csv_preview['headers']) && is_array($csv_preview['headers'])
            ? $csv_preview['headers']
            : [];

        if (empty($rows) || LPG_CSV_Importer::MAX_ROWS < count($rows)) {
            $errors[] = __('Les lignes du fichier CSV sont absentes ou invalides.', 'local-page-generator');
        }

        $variables = $template_id
            ? $this->template_variables->get_variables($template_id)
            : [];
        $variables = array_values(
            array_unique(
                array_filter(
                    array_map('sanitize_key', $variables)
                )
            )
        );
        $expected_headers = array_values(
            array_unique(
                array_merge(['page_title', 'slug'], $variables)
            )
        );

        if (
            !empty($headers)
            && (
                !empty(array_diff($expected_headers, $headers))
                || !empty(array_diff($headers, $expected_headers))
            )
        ) {
            $errors[] = __(
                'Les variables du modèle ont changé depuis l’importation du CSV.',
                'local-page-generator'
            );
        }

        if (!empty($errors)) {
            $this->store_generation_result(
                [
                    'generated'   => [],
                    'errors'      => array_values(array_unique($errors)),
                    'template_id' => $template_id,
                    'status'      => $post_status,
                    'created_at'  => time(),
                ],
                $user_id
            );

            $this->redirect_to_dashboard(['generation_error' => 1]);
        }

        $result = $this->page_generator->generate(
            $template_id,
            $rows,
            $variables,
            $post_status
        );

        $result['generated'] = isset($result['generated']) && is_array($result['generated'])
            ? $result['generated']
            : [];
        $result['errors'] = isset($result['errors']) && is_array($result['errors'])
            ? array_values(array_unique(array_filter($result['errors'], 'is_string')))
            : [];
        $result['template_id'] = $template_id;
        $result['status']      = $post_status;
        $result['created_at']  = time();

        $this->store_generation_result($result, $user_id);

        if (!empty($result['generated'])) {
            $generated_slugs = array_column($result['generated'], 'slug');
            $remaining_rows  = array_values(
                array_filter(
                    $rows,
                    static function ($row) use ($generated_slugs) {
                        return !is_array($row)
                            || !in_array($row['slug'] ?? '', $generated_slugs, true);
                    }
                )
            );

            if (empty($remaining_rows)) {
                delete_transient('lpg_csv_preview_' . $user_id);
            } else {
                $csv_preview['rows']      = $remaining_rows;
                $csv_preview['row_count'] = count($remaining_rows);
                set_transient(
                    'lpg_csv_preview_' . $user_id,
                    $csv_preview,
                    30 * MINUTE_IN_SECONDS
                );
            }

            $this->clear_elementor_cache();
        }

        $redirect_arguments = [
            'pages_generated' => count($result['generated']),
        ];

        if (!empty($result['errors'])) {
            $redirect_arguments['generation_error'] = 1;
        }

        $this->redirect_to_dashboard($redirect_arguments);
    }

    /**
     * Conserve temporairement le résultat de la génération.
     *
     * @param array $result  Résultat du générateur.
     * @param int   $user_id Identifiant de l'utilisateur.
     *
     * @return void
     */
    private function store_generation_result($result, $user_id)
    {
        set_transient(
            'lpg_generation_result_' . absint($user_id),
            $result,
            30 * MINUTE_IN_SECONDS
        );
    }

    /**
     * Force Elementor à reconstruire ses fichiers après la génération.
     *
     * @return void
     */
    private function clear_elementor_cache()
    {
        if (!class_exists('\\Elementor\\Plugin')) {
            return;
        }

        $elementor = \Elementor\Plugin::$instance;

        if (
            isset($elementor->files_manager)
            && method_exists($elementor->files_manager, 'clear_cache')
        ) {
            $elementor->files_manager->clear_cache();
        }
    }

    /**
     * Traduit un code d'erreur d'upload PHP en message lisible.
     *
     * @param int $error_code Code UPLOAD_ERR_*.
     *
     * @return string
     */
    private function get_upload_error_message($error_code)
    {
        if (UPLOAD_ERR_NO_FILE === $error_code) {
            return __('Aucun fichier CSV n’a été sélectionné.', 'local-page-generator');
        }

        if (in_array($error_code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            return __('Le fichier CSV dépasse la taille maximale autorisée.', 'local-page-generator');
        }

        if (UPLOAD_ERR_PARTIAL === $error_code) {
            return __('Le fichier CSV n’a été envoyé que partiellement.', 'local-page-generator');
        }

        return __('Une erreur est survenue pendant l’envoi du fichier CSV.', 'local-page-generator');
    }

    /**
     * Redirige vers le dashboard après traitement de l'import.
     *
     * @param array $arguments Paramètres ajoutés à l'URL.
     *
     * @return void
     */
    private function redirect_to_dashboard($arguments = [])
    {
        $redirect_url = add_query_arg(
            array_merge(
                ['page' => 'local-page-generator'],
                $arguments
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Récupère et normalise les modèles renvoyés par le service Elementor.
     *
     * La méthode principale attendue est LPG_Elementor::get_templates().
     * get_saved_templates() reste accepté afin de ne pas casser une ancienne
     * version du service créée pendant les épisodes précédents.
     *
     * @return WP_Post[]
     */
    private function get_elementor_templates()
    {
        $templates = [];

        if (method_exists($this->elementor, 'get_templates')) {
            $templates = $this->elementor->get_templates();
        } elseif (method_exists($this->elementor, 'get_saved_templates')) {
            $templates = $this->elementor->get_saved_templates();
        }

        if (!is_array($templates)) {
            return [];
        }

        $normalized_templates = [];

        foreach ($templates as $key => $template) {
            if ($template instanceof WP_Post) {
                $normalized_templates[] = $template;
                continue;
            }

            $template_id = 0;

            if (is_array($template)) {
                if (isset($template['ID'])) {
                    $template_id = absint($template['ID']);
                } elseif (isset($template['id'])) {
                    $template_id = absint($template['id']);
                }
            } elseif (is_object($template) && isset($template->ID)) {
                $template_id = absint($template->ID);
            } elseif (is_numeric($key)) {
                $template_id = absint($key);
            }

            if (!$template_id) {
                continue;
            }

            $template_post = get_post($template_id);

            if ($template_post instanceof WP_Post) {
                $normalized_templates[] = $template_post;
            }
        }

        return $normalized_templates;
    }

    /**
     * Vérifie que l'identifiant correspond à un modèle Elementor existant.
     *
     * @param int $template_id Identifiant du modèle.
     *
     * @return bool
     */
    private function is_valid_elementor_template($template_id)
    {
        if (!$template_id) {
            return false;
        }

        $template = get_post($template_id);

        return $template instanceof WP_Post
            && 'elementor_library' === $template->post_type
            && 'trash' !== $template->post_status;
    }
}
