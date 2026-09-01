<?php

defined('ABSPATH') || exit;
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

    <div class="lpg-notice">
        <span class="lpg-notice-dot"></span>

        <div>
            <?php if ($elementor_active) : ?>

                <?php esc_html_e(
                    'Connexion Elementor active',
                    'local-page-generator'
                ); ?>

            <?php else : ?>

                <?php esc_html_e(
                    'La sélection du modèle est maintenant fonctionnelle. Les autres étapes seront connectées progressivement.',
                    'local-page-generator'
                ); ?>

            <?php endif; ?>
        </div>
    </div>

    <nav class="lpg-steps" aria-label="<?php esc_attr_e(
                                            'Étapes de génération',
                                            'local-page-generator'
                                        ); ?>">

        <div class="lpg-step lpg-step-active">
            <span>1</span>
            <div>
                <strong>
                    <?php esc_html_e('Modèle', 'local-page-generator'); ?>
                </strong>
                <small>
                    <?php esc_html_e('Elementor', 'local-page-generator'); ?>
                </small>
            </div>
        </div>

        <div class="lpg-step">
            <span>2</span>
            <div>
                <strong>
                    <?php esc_html_e('Importation', 'local-page-generator'); ?>
                </strong>
                <small>
                    <?php esc_html_e('Fichier CSV', 'local-page-generator'); ?>
                </small>
            </div>
        </div>

        <div class="lpg-step">
            <span>3</span>
            <div>
                <strong>
                    <?php esc_html_e('Vérification', 'local-page-generator'); ?>
                </strong>
                <small>
                    <?php esc_html_e('Aperçu', 'local-page-generator'); ?>
                </small>
            </div>
        </div>

        <div class="lpg-step">
            <span>4</span>
            <div>
                <strong>
                    <?php esc_html_e('Génération', 'local-page-generator'); ?>
                </strong>
                <small>
                    <?php esc_html_e('Création des pages', 'local-page-generator'); ?>
                </small>
            </div>
        </div>

    </nav>

    <div class="lpg-grid">

        <!-- ÉTAPE 1 -->
        <section class="lpg-card">
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
                                'Ce modèle sera utilisé pour toutes les pages générées.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <?php if ($elementor_active) : ?>

                        <span class="lpg-badge lpg-badge-success">
                            <?php
                            echo esc_html(
                                sprintf(
                                    'Elementor connecté — version %s',
                                    $elementor_version
                                )
                            );
                            ?>
                        </span>

                    <?php else : ?>

                        <span class="lpg-badge lpg-badge-error">
                            <?php
                            esc_html_e(
                                'Elementor absent ou désactivé',
                                'local-page-generator'
                            );
                            ?>
                        </span>

                    <?php endif; ?>
                </div>

                <?php if (!$elementor_active) : ?>

                    <div class="lpg-inline-error">
                        <strong>
                            <?php
                            esc_html_e(
                                'Elementor est nécessaire pour continuer.',
                                'local-page-generator'
                            );
                            ?>
                        </strong>

                        <p>
                            <?php
                            esc_html_e(
                                'Installez ou activez Elementor pour récupérer les modèles.',
                                'local-page-generator'
                            );
                            ?>
                        </p>
                    </div>

                <?php else : ?>

                    <form
                        class="lpg-template-form"
                        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                        method="post">
                        <input
                            type="hidden"
                            name="action"
                            value="lpg_save_template">

                        <?php
                        wp_nonce_field(
                            'lpg_save_template',
                            'lpg_nonce'
                        );
                        ?>

                        <label for="lpg-template">
                            <?php
                            esc_html_e(
                                'Modèle Elementor',
                                'local-page-generator'
                            );
                            ?>
                        </label>

                        <select
                            id="lpg-template"
                            name="lpg_template_id"
                            required
                            <?php disabled(empty($templates)); ?>>
                            <option value="">
                                <?php
                                esc_html_e(
                                    'Sélectionnez un modèle',
                                    'local-page-generator'
                                );
                                ?>
                            </option>

                            <?php foreach ($templates as $template) : ?>

                                <?php
                                $template_type = sanitize_key(
                                    get_post_meta(
                                        $template->ID,
                                        '_elementor_template_type',
                                        true
                                    )
                                );
                                ?>

                                <option
                                    value="<?php echo esc_attr($template->ID); ?>"
                                    <?php
                                    selected(
                                        $selected_template_id,
                                        $template->ID
                                    );
                                    ?>>
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            '%s — %s',
                                            $template->post_title,
                                            $template_type ?: 'modèle'
                                        )
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>
                        </select>

                        <?php if (empty($templates)) : ?>

                            <p class="description">
                                <?php
                                esc_html_e(
                                    'Aucun modèle Elementor publié n’a été trouvé.',
                                    'local-page-generator'
                                );
                                ?>
                            </p>

                        <?php endif; ?>

                        <div class="lpg-template-actions">
                            <button
                                type="submit"
                                class="button button-primary"
                                <?php disabled(empty($templates)); ?>>
                                <?php
                                esc_html_e(
                                    'Enregistrer le modèle',
                                    'local-page-generator'
                                );
                                ?>
                            </button>

                            <?php if ($selected_template_id) : ?>

                                <span class="lpg-template-saved">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            'Modèle enregistré : #%d',
                                            $selected_template_id
                                        )
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </section>

        <!-- ÉTAPE 2 -->
        <section class="lpg-card">
            <div class="lpg-card-number">2</div>

            <div class="lpg-card-content">
                <h2>
                    <?php esc_html_e(
                        'Importer un fichier CSV',
                        'local-page-generator'
                    ); ?>
                </h2>

                <p>
                    <?php esc_html_e(
                        'Chaque colonne correspondra à une variable et chaque ligne à une nouvelle page.',
                        'local-page-generator'
                    ); ?>
                </p>

                <div class="lpg-upload-zone">
                    <span class="dashicons dashicons-upload"></span>

                    <strong>
                        <?php esc_html_e(
                            'Glissez votre fichier CSV ici',
                            'local-page-generator'
                        ); ?>
                    </strong>

                    <small>
                        <?php esc_html_e(
                            'ou sélectionnez-le depuis votre ordinateur',
                            'local-page-generator'
                        ); ?>
                    </small>

                    <button type="button" class="button" disabled>
                        <?php esc_html_e(
                            'Choisir un fichier CSV',
                            'local-page-generator'
                        ); ?>
                    </button>
                </div>
            </div>
        </section>

        <!-- ÉTAPE 3 -->
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
                                'Cet aperçu utilise temporairement des données de démonstration.',
                                'local-page-generator'
                            ); ?>
                        </p>
                    </div>

                    <span class="lpg-badge">
                        <?php esc_html_e(
                            '3 pages détectées',
                            'local-page-generator'
                        ); ?>
                    </span>
                </div>

                <div class="lpg-table-wrapper">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>
                                    <?php esc_html_e('Page', 'local-page-generator'); ?>
                                </th>
                                <th>
                                    <?php esc_html_e('Ville', 'local-page-generator'); ?>
                                </th>
                                <th>
                                    <?php esc_html_e('Slug', 'local-page-generator'); ?>
                                </th>
                                <th>
                                    <?php esc_html_e('Statut', 'local-page-generator'); ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Création de site internet à Nancy</td>
                                <td>Nancy</td>
                                <td>creation-site-internet-nancy</td>
                                <td>
                                    <span class="lpg-status">
                                        Prête
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Création de site internet à Metz</td>
                                <td>Metz</td>
                                <td>creation-site-internet-metz</td>
                                <td>
                                    <span class="lpg-status">
                                        Prête
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Création de site internet à Épinal</td>
                                <td>Épinal</td>
                                <td>creation-site-internet-epinal</td>
                                <td>
                                    <span class="lpg-status">
                                        Prête
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ÉTAPE 4 -->
        <section class="lpg-card lpg-card-full">
            <div class="lpg-card-number">4</div>

            <div class="lpg-card-content">
                <h2>
                    <?php esc_html_e(
                        'Générer les pages',
                        'local-page-generator'
                    ); ?>
                </h2>

                <p>
                    <?php esc_html_e(
                        'Choisissez le statut appliqué aux futures pages.',
                        'local-page-generator'
                    ); ?>
                </p>

                <div class="lpg-generation">
                    <div class="lpg-radio-group">
                        <label>
                            <input type="radio" checked disabled>
                            <?php esc_html_e(
                                'Créer en brouillon',
                                'local-page-generator'
                            ); ?>
                        </label>

                        <label>
                            <input type="radio" disabled>
                            <?php esc_html_e(
                                'Publier immédiatement',
                                'local-page-generator'
                            ); ?>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="button button-primary button-hero"
                        disabled>
                        <?php esc_html_e(
                            'Générer les pages',
                            'local-page-generator'
                        ); ?>
                    </button>
                </div>
            </div>
        </section>

    </div>
</div>