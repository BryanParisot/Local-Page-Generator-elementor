<?php

defined('ABSPATH') || exit;

class LPG_Template_Variables
{
    /**
     * Récupère les variables LPG d'un modèle Elementor.
     */
    public function get_variables($template_id)
    {
        $template_id = absint($template_id);

        if (!$template_id) {
            return [];
        }

        if ('elementor_library' !== get_post_type($template_id)) {
            return [];
        }

        $elementor_data = get_post_meta(
            $template_id,
            '_elementor_data',
            true
        );

        if (empty($elementor_data)) {
            return [];
        }

        if (is_string($elementor_data)) {
            $elementor_data = json_decode(
                $elementor_data,
                true
            );
        }

        if (!is_array($elementor_data)) {
            return [];
        }

        $variables = [];

        $this->walk_through_elements(
            $elementor_data,
            $variables
        );

        $variables = array_filter(
            array_unique($variables)
        );

        sort($variables, SORT_NATURAL);

        return array_values($variables);
    }

    /**
     * Parcourt récursivement les données Elementor.
     */
    private function walk_through_elements(
        $element,
        &$variables
    ) {
        if (is_array($element)) {
            foreach ($element as $value) {
                $this->walk_through_elements(
                    $value,
                    $variables
                );
            }

            return;
        }

        if (!is_string($element)) {
            return;
        }

        $this->extract_variables_from_string(
            $element,
            $variables
        );
    }

    /**
     * Recherche les balises dynamiques LPG dans une chaîne.
     */
    private function extract_variables_from_string(
        $value,
        &$variables
    ) {
        if (false === strpos($value, '[elementor-tag')) {
            return;
        }

        preg_match_all(
            '/\[elementor-tag\s+([^\]]+)\]/',
            $value,
            $matches
        );

        if (empty($matches[1])) {
            return;
        }

        foreach ($matches[1] as $attributes_string) {
            $attributes = shortcode_parse_atts(
                html_entity_decode(
                    $attributes_string,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );

            if (!is_array($attributes)) {
                continue;
            }

            if (
                empty($attributes['name'])
                || 'lpg-variable' !== $attributes['name']
            ) {
                continue;
            }

            if (empty($attributes['settings'])) {
                continue;
            }

            $settings_json = rawurldecode(
                $attributes['settings']
            );

            $settings = json_decode(
                $settings_json,
                true
            );

            if (
                !is_array($settings)
                || empty($settings['variable_key'])
            ) {
                continue;
            }

            $variable_key = sanitize_key(
                $settings['variable_key']
            );

            if ('' !== $variable_key) {
                $variables[] = $variable_key;
            }
        }
    }
}
