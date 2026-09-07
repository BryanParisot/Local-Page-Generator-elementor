<?php

defined('ABSPATH') || exit;

class LPG_Variable_Tag extends \Elementor\Core\DynamicTags\Tag
{
    /**
     * Identifiant technique de la balise.
     */
    public function get_name(): string
    {
        return 'lpg-variable';
    }

    /**
     * Nom affiché dans Elementor.
     */
    public function get_title(): string
    {
        return esc_html__(
            'Variable LPG',
            'local-page-generator'
        );
    }

    /**
     * Groupe dans lequel la balise apparaîtra.
     */
    public function get_group(): array
    {
        return ['lpg-variables'];
    }

    /**
     * Type de données retourné.
     */
    public function get_categories(): array
    {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
        ];
    }

    /**
     * Configuration de la variable.
     */
    protected function register_controls(): void
    {
        $this->add_control(
            'variable_key',
            [
                'label'       => esc_html__(
                    'Clé de la variable',
                    'local-page-generator'
                ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'ville',
                'description' => esc_html__(
                    'Utilisez des minuscules, sans accent ni espace.',
                    'local-page-generator'
                ),
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label'       => esc_html__(
                    'Valeur par défaut',
                    'local-page-generator'
                ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => esc_html__(
                    'Valeur utilisée si la variable est vide',
                    'local-page-generator'
                ),
            ]
        );
    }

    /**
     * Affiche la valeur de la variable.
     */
    public function render(): void
    {
        $variable_key = sanitize_key(
            (string) $this->get_settings('variable_key')
        );

        if ('' === $variable_key) {
            return;
        }

        $post_id = get_queried_object_id();

        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $value = get_post_meta(
            $post_id,
            '_lpg_var_' . $variable_key,
            true
        );

        if ('' === $value) {
            if ($this->is_elementor_editor()) {
                $value = '{{' . $variable_key . '}}';
            } else {
                $value = $this->get_settings('fallback');
            }
        }

        echo esc_html((string) $value);
    }

    /**
     * Vérifie si le modèle est ouvert dans Elementor.
     */
    private function is_elementor_editor(): bool
    {
        $elementor = \Elementor\Plugin::$instance;

        return isset($elementor->editor)
            && $elementor->editor->is_edit_mode();
    }
}
