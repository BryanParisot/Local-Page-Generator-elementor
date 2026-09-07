<?php
/**
 * Dashboard principal du plugin Local Page Generator.
 *
 * Variables transmises par LPG_Admin::render_dashboard() :
 * - $templates               Liste de WP_Post Elementor.
 * - $selected_template_id    Identifiant du modèle sélectionné.
 * - $detected_variables      Variables LPG trouvées dans le modèle.
 * - $csv_preview             Résumé du dernier CSV valide de l'utilisateur.
 * - $csv_import_errors       Erreurs du dernier import de l'utilisateur.
 * - $generation_result       Résultat de la dernière génération.
 *
 * @package Local_Page_Generator
 */

defined('ABSPATH') || exit;

$templates            = isset($templates) && is_array($templates) ? $templates : [];
$selected_template_id = isset($selected_template_id) ? absint($selected_template_id) : 0;
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
$can_import_csv       = 0 < $selected_template_id && !empty($detected_variables);

$visible_variables = array_slice($detected_variables, 0, 2);
$hidden_variables  = array_slice($detected_variables, 2);
$hidden_count      = count($hidden_variables);
$preview_rows      = isset($csv_preview['rows']) && is_array($csv_preview['rows'])
    ? array_slice($csv_preview['rows'], 0, 10)
    : [];
$preview_row_count = isset($csv_preview['rows']) && is_array($csv_preview['rows'])
    ? count($csv_preview['rows'])
    : 0;
$preview_headers   = array_merge(['page_title', 'slug'], $detected_variables);
$can_generate      = 0 < $preview_row_count && 0 < $selected_template_id;
$generated_pages   = isset($generation_result['generated']) && is_array($generation_result['generated'])
    ? $generation_result['generated']
    : [];
$generation_errors = isset($generation_result['errors']) && is_array($generation_result['errors'])
    ? array_values(array_filter($generation_result['errors'], 'is_string'))
    : [];
$current_step = 1;

if ($can_import_csv) {
    $current_step = 2;
}

if ($can_generate) {
    $current_step = 3;
}

if (!empty($generated_pages) || !empty($generation_errors)) {
    $current_step = 4;
}

$dashboard_url = add_query_arg(
    ['page' => 'local-page-generator'],
    admin_url('admin.php')
);

$download_url = '';
$delimiter_label = '';

if (!empty($csv_preview)) {
    $delimiter_label = ';' === ($csv_preview['delimiter'] ?? '')
        ? __('point-virgule', 'local-page-generator')
        : __('virgule', 'local-page-generator');
}

if ($selected_template_id && !empty($detected_variables)) {
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
?>

<div class="wrap lpg-wrap">

    <header class="lpg-header">
        <div>
            <span class="lpg-eyebrow">
                <?php esc_html_e('Local Page Generator', 'local-page-generator'); ?>
            </span>

            <h1>
                <?php esc_html_e(
                    'Générez vos pages Elementor à partir d’un fichier CSV',
                    'local-page-generator'
                ); ?>
            </h1>

            <p>
                <?php esc_html_e(
                    'Sélectionnez un modèle, importez vos données, vérifiez le résultat puis générez vos pages.',
                    'local-page-generator'
                ); ?>
            </p>
        </div>

        <div class="lpg-version">
            <?php echo esc_html('Version ' . LPG_VERSION); ?>
        </div>
    </header>

    <?php if (isset($_GET['template_saved'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php esc_html_e(
                    'Le modèle Elementor a bien été enregistré.',
                    'local-page-generator'
                ); ?>
            </p>
        </div>
    <?php endif; ?>

    <nav
        class="lpg-steps"
        aria-label="<?php esc_attr_e('Étapes de génération', 'local-page-generator'); ?>"
    >
        <div class="<?php echo esc_attr('lpg-step' . (1 === $current_step ? ' lpg-step-active' : '')); ?>">
            <span>1</span>
            <div>
                <strong><?php esc_html_e('Modèle', 'local-page-generator'); ?></strong>
                <small><?php esc_html_e('Elementor', 'local-page-generator'); ?></small>
            </div>
        </div>

        <div class="<?php echo esc_attr('lpg-step' . (2 === $current_step ? ' lpg-step-active' : '')); ?>">
            <span>2</span>
            <div>
                <strong><?php esc_html_e('Importation', 'local-page-generator'); ?></strong>
                <small><?php esc_html_e('Fichier CSV', 'local-page-generator'); ?></small>
            </div>
        </div>

        <div class="<?php echo esc_attr('lpg-step' . (3 === $current_step ? ' lpg-step-active' : '')); ?>">
            <span>3</span>
            <div>
                <strong><?php esc_html_e('Vérification', 'local-page-generator'); ?></strong>
                <small><?php esc_html_e('Aperçu', 'local-page-generator'); ?></small>
            </div>
        </div>

        <div class="<?php echo esc_attr('lpg-step' . (4 === $current_step ? ' lpg-step-active' : '')); ?>">
            <span>4</span>
            <div>
                <strong><?php esc_html_e('Génération', 'local-page-generator'); ?></strong>
                <small><?php esc_html_e('Création des pages', 'local-page-generator'); ?></small>
            </div>
        </div>
    </nav>

    <div class="lpg-grid">

        <!-- ÉTAPE 1 : CHOIX DU MODÈLE ET VARIABLES -->
        <section class="lpg-card lpg-card-full">
            <div class="lpg-card-number">1</div>

            <div class="lpg-card-content">
                <div class="lpg-card-header">
                    <div>
                        <h2>
                            <?php esc_html_e(
                                'Choisir un modèle Elementor',
                                'local-page-generator'
                            ); ?>
                        </h2>

                        <p>
                            <?php esc_html_e(
                                'Ce modèle sera utilisé comme base pour toutes les pages générées.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <span class="lpg-badge lpg-badge-success">
                        <?php esc_html_e('Elementor connecté', 'local-page-generator'); ?>
                    </span>
                </div>

                <?php if (empty($templates)) : ?>
                    <div class="lpg-inline-warning">
                        <strong>
                            <?php esc_html_e(
                                'Aucun modèle Elementor enregistré.',
                                'local-page-generator'
                            ); ?>
                        </strong>

                        <p>
                            <?php esc_html_e(
                                'Créez d’abord un modèle dans Elementor, puis revenez sur cette page.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>
                <?php else : ?>
                    <form
                        class="lpg-template-form"
                        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                        method="post"
                    >
                        <input type="hidden" name="action" value="lpg_save_template">

                        <?php wp_nonce_field('lpg_save_template', 'lpg_template_nonce'); ?>

                        <label for="lpg-template">
                            <?php esc_html_e('Modèle Elementor', 'local-page-generator'); ?>
                        </label>

                        <div class="lpg-template-row">
                            <select id="lpg-template" name="template_id" required>
                                <option value="">
                                    <?php esc_html_e(
                                        'Sélectionnez un modèle',
                                        'local-page-generator'
                                    ); ?>
                                </option>

                                <?php foreach ($templates as $template) : ?>
                                    <?php
                                    if (!is_object($template) || !isset($template->ID)) {
                                        continue;
                                    }
                                    ?>

                                    <option
                                        value="<?php echo esc_attr($template->ID); ?>"
                                        <?php selected($selected_template_id, $template->ID); ?>
                                    >
                                        <?php echo esc_html(get_the_title($template->ID)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="button button-primary">
                                <?php esc_html_e(
                                    'Enregistrer le modèle',
                                    'local-page-generator'
                                ); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($selected_template_id) : ?>
                    <div class="lpg-detected-variables">
                        <div class="lpg-detected-header">
                            <div>
                                <strong>
                                    <?php esc_html_e(
                                        'Variables détectées dans le modèle',
                                        'local-page-generator'
                                    ); ?>
                                </strong>

                                <p>
                                    <?php esc_html_e(
                                        'Les noms détectés deviendront les colonnes du fichier CSV.',
                                        'local-page-generator'
                                    ); ?>
                                </p>
                            </div>

                            <?php if (!empty($detected_variables)) : ?>
                                <span class="lpg-variable-count">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            _n(
                                                '%d variable',
                                                '%d variables',
                                                count($detected_variables),
                                                'local-page-generator'
                                            ),
                                            count($detected_variables)
                                        )
                                    );
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($detected_variables)) : ?>
                            <div class="lpg-variable-list">
                                <?php foreach ($visible_variables as $variable) : ?>
                                    <code class="lpg-variable-chip">
                                        <?php echo esc_html($variable); ?>
                                    </code>
                                <?php endforeach; ?>

                                <?php if ($hidden_count > 0) : ?>
                                    <span
                                        class="lpg-variable-more"
                                        title="<?php echo esc_attr(implode(', ', $hidden_variables)); ?>"
                                        aria-label="<?php
                                        echo esc_attr(
                                            sprintf(
                                                _n(
                                                    '%d variable supplémentaire : %s',
                                                    '%d variables supplémentaires : %s',
                                                    $hidden_count,
                                                    'local-page-generator'
                                                ),
                                                $hidden_count,
                                                implode(', ', $hidden_variables)
                                            )
                                        );
                                        ?>"
                                    >
                                        …
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="lpg-variable-actions">
                                <a
                                    href="<?php echo esc_url($dashboard_url); ?>"
                                    class="button"
                                >
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Analyser à nouveau', 'local-page-generator'); ?>
                                </a>

                                <a
                                    href="<?php echo esc_url($download_url); ?>"
                                    class="button button-secondary lpg-csv-button"
                                >
                                    <span class="dashicons dashicons-download"></span>
                                    <?php esc_html_e(
                                        'Générer le modèle CSV',
                                        'local-page-generator'
                                    ); ?>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="lpg-inline-warning">
                                <strong>
                                    <?php esc_html_e(
                                        'Aucune variable LPG détectée.',
                                        'local-page-generator'
                                    ); ?>
                                </strong>

                                <p>
                                    <?php esc_html_e(
                                        'Ajoutez une balise dynamique « Variable LPG » dans le modèle Elementor, enregistrez-le puis relancez l’analyse.',
                                        'local-page-generator'
                                    ); ?>
                                </p>
                            </div>

                            <a href="<?php echo esc_url($dashboard_url); ?>" class="button">
                                <span class="dashicons dashicons-update"></span>
                                <?php esc_html_e('Relancer l’analyse', 'local-page-generator'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ÉTAPE 2 : IMPORT DU CSV -->
        <section class="lpg-card lpg-card-full">
            <div class="lpg-card-number">2</div>

            <div class="lpg-card-content">
                <div class="lpg-card-header">
                    <div>
                        <h2><?php esc_html_e('Importer un fichier CSV', 'local-page-generator'); ?></h2>

                        <p>
                            <?php esc_html_e(
                                'Chaque colonne correspondra à une variable et chaque ligne à une nouvelle page.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <?php if (!empty($csv_preview)) : ?>
                        <span class="lpg-badge lpg-badge-success">
                            <?php esc_html_e('Fichier valide', 'local-page-generator'); ?>
                        </span>
                    <?php elseif (!empty($csv_import_errors)) : ?>
                        <span class="lpg-badge lpg-badge-error">
                            <?php esc_html_e('À corriger', 'local-page-generator'); ?>
                        </span>
                    <?php elseif ($can_import_csv) : ?>
                        <span class="lpg-badge">
                            <?php esc_html_e('Prêt', 'local-page-generator'); ?>
                        </span>
                    <?php else : ?>
                        <span class="lpg-badge lpg-badge-warning">
                            <?php esc_html_e('En attente du modèle', 'local-page-generator'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($csv_import_errors)) : ?>
                    <div class="lpg-import-errors" role="alert">
                        <strong>
                            <?php esc_html_e('Impossible d’importer le CSV', 'local-page-generator'); ?>
                        </strong>

                        <ul>
                            <?php foreach ($csv_import_errors as $import_error) : ?>
                                <li><?php echo esc_html($import_error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($csv_preview)) : ?>
                    <div class="lpg-import-success">
                        <strong><?php esc_html_e('Fichier valide', 'local-page-generator'); ?></strong>

                        <div class="lpg-import-summary">
                            <span class="lpg-import-stat">
                                <?php echo esc_html($csv_preview['filename'] ?? ''); ?>
                            </span>

                            <span class="lpg-import-stat">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        _n(
                                            '%d page détectée',
                                            '%d pages détectées',
                                            absint($csv_preview['row_count'] ?? 0),
                                            'local-page-generator'
                                        ),
                                        absint($csv_preview['row_count'] ?? 0)
                                    )
                                );
                                ?>
                            </span>

                            <span class="lpg-import-stat">
                                <?php
                                $column_count = isset($csv_preview['headers']) && is_array($csv_preview['headers'])
                                    ? count($csv_preview['headers'])
                                    : 0;

                                echo esc_html(
                                    sprintf(
                                        _n(
                                            '%d colonne',
                                            '%d colonnes',
                                            $column_count,
                                            'local-page-generator'
                                        ),
                                        $column_count
                                    )
                                );
                                ?>
                            </span>

                            <span class="lpg-import-stat">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        __('Séparateur : %s', 'local-page-generator'),
                                        $delimiter_label
                                    )
                                );
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$can_import_csv) : ?>
                    <div class="lpg-inline-warning">
                        <strong>
                            <?php esc_html_e('L’importation n’est pas encore disponible.', 'local-page-generator'); ?>
                        </strong>

                        <p>
                            <?php
                            if (!$selected_template_id) {
                                esc_html_e(
                                    'Sélectionnez et enregistrez d’abord un modèle Elementor.',
                                    'local-page-generator'
                                );
                            } else {
                                esc_html_e(
                                    'Ajoutez au moins une variable LPG au modèle Elementor avant d’importer un CSV.',
                                    'local-page-generator'
                                );
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form
                    class="lpg-upload-form"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                    method="post"
                    enctype="multipart/form-data"
                >
                    <input type="hidden" name="action" value="lpg_import_csv">
                    <input
                        type="hidden"
                        name="template_id"
                        value="<?php echo esc_attr($selected_template_id); ?>"
                    >

                    <?php wp_nonce_field('lpg_import_csv', 'lpg_csv_nonce'); ?>

                    <div class="lpg-upload-zone">
                        <span class="dashicons dashicons-upload"></span>

                        <label for="lpg-csv-file">
                            <strong>
                                <?php
                                echo esc_html(
                                    !empty($csv_preview)
                                        ? __('Choisir un autre CSV', 'local-page-generator')
                                        : __('Choisir un fichier CSV', 'local-page-generator')
                                );
                                ?>
                            </strong>
                        </label>

                        <small>
                            <?php esc_html_e('Fichier .csv de 2 Mo maximum', 'local-page-generator'); ?>
                        </small>

                        <input
                            id="lpg-csv-file"
                            class="lpg-file-input"
                            type="file"
                            name="lpg_csv_file"
                            accept=".csv"
                            required
                            <?php disabled(!$can_import_csv); ?>
                        >

                        <button
                            type="submit"
                            class="button button-primary"
                            <?php disabled(!$can_import_csv); ?>
                        >
                            <?php esc_html_e('Importer et vérifier le CSV', 'local-page-generator'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ÉTAPE 3 : APERÇU -->
        <section class="lpg-card lpg-card-full">
            <div class="lpg-card-number">3</div>

            <div class="lpg-card-content">
                <div class="lpg-card-header">
                    <div>
                        <h2>
                            <?php esc_html_e(
                                'Vérifier les futures pages',
                                'local-page-generator'
                            ); ?>
                        </h2>

                        <p>
                            <?php esc_html_e(
                                'Un aperçu des lignes du CSV apparaîtra ici avant la génération.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <?php if ($can_generate) : ?>
                        <span class="lpg-badge lpg-badge-success">
                            <?php
                            echo esc_html(
                                sprintf(
                                    _n(
                                        '%d page prête',
                                        '%d pages prêtes',
                                        $preview_row_count,
                                        'local-page-generator'
                                    ),
                                    $preview_row_count
                                )
                            );
                            ?>
                        </span>
                    <?php else : ?>
                        <span class="lpg-badge">
                            <?php esc_html_e('En attente du CSV', 'local-page-generator'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($can_generate) : ?>
                    <div class="lpg-preview-table-wrapper">
                        <table class="widefat striped lpg-preview-table">
                            <thead>
                                <tr>
                                    <?php foreach ($preview_headers as $preview_header) : ?>
                                        <th scope="col">
                                            <?php echo esc_html($preview_header); ?>
                                        </th>
                                    <?php endforeach; ?>

                                    <th scope="col">
                                        <?php esc_html_e('Statut', 'local-page-generator'); ?>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($preview_rows as $preview_row) : ?>
                                    <tr>
                                        <?php foreach ($preview_headers as $preview_header) : ?>
                                            <?php
                                            $preview_value = is_array($preview_row)
                                                && isset($preview_row[$preview_header])
                                                ? (string) $preview_row[$preview_header]
                                                : '';
                                            ?>
                                            <td>
                                                <?php
                                                echo '' === $preview_value
                                                    ? '<span aria-hidden="true">—</span>'
                                                    : esc_html(wp_html_excerpt($preview_value, 140, '…'));
                                                ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <td>
                                            <span class="lpg-status lpg-status-ready">
                                                <?php esc_html_e('Prête', 'local-page-generator'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (10 < $preview_row_count) : ?>
                        <p class="lpg-preview-limit">
                            <?php
                            echo esc_html(
                                sprintf(
                                    __('Les 10 premières pages sont affichées sur un total de %d.', 'local-page-generator'),
                                    $preview_row_count
                                )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="lpg-empty-state">
                        <span class="dashicons dashicons-visibility"></span>
                        <p>
                            <?php esc_html_e(
                                'Importez un fichier CSV pour afficher les pages qui seront créées.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ÉTAPE 4 : GÉNÉRATION -->
        <section class="lpg-card lpg-card-full">
            <div class="lpg-card-number">4</div>

            <div class="lpg-card-content">
                <div class="lpg-card-header">
                    <div>
                        <h2><?php esc_html_e('Générer les pages', 'local-page-generator'); ?></h2>

                        <p>
                            <?php esc_html_e(
                                'Choisissez le statut WordPress des pages avant de lancer leur création.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <?php if (!empty($generated_pages) && empty($generation_errors)) : ?>
                        <span class="lpg-badge lpg-badge-success">
                            <?php esc_html_e('Terminé', 'local-page-generator'); ?>
                        </span>
                    <?php elseif (!empty($generation_errors)) : ?>
                        <span class="lpg-badge lpg-badge-error">
                            <?php esc_html_e('Action requise', 'local-page-generator'); ?>
                        </span>
                    <?php elseif ($can_generate) : ?>
                        <span class="lpg-badge lpg-badge-success">
                            <?php esc_html_e('Prêt à générer', 'local-page-generator'); ?>
                        </span>
                    <?php else : ?>
                        <span class="lpg-badge lpg-badge-warning">
                            <?php esc_html_e('En attente du CSV', 'local-page-generator'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($generation_errors)) : ?>
                    <div class="lpg-generation-errors" role="alert">
                        <strong>
                            <?php esc_html_e('Certaines pages n’ont pas pu être générées', 'local-page-generator'); ?>
                        </strong>

                        <ul>
                            <?php foreach ($generation_errors as $generation_error) : ?>
                                <li><?php echo esc_html($generation_error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($generated_pages)) : ?>
                    <div class="lpg-generation-success">
                        <strong>
                            <?php
                            echo esc_html(
                                sprintf(
                                    _n(
                                        '%d page a été générée.',
                                        '%d pages ont été générées.',
                                        count($generated_pages),
                                        'local-page-generator'
                                    ),
                                    count($generated_pages)
                                )
                            );
                            ?>
                        </strong>

                        <ul class="lpg-generated-pages">
                            <?php foreach (array_slice($generated_pages, 0, 10) as $generated_page) : ?>
                                <?php
                                $generated_post_id = absint($generated_page['post_id'] ?? 0);
                                $edit_url          = $generated_post_id
                                    ? get_edit_post_link($generated_post_id, '')
                                    : '';
                                $view_url          = $generated_post_id
                                    ? get_permalink($generated_post_id)
                                    : '';
                                ?>
                                <li>
                                    <span><?php echo esc_html($generated_page['title'] ?? ''); ?></span>

                                    <?php if ($edit_url) : ?>
                                        <a href="<?php echo esc_url($edit_url); ?>">
                                            <?php esc_html_e('Modifier', 'local-page-generator'); ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($view_url) : ?>
                                        <a href="<?php echo esc_url($view_url); ?>">
                                            <?php esc_html_e('Voir', 'local-page-generator'); ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a
                            class="button"
                            href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>"
                        >
                            <?php esc_html_e('Voir toutes les pages', 'local-page-generator'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($can_generate) : ?>
                    <form
                        class="lpg-generation-form"
                        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                        method="post"
                    >
                        <input type="hidden" name="action" value="lpg_generate_pages">
                        <input
                            type="hidden"
                            name="template_id"
                            value="<?php echo esc_attr($selected_template_id); ?>"
                        >

                        <?php wp_nonce_field('lpg_generate_pages', 'lpg_generate_nonce'); ?>

                        <div class="lpg-generation-row">
                            <div class="lpg-field">
                                <label for="lpg-page-status">
                                    <?php esc_html_e('Statut des pages', 'local-page-generator'); ?>
                                </label>

                                <select id="lpg-page-status" name="page_status" required>
                                    <option value="draft">
                                        <?php esc_html_e('Brouillon', 'local-page-generator'); ?>
                                    </option>
                                    <option value="publish">
                                        <?php esc_html_e('Publié', 'local-page-generator'); ?>
                                    </option>
                                    <option value="pending">
                                        <?php esc_html_e('En attente de relecture', 'local-page-generator'); ?>
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        _n(
                                            'Générer %d page',
                                            'Générer %d pages',
                                            $preview_row_count,
                                            'local-page-generator'
                                        ),
                                        $preview_row_count
                                    )
                                );
                                ?>
                            </button>
                        </div>
                    </form>
                <?php elseif (empty($generated_pages)) : ?>
                    <div class="lpg-empty-state">
                        <span class="dashicons dashicons-admin-page"></span>
                        <p>
                            <?php esc_html_e(
                                'Validez un fichier CSV pour activer la génération.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>
