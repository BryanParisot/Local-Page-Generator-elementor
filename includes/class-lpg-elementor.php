<?php

defined('ABSPATH') || exit;

class LPG_Elementor
{
    /**
     * Vérifie si Elementor est chargé.
     */
    public function is_active()
    {
        return did_action('elementor/loaded')
            || defined('ELEMENTOR_VERSION');
    }

    /**
     * Récupère la version installée.
     */
    public function get_version()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return '';
        }

        return ELEMENTOR_VERSION;
    }

    /**
     * Récupère les modèles enregistrés localement.
     */
    public function get_templates()
    {
        if (!$this->is_active()) {
            return [];
        }

        return get_posts([
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ]);
    }

    /**
     * Récupère le type d'un modèle.
     */
    public function get_template_type($template_id)
    {
        return sanitize_key(
            get_post_meta(
                $template_id,
                '_elementor_template_type',
                true
            )
        );
    }

    /**
     * Vérifie qu'un modèle existe réellement.
     */
    public function template_exists($template_id)
    {
        $template = get_post($template_id);

        if (!$template) {
            return false;
        }

        return 'elementor_library' === $template->post_type
            && 'publish' === $template->post_status;
    }
}