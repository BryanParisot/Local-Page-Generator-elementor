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
            <strong>
                <?php esc_html_e(
                    'Interface de démonstration',
                    'local-page-generator'
                ); ?>
            </strong>

            <p>
                <?php esc_html_e(
                    'Les fonctionnalités seront connectées progressivement pendant les prochains épisodes.',
                    'local-page-generator'
                ); ?>
            </p>
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

                    <span class="lpg-badge lpg-badge-warning">
                        <?php esc_html_e(
                            'Connexion prévue au jour 4',
                            'local-page-generator'
                        ); ?>
                    </span>
                </div>

                <label for="lpg-template">
                    <?php esc_html_e(
                        'Modèle Elementor',
                        'local-page-generator'
                    ); ?>
                </label>

                <select id="lpg-template" disabled>
                    <option>
                        <?php esc_html_e(
                            'Aucun modèle chargé pour le moment',
                            'local-page-generator'
                        ); ?>
                    </option>
                </select>
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
                        disabled
                    >
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