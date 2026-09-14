<?php
/**
 * Parcours guidé du plugin Local Page Generator.
 *
 * @package Local_Page_Generator
 */

defined('ABSPATH') || exit;

$templates            = isset($templates) && is_array($templates) ? $templates : [];
$selected_template_id = isset($selected_template_id) ? absint($selected_template_id) : 0;
$template_is_valid    = !empty($template_is_valid);
$elementor_is_active  = !empty($elementor_is_active);
$detected_variables   = isset($detected_variables) && is_array($detected_variables)
    ? array_values(array_unique(array_filter(array_map('sanitize_key', $detected_variables))))
    : [];
$csv_preview          = isset($csv_preview) && is_array($csv_preview) ? $csv_preview : [];
$csv_import_errors    = isset($csv_import_errors) && is_array($csv_import_errors)
    ? array_values(array_filter($csv_import_errors, 'is_string'))
    : [];
$generation_result    = isset($generation_result) && is_array($generation_result)
    ? $generation_result
    : [];
$current_step         = isset($current_step) ? max(1, min(4, absint($current_step))) : 1;
$max_accessible_step  = isset($max_accessible_step)
    ? max(1, min(4, absint($max_accessible_step)))
    : 1;
$step_was_blocked     = !empty($step_was_blocked);
$onboarding_complete  = !empty($onboarding_complete);
$mascot_hidden        = !empty($mascot_hidden);
$mascot_images        = isset($mascot_images) && is_array($mascot_images) ? $mascot_images : [];

$generated_pages   = isset($generation_result['generated']) && is_array($generation_result['generated'])
    ? $generation_result['generated']
    : [];
$generation_errors = isset($generation_result['errors']) && is_array($generation_result['errors'])
    ? array_values(array_filter($generation_result['errors'], 'is_string'))
    : [];
$generation_succeeded  = !empty($generated_pages) && empty($generation_errors);
$has_generation_result = !empty($generated_pages) || !empty($generation_errors);
$preview_rows = isset($csv_preview['rows']) && is_array($csv_preview['rows'])
    ? $csv_preview['rows']
    : [];
$has_csv            = !empty($preview_rows);
$can_import_csv     = $template_is_valid && !empty($detected_variables);
$can_generate       = $has_csv && $can_import_csv;
$visible_variables  = array_slice($detected_variables, 0, 2);
$hidden_variables   = array_slice($detected_variables, 2);
$preview_headers    = isset($csv_preview['headers']) && is_array($csv_preview['headers'])
    ? $csv_preview['headers']
    : [];
$preview_row_count  = count($preview_rows);
$preview_filename   = isset($csv_preview['filename'])
    ? sanitize_file_name($csv_preview['filename'])
    : '';

if ('' === $preview_filename && isset($generation_result['filename'])) {
    $preview_filename = sanitize_file_name($generation_result['filename']);
}

if (empty($preview_headers) && isset($generation_result['headers']) && is_array($generation_result['headers'])) {
    $preview_headers = array_values(array_filter($generation_result['headers'], 'is_string'));
}

if (0 === $preview_row_count && isset($generation_result['row_count'])) {
    $preview_row_count = absint($generation_result['row_count']);
}

if (empty($preview_headers) && $can_import_csv) {
    $preview_headers = array_merge(['page_title', 'slug'], $detected_variables);
}

$delimiter = isset($csv_preview['delimiter']) ? (string) $csv_preview['delimiter'] : '';
$delimiter_label = ';' === $delimiter
    ? __('point-virgule', 'local-page-generator')
    : (',' === $delimiter ? __('virgule', 'local-page-generator') : __('non disponible', 'local-page-generator'));
$selected_template_title = $template_is_valid ? get_the_title($selected_template_id) : '';
$selected_status = isset($generation_result['status'])
    ? sanitize_key($generation_result['status'])
    : 'draft';

if (!in_array($selected_status, ['draft', 'publish', 'pending'], true)) {
    $selected_status = 'draft';
}

$status_labels = [
    'draft'   => __('Brouillon', 'local-page-generator'),
    'publish' => __('Publié', 'local-page-generator'),
    'pending' => __('En attente de relecture', 'local-page-generator'),
];

$dashboard_url = add_query_arg(['page' => 'local-page-generator'], admin_url('admin.php'));
$step_urls     = [];

for ($step_number = 1; $step_number <= 4; ++$step_number) {
    $step_urls[$step_number] = add_query_arg('lpg_step', $step_number, $dashboard_url);
}

$download_url = '';

if ($can_import_csv) {
    $download_url = wp_nonce_url(
        add_query_arg(
            [
                'action'      => 'lpg_download_template_csv',
                'template_id' => $selected_template_id,
            ],
            admin_url('admin-post.php')
        ),
        'lpg_download_template_csv_' . $selected_template_id
    );
}

$steps = [
    1 => [
        'label'     => __('Modèle', 'local-page-generator'),
        'completed' => $template_is_valid,
    ],
    2 => [
        'label'     => __('Variables', 'local-page-generator'),
        'completed' => $can_import_csv,
    ],
    3 => [
        'label'     => __('CSV', 'local-page-generator'),
        'completed' => $has_csv || $has_generation_result || $onboarding_complete,
    ],
    4 => [
        'label'     => __('Génération', 'local-page-generator'),
        'completed' => $generation_succeeded || $onboarding_complete,
    ],
];

$assistant_messages = [
    1 => __('Commençons par choisir le modèle qui servira de base à toutes tes futures pages.', 'local-page-generator'),
    2 => __('J’analyse ton modèle pour retrouver les contenus qui changeront sur chaque page.', 'local-page-generator'),
    3 => __('Envoie-moi ton fichier. Je vais vérifier que toutes les colonnes correspondent au modèle.', 'local-page-generator'),
    4 => __('Tout est prêt. Vérifie les informations avant de lancer la création des pages.', 'local-page-generator'),
];
$assistant_tips = [
    1 => __('Choisis un modèle Elementor publié et prêt à être dupliqué.', 'local-page-generator'),
    2 => __('Les colonnes page_title et slug seront ajoutées automatiquement au modèle CSV.', 'local-page-generator'),
    3 => __('Utilise le fichier modèle téléchargé pour conserver les bons noms de colonnes.', 'local-page-generator'),
    4 => __('Commence par des brouillons si tu souhaites relire les pages avant publication.', 'local-page-generator'),
];
$mascot_key = 4 === $current_step && $generation_succeeded ? 'success' : $current_step;
$mascot_file = isset($mascot_images[$mascot_key]) ? sanitize_file_name($mascot_images[$mascot_key]) : '';
$mascot_dimensions = in_array($mascot_key, [4, 'success'], true)
    ? ['width' => 992, 'height' => 1072]
    : ['width' => 1131, 'height' => 928];
$mascot_path = '' !== $mascot_file ? LPG_PATH . 'assets/images/' . $mascot_file : '';
$mascot_url  = '' !== $mascot_path && file_exists($mascot_path)
    ? LPG_URL . 'assets/images/' . $mascot_file
    : '';
?>

<div class="wrap lpg-wrap">
    <header class="lpg-header">
        <div>
            <span class="lpg-eyebrow"><?php esc_html_e('Local Page Generator', 'local-page-generator'); ?></span>
            <h1><?php esc_html_e('Créez vos pages locales pas à pas', 'local-page-generator'); ?></h1>
            <p><?php esc_html_e('Suivez les quatre étapes pour préparer, vérifier puis générer vos pages Elementor.', 'local-page-generator'); ?></p>
        </div>

        <div class="lpg-header-tools">
            <span class="lpg-version"><?php echo esc_html('Version ' . LPG_VERSION); ?></span>

            <?php if ($mascot_hidden) : ?>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="lpg_toggle_mascot">
                    <input type="hidden" name="return_step" value="<?php echo esc_attr($current_step); ?>">
                    <input type="hidden" name="mascot_hidden" value="0">
                    <?php wp_nonce_field('lpg_toggle_mascot', 'lpg_mascot_nonce'); ?>
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Afficher les conseils', 'local-page-generator'); ?></button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($step_was_blocked) : ?>
        <div class="lpg-alert lpg-alert-warning" role="alert">
            <strong><?php esc_html_e('Cette étape n’est pas encore disponible.', 'local-page-generator'); ?></strong>
            <p><?php esc_html_e('Terminez l’étape affichée pour poursuivre le parcours.', 'local-page-generator'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['workflow_reset'])) : ?>
        <div class="lpg-alert lpg-alert-success" role="status">
            <strong><?php esc_html_e('Nouvelle génération prête à démarrer.', 'local-page-generator'); ?></strong>
            <p><?php esc_html_e('Les données temporaires ont été supprimées. Le modèle et les pages existantes sont conservés.', 'local-page-generator'); ?></p>
        </div>
    <?php elseif (isset($_GET['onboarding_finished'])) : ?>
        <div class="lpg-alert lpg-alert-success lpg-validation-pop" role="status">
            <strong><?php esc_html_e('Tutoriel terminé !', 'local-page-generator'); ?></strong>
            <p><?php esc_html_e('Vous pouvez revoir les étapes ou lancer une nouvelle génération à tout moment.', 'local-page-generator'); ?></p>
        </div>
    <?php endif; ?>

    <nav class="lpg-progress" aria-label="<?php esc_attr_e('Étapes de génération', 'local-page-generator'); ?>">
        <ol>
            <?php foreach ($steps as $step_number => $step_data) : ?>
                <?php
                $is_active    = $current_step === $step_number;
                $is_completed = !empty($step_data['completed']);
                $is_available = $step_number <= $max_accessible_step;
                $state_class  = $is_active ? 'is-active' : ($is_completed ? 'is-completed' : 'is-inactive');
                ?>
                <li class="<?php echo esc_attr($state_class); ?>">
                    <?php if ($is_available && !$is_active) : ?>
                        <a href="<?php echo esc_url($step_urls[$step_number]); ?>">
                    <?php else : ?>
                        <span <?php echo $is_active ? 'aria-current="step"' : ''; ?> <?php echo !$is_available ? 'aria-disabled="true"' : ''; ?>>
                    <?php endif; ?>

                        <span class="lpg-progress-number" aria-hidden="true">
                            <?php echo $is_completed && !$is_active ? '&#10003;' : esc_html($step_number); ?>
                        </span>
                        <span class="lpg-progress-label"><?php echo esc_html($step_data['label']); ?></span>

                    <?php if ($is_available && !$is_active) : ?>
                        </a>
                    <?php else : ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="<?php echo esc_attr('lpg-stage' . (!$mascot_hidden && '' !== $mascot_url ? ' lpg-stage-with-assistant' : '')); ?>">
        <main class="lpg-stage-card lpg-stage-<?php echo esc_attr($current_step); ?>">
            <div class="lpg-stage-heading">
                <span class="lpg-stage-kicker"><?php echo esc_html(sprintf(__('Étape %d sur 4', 'local-page-generator'), $current_step)); ?></span>

                <?php if (1 === $current_step) : ?>
                    <h2><?php esc_html_e('Choisissez votre modèle Elementor', 'local-page-generator'); ?></h2>
                    <p><?php esc_html_e('Ce modèle sera utilisé comme base pour toutes les pages générées.', 'local-page-generator'); ?></p>
                <?php elseif (2 === $current_step) : ?>
                    <h2><?php esc_html_e('Préparez les variables du modèle', 'local-page-generator'); ?></h2>
                    <p><?php esc_html_e('Les variables détectées deviendront les colonnes de votre fichier CSV.', 'local-page-generator'); ?></p>
                <?php elseif (3 === $current_step) : ?>
                    <h2><?php esc_html_e('Importez votre fichier CSV', 'local-page-generator'); ?></h2>
                    <p><?php esc_html_e('Le fichier est vérifié avant toute création de page.', 'local-page-generator'); ?></p>
                <?php else : ?>
                    <h2><?php esc_html_e('Vérifiez les pages avant la génération', 'local-page-generator'); ?></h2>
                    <p><?php esc_html_e('Contrôlez le modèle, les données et le statut WordPress avant de lancer la création.', 'local-page-generator'); ?></p>
                <?php endif; ?>
            </div>

            <?php if (1 === $current_step) : ?>
                <div class="lpg-connection-status">
                    <span class="lpg-status-dot <?php echo esc_attr($elementor_is_active ? 'is-connected' : 'is-disconnected'); ?>"></span>
                    <strong><?php echo esc_html($elementor_is_active ? __('Elementor est connecté', 'local-page-generator') : __('Elementor n’est pas actif', 'local-page-generator')); ?></strong>
                </div>

                <?php if (!$elementor_is_active) : ?>
                    <div class="lpg-alert lpg-alert-error" role="alert">
                        <strong><?php esc_html_e('Elementor n’est pas actif.', 'local-page-generator'); ?></strong>
                        <p><?php esc_html_e('Activez Elementor puis rechargez cette page pour récupérer vos modèles.', 'local-page-generator'); ?></p>
                    </div>
                <?php elseif (empty($templates)) : ?>
                    <div class="lpg-alert lpg-alert-warning" role="alert">
                        <strong><?php esc_html_e('Aucun modèle Elementor n’est disponible.', 'local-page-generator'); ?></strong>
                        <p><?php esc_html_e('Créez et publiez un modèle dans Elementor, puis revenez sur cette étape.', 'local-page-generator'); ?></p>
                    </div>
                <?php else : ?>
                    <?php if (isset($_GET['template_error'])) : ?>
                        <div class="lpg-alert lpg-alert-error" role="alert">
                            <strong><?php esc_html_e('Le modèle sélectionné est invalide.', 'local-page-generator'); ?></strong>
                            <p><?php esc_html_e('Choisissez un modèle Elementor existant dans la liste.', 'local-page-generator'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form class="lpg-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="lpg_save_template">
                        <?php wp_nonce_field('lpg_save_template', 'lpg_template_nonce'); ?>
                        <label for="lpg-template"><?php esc_html_e('Modèle Elementor', 'local-page-generator'); ?></label>
                        <select id="lpg-template" name="template_id" required>
                            <option value=""><?php esc_html_e('Sélectionnez un modèle', 'local-page-generator'); ?></option>
                            <?php foreach ($templates as $template) : ?>
                                <?php if (!$template instanceof WP_Post) { continue; } ?>
                                <option value="<?php echo esc_attr($template->ID); ?>" <?php selected($selected_template_id, $template->ID); ?>>
                                    <?php echo esc_html(get_the_title($template->ID)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($template_is_valid) : ?>
                            <p class="lpg-selection-summary">
                                <?php esc_html_e('Modèle actuellement enregistré :', 'local-page-generator'); ?>
                                <strong><?php echo esc_html($selected_template_title); ?></strong>
                            </p>
                        <?php endif; ?>

                        <div class="lpg-primary-action">
                            <button type="submit" class="button button-primary button-hero"><?php esc_html_e('Enregistrer et continuer', 'local-page-generator'); ?></button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php elseif (2 === $current_step) : ?>
                <?php if (isset($_GET['template_saved'])) : ?>
                    <div class="lpg-alert lpg-alert-success lpg-validation-pop" role="status">
                        <strong><?php esc_html_e('Le modèle Elementor a bien été enregistré.', 'local-page-generator'); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="lpg-summary-card">
                    <span><?php esc_html_e('Modèle analysé', 'local-page-generator'); ?></span>
                    <strong><?php echo esc_html($selected_template_title); ?></strong>
                </div>

                <?php if (!empty($detected_variables)) : ?>
                    <div class="lpg-variable-heading">
                        <div>
                            <h3><?php esc_html_e('Variables LPG détectées', 'local-page-generator'); ?></h3>
                            <p><?php esc_html_e('Elles seront ajoutées après page_title et slug.', 'local-page-generator'); ?></p>
                        </div>
                        <span class="lpg-count-badge"><?php echo esc_html(sprintf(_n('%d variable', '%d variables', count($detected_variables), 'local-page-generator'), count($detected_variables))); ?></span>
                    </div>

                    <div class="lpg-variable-list">
                        <?php foreach ($visible_variables as $variable) : ?>
                            <code class="lpg-variable-chip"><?php echo esc_html($variable); ?></code>
                        <?php endforeach; ?>
                        <?php if (!empty($hidden_variables)) : ?>
                            <span class="lpg-variable-more" title="<?php echo esc_attr(implode(', ', $hidden_variables)); ?>">
                                <span aria-hidden="true">…</span>
                                <span class="screen-reader-text"><?php echo esc_html(sprintf(__('%d variables supplémentaires', 'local-page-generator'), count($hidden_variables))); ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="lpg-secondary-actions">
                        <a class="button button-secondary" href="<?php echo esc_url($step_urls[2]); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('Analyser à nouveau', 'local-page-generator'); ?></a>
                        <a class="button button-secondary" href="<?php echo esc_url($download_url); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e('Générer le modèle CSV', 'local-page-generator'); ?></a>
                    </div>
                <?php else : ?>
                    <div class="lpg-alert lpg-alert-error" role="alert">
                        <strong><?php esc_html_e('Aucune variable LPG n’a été détectée.', 'local-page-generator'); ?></strong>
                        <p><?php esc_html_e('Ajoutez une balise dynamique « Variable LPG » dans le modèle Elementor, enregistrez-le, puis relancez l’analyse.', 'local-page-generator'); ?></p>
                    </div>
                    <a class="button button-secondary" href="<?php echo esc_url($step_urls[2]); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('Relancer l’analyse', 'local-page-generator'); ?></a>
                <?php endif; ?>

                <div class="lpg-navigation-actions">
                    <a class="button button-secondary" href="<?php echo esc_url($step_urls[1]); ?>"><?php esc_html_e('Précédent', 'local-page-generator'); ?></a>
                    <?php if ($can_import_csv) : ?>
                        <a class="button button-primary button-hero" href="<?php echo esc_url($step_urls[3]); ?>"><?php esc_html_e('J’ai préparé mon CSV', 'local-page-generator'); ?></a>
                    <?php else : ?>
                        <button type="button" class="button button-primary button-hero" disabled><?php esc_html_e('J’ai préparé mon CSV', 'local-page-generator'); ?></button>
                    <?php endif; ?>
                </div>

            <?php elseif (3 === $current_step) : ?>
                <?php if (!empty($csv_import_errors)) : ?>
                    <div class="lpg-alert lpg-alert-error" role="alert">
                        <strong><?php esc_html_e('Le fichier CSV ne correspond pas au modèle.', 'local-page-generator'); ?></strong>
                        <ul><?php foreach ($csv_import_errors as $import_error) : ?><li><?php echo esc_html($import_error); ?></li><?php endforeach; ?></ul>
                        <p><?php esc_html_e('Corrigez le fichier puis importez-le à nouveau ci-dessous.', 'local-page-generator'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($has_csv) : ?>
                    <div class="lpg-alert lpg-alert-success lpg-validation-pop" role="status"><strong><?php esc_html_e('Fichier valide', 'local-page-generator'); ?></strong></div>
                    <div class="lpg-import-summary">
                        <span><strong><?php echo esc_html($preview_filename); ?></strong></span>
                        <span><?php echo esc_html(sprintf(_n('%d page détectée', '%d pages détectées', $preview_row_count, 'local-page-generator'), $preview_row_count)); ?></span>
                        <span><?php echo esc_html(sprintf(_n('%d colonne reconnue', '%d colonnes reconnues', count($preview_headers), 'local-page-generator'), count($preview_headers))); ?></span>
                        <span><?php echo esc_html(sprintf(__('Séparateur : %s', 'local-page-generator'), $delimiter_label)); ?></span>
                    </div>
                <?php endif; ?>

                <form class="lpg-upload-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="lpg_import_csv">
                    <input type="hidden" name="template_id" value="<?php echo esc_attr($selected_template_id); ?>">
                    <?php wp_nonce_field('lpg_import_csv', 'lpg_csv_nonce'); ?>
                    <div class="lpg-upload-zone">
                        <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                        <label for="lpg-csv-file"><strong><?php echo esc_html($has_csv ? __('Choisir un autre CSV', 'local-page-generator') : __('Choisir un fichier CSV', 'local-page-generator')); ?></strong></label>
                        <small><?php esc_html_e('Extension autorisée : .csv · Taille maximale : 2 Mo', 'local-page-generator'); ?></small>
                        <input id="lpg-csv-file" class="lpg-file-input" type="file" name="lpg_csv_file" accept=".csv,text/csv" required>
                        <button type="submit" class="button <?php echo esc_attr($has_csv ? 'button-secondary' : 'button-primary button-hero'); ?>"><?php esc_html_e('Importer et vérifier le CSV', 'local-page-generator'); ?></button>
                    </div>
                </form>

                <div class="lpg-navigation-actions">
                    <a class="button button-secondary" href="<?php echo esc_url($step_urls[2]); ?>"><?php esc_html_e('Précédent', 'local-page-generator'); ?></a>
                    <?php if ($has_csv) : ?><a class="button button-primary" href="<?php echo esc_url($step_urls[4]); ?>"><?php esc_html_e('Vérifier les pages', 'local-page-generator'); ?></a><?php endif; ?>
                </div>

            <?php else : ?>
                <?php if (isset($_GET['finish_error'])) : ?>
                    <div class="lpg-alert lpg-alert-error" role="alert"><strong><?php esc_html_e('Le tutoriel ne peut pas encore être terminé.', 'local-page-generator'); ?></strong><p><?php esc_html_e('Générez les pages sans erreur avant de terminer le parcours.', 'local-page-generator'); ?></p></div>
                <?php endif; ?>

                <?php if (!empty($generation_errors)) : ?>
                    <div class="lpg-alert lpg-alert-error" role="alert">
                        <strong><?php esc_html_e('Certaines pages n’ont pas pu être générées.', 'local-page-generator'); ?></strong>
                        <ul><?php foreach ($generation_errors as $generation_error) : ?><li><?php echo esc_html($generation_error); ?></li><?php endforeach; ?></ul>
                        <p><?php esc_html_e('Corrigez le problème ou revenez à l’importation pour valider un nouveau CSV.', 'local-page-generator'); ?></p>
                    </div>
                <?php endif; ?>

                <dl class="lpg-review-grid">
                    <div><dt><?php esc_html_e('Modèle Elementor', 'local-page-generator'); ?></dt><dd><?php echo esc_html($selected_template_title); ?></dd></div>
                    <div><dt><?php esc_html_e('Fichier CSV', 'local-page-generator'); ?></dt><dd><?php echo esc_html($preview_filename ?: __('Non disponible', 'local-page-generator')); ?></dd></div>
                    <div><dt><?php esc_html_e('Pages détectées', 'local-page-generator'); ?></dt><dd><?php echo esc_html($preview_row_count); ?></dd></div>
                    <div><dt><?php esc_html_e('Colonnes reconnues', 'local-page-generator'); ?></dt><dd><?php echo esc_html(count($preview_headers)); ?></dd></div>
                    <div><dt><?php esc_html_e('Statut WordPress', 'local-page-generator'); ?></dt><dd><?php echo esc_html($status_labels[$selected_status]); ?></dd></div>
                </dl>

                <?php if ($has_csv) : ?>
                    <div class="lpg-preview-table-wrapper" tabindex="0" aria-label="<?php esc_attr_e('Aperçu défilable des cinq premières lignes', 'local-page-generator'); ?>">
                        <table class="widefat striped lpg-preview-table">
                            <thead><tr><?php foreach ($preview_headers as $preview_header) : ?><th scope="col"><?php echo esc_html($preview_header); ?></th><?php endforeach; ?></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($preview_rows, 0, 5) as $preview_row) : ?>
                                    <tr>
                                        <?php foreach ($preview_headers as $preview_header) : ?>
                                            <?php $preview_value = is_array($preview_row) && isset($preview_row[$preview_header]) ? (string) $preview_row[$preview_header] : ''; ?>
                                            <td><?php echo '' === $preview_value ? '<span aria-hidden="true">—</span>' : esc_html(wp_html_excerpt($preview_value, 120, '…')); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (5 < count($preview_rows)) : ?><p class="lpg-table-note"><?php esc_html_e('Seules les cinq premières lignes sont affichées.', 'local-page-generator'); ?></p><?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($generated_pages)) : ?>
                    <div class="lpg-alert lpg-alert-success lpg-validation-pop" role="status">
                        <strong><?php echo esc_html(sprintf(_n('%d page a été générée.', '%d pages ont été générées.', count($generated_pages), 'local-page-generator'), count($generated_pages))); ?></strong>
                        <ul class="lpg-generated-pages">
                            <?php foreach (array_slice($generated_pages, 0, 10) as $generated_page) : ?>
                                <?php
                                $generated_post_id = absint($generated_page['post_id'] ?? 0);
                                $edit_url = $generated_post_id ? get_edit_post_link($generated_post_id, '') : '';
                                $view_url = $generated_post_id ? get_permalink($generated_post_id) : '';
                                ?>
                                <li><span><?php echo esc_html($generated_page['title'] ?? ''); ?></span><?php if ($edit_url) : ?><a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Modifier', 'local-page-generator'); ?></a><?php endif; ?><?php if ($view_url) : ?><a href="<?php echo esc_url($view_url); ?>"><?php esc_html_e('Voir', 'local-page-generator'); ?></a><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>"><?php esc_html_e('Voir toutes les pages', 'local-page-generator'); ?></a></p>
                    </div>
                <?php endif; ?>

                <?php if ($can_generate && !$generation_succeeded) : ?>
                    <form class="lpg-generation-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="lpg_generate_pages">
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($selected_template_id); ?>">
                        <?php wp_nonce_field('lpg_generate_pages', 'lpg_generate_nonce'); ?>
                        <div class="lpg-generation-row">
                            <div class="lpg-field">
                                <label for="lpg-page-status"><?php esc_html_e('Statut WordPress des futures pages', 'local-page-generator'); ?></label>
                                <select id="lpg-page-status" name="page_status" required>
                                    <option value="draft" <?php selected($selected_status, 'draft'); ?>><?php esc_html_e('Brouillon', 'local-page-generator'); ?></option>
                                    <option value="publish" <?php selected($selected_status, 'publish'); ?>><?php esc_html_e('Publié', 'local-page-generator'); ?></option>
                                    <option value="pending" <?php selected($selected_status, 'pending'); ?>><?php esc_html_e('En attente de relecture', 'local-page-generator'); ?></option>
                                </select>
                            </div>
                            <button type="submit" class="button button-primary button-hero"><?php echo esc_html(sprintf(_n('Générer %d page', 'Générer %d pages', $preview_row_count, 'local-page-generator'), $preview_row_count)); ?></button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="lpg-navigation-actions">
                    <a class="button button-secondary" href="<?php echo esc_url($step_urls[3]); ?>"><?php esc_html_e('Précédent', 'local-page-generator'); ?></a>
                    <?php if ($generation_succeeded && !$onboarding_complete) : ?>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                            <input type="hidden" name="action" value="lpg_finish_onboarding">
                            <?php wp_nonce_field('lpg_finish_onboarding', 'lpg_finish_nonce'); ?>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Terminer le tutoriel', 'local-page-generator'); ?></button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($has_generation_result || $onboarding_complete) : ?>
                    <section class="lpg-aftercare" aria-labelledby="lpg-aftercare-title">
                        <h3 id="lpg-aftercare-title"><?php esc_html_e('Et maintenant ?', 'local-page-generator'); ?></h3>
                        <div class="lpg-aftercare-actions">
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="lpg_review_onboarding">
                                <?php wp_nonce_field('lpg_review_onboarding', 'lpg_review_nonce'); ?>
                                <button type="submit" class="button button-secondary"><?php esc_html_e('Revoir le tutoriel', 'local-page-generator'); ?></button>
                            </form>
                            <form class="lpg-reset-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-confirm="<?php esc_attr_e('Commencer une nouvelle génération ? Les données CSV temporaires seront supprimées, mais le modèle et les pages existantes seront conservés.', 'local-page-generator'); ?>">
                                <input type="hidden" name="action" value="lpg_reset_workflow">
                                <?php wp_nonce_field('lpg_reset_workflow', 'lpg_reset_nonce'); ?>
                                <button type="submit" class="button lpg-button-warning"><?php esc_html_e('Commencer une nouvelle génération', 'local-page-generator'); ?></button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <?php if (!$mascot_hidden && '' !== $mascot_url) : ?>
            <aside class="lpg-assistant" aria-label="<?php esc_attr_e('Conseils de l’assistant', 'local-page-generator'); ?>">
                <div class="lpg-assistant-bubble">
                    <p><?php echo esc_html($assistant_messages[$current_step]); ?></p>
                    <small><?php echo esc_html($assistant_tips[$current_step]); ?></small>
                    <form class="lpg-assistant-toggle" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="lpg_toggle_mascot">
                        <input type="hidden" name="return_step" value="<?php echo esc_attr($current_step); ?>">
                        <input type="hidden" name="mascot_hidden" value="1">
                        <?php wp_nonce_field('lpg_toggle_mascot', 'lpg_mascot_nonce'); ?>
                        <button type="submit" class="button-link"><?php esc_html_e('Masquer les conseils', 'local-page-generator'); ?></button>
                    </form>
                </div>
                <img src="<?php echo esc_url($mascot_url); ?>" alt="" width="<?php echo esc_attr($mascot_dimensions['width']); ?>" height="<?php echo esc_attr($mascot_dimensions['height']); ?>" loading="lazy" decoding="async">
            </aside>
        <?php endif; ?>
    </div>
</div>
