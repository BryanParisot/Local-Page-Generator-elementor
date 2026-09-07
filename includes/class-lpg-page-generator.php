<?php

defined('ABSPATH') || exit;

class LPG_Page_Generator
{
    /**
     * Crée les pages à partir d'un modèle Elementor et des lignes validées.
     *
     * @param int      $template_id Identifiant du modèle Elementor.
     * @param array    $rows        Lignes du CSV validé.
     * @param string[] $variables   Variables LPG attendues.
     * @param string   $post_status Statut des pages à créer.
     *
     * @return array
     */
    public function generate($template_id, $rows, $variables, $post_status)
    {
        $result = [
            'generated' => [],
            'errors'    => [],
        ];

        $template_id = absint($template_id);
        $template    = $template_id ? get_post($template_id) : null;

        if (!$template instanceof WP_Post || 'elementor_library' !== $template->post_type) {
            $result['errors'][] = 'Le modèle Elementor est invalide.';
            return $result;
        }

        if (!in_array($post_status, ['draft', 'publish', 'pending'], true)) {
            $result['errors'][] = 'Le statut demandé pour les pages est invalide.';
            return $result;
        }

        if (!is_array($rows) || empty($rows)) {
            $result['errors'][] = 'Aucune page ne peut être générée sans données CSV.';
            return $result;
        }

        $elementor_data = get_post_meta($template_id, '_elementor_data', true);

        if (is_array($elementor_data)) {
            $elementor_data = wp_json_encode($elementor_data);
        }

        if (!is_string($elementor_data) || '' === $elementor_data) {
            $result['errors'][] = 'Le modèle ne contient aucune donnée Elementor à copier.';
            return $result;
        }

        $variables = array_values(
            array_unique(
                array_filter(
                    array_map('sanitize_key', (array) $variables)
                )
            )
        );
        $prepared_rows = [];
        $seen_slugs    = [];

        foreach ($rows as $row_index => $row) {
            $line_number = $row_index + 2;

            if (!is_array($row)) {
                $result['errors'][] = sprintf(
                    'Les données de la ligne %d sont invalides.',
                    $line_number
                );
                continue;
            }

            $page_title = isset($row['page_title'])
                ? sanitize_text_field((string) $row['page_title'])
                : '';
            $slug = isset($row['slug'])
                ? sanitize_title((string) $row['slug'])
                : '';

            if ('' === $page_title || '' === $slug) {
                $result['errors'][] = sprintf(
                    'La ligne %d ne contient pas de titre ou de slug valide.',
                    $line_number
                );
                continue;
            }

            if (isset($seen_slugs[$slug])) {
                $result['errors'][] = sprintf(
                    'Le slug « %s » est présent plusieurs fois.',
                    $slug
                );
                continue;
            }

            $seen_slugs[$slug] = true;
            $existing_post     = get_page_by_path(
                $slug,
                OBJECT,
                ['page', 'attachment']
            );

            if ($existing_post instanceof WP_Post) {
                $result['errors'][] = sprintf(
                    'Le slug « %s » est déjà utilisé dans WordPress.',
                    $slug
                );
                continue;
            }

            $row['page_title'] = $page_title;
            $row['slug']       = $slug;
            $prepared_rows[]   = $row;
        }

        /* Aucun contenu n'est créé tant que tous les contrôles préalables n'ont pas réussi. */
        if (!empty($result['errors'])) {
            return $result;
        }

        $page_settings = get_post_meta($template_id, '_elementor_page_settings', true);
        $page_template = get_post_meta($template_id, '_wp_page_template', true);
        $elementor_version = get_post_meta($template_id, '_elementor_version', true);
        $elementor_pro_version = get_post_meta($template_id, '_elementor_pro_version', true);

        foreach ($prepared_rows as $row) {
            $post_id = wp_insert_post(
                wp_slash(
                    [
                        'post_type'    => 'page',
                        'post_status'  => $post_status,
                        'post_title'   => $row['page_title'],
                        'post_name'    => $row['slug'],
                        'post_content' => (string) $template->post_content,
                        'post_author'  => get_current_user_id(),
                    ]
                ),
                true
            );

            if (is_wp_error($post_id)) {
                $result['errors'][] = sprintf(
                    'La page « %1$s » n’a pas pu être créée : %2$s',
                    $row['page_title'],
                    $post_id->get_error_message()
                );
                continue;
            }

            update_post_meta($post_id, '_elementor_data', wp_slash($elementor_data));
            update_post_meta($post_id, '_elementor_edit_mode', 'builder');

            if ('' !== $page_settings && [] !== $page_settings) {
                update_post_meta($post_id, '_elementor_page_settings', $page_settings);
            }

            if (is_string($page_template) && '' !== $page_template) {
                update_post_meta($post_id, '_wp_page_template', $page_template);
            }

            if (is_string($elementor_version) && '' !== $elementor_version) {
                update_post_meta($post_id, '_elementor_version', $elementor_version);
            }

            if (is_string($elementor_pro_version) && '' !== $elementor_pro_version) {
                update_post_meta($post_id, '_elementor_pro_version', $elementor_pro_version);
            }

            foreach ($variables as $variable) {
                $value = isset($row[$variable])
                    ? sanitize_textarea_field((string) $row[$variable])
                    : '';

                update_post_meta(
                    $post_id,
                    '_lpg_var_' . $variable,
                    wp_slash($value)
                );
            }

            update_post_meta($post_id, '_lpg_generated', 1);
            update_post_meta($post_id, '_lpg_template_id', $template_id);

            $result['generated'][] = [
                'post_id' => absint($post_id),
                'title'   => $row['page_title'],
                'slug'    => $row['slug'],
            ];
        }

        return $result;
    }
}
