<?php

defined('ABSPATH') || exit;

class LPG_Admin
{
    private $page_hook = '';

    /**
     * Gestionnaire de l'intégration Elementor.
     *
     * @var LPG_Elementor
     */
    private $elementor;

    public function __construct($elementor)
    {
        $this->elementor = $elementor;

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action(
            'admin_post_lpg_save_template',
            [$this, 'save_template']
        );
    }

    public function register_menu()
    {
        $this->page_hook = add_menu_page(
            __('Page Generator', 'local-page-generator'),
            __('Page Generator', 'local-page-generator'),
            'manage_options',
            'local-page-generator',
            [$this, 'render_dashboard'],
            'dashicons-admin-page',
            25
        );
    }

    public function render_dashboard()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Vous ne disposez pas des autorisations nécessaires.',
                    'local-page-generator'
                )
            );
        }

        $elementor_active = $this->elementor->is_active();
        $elementor_version = $this->elementor->get_version();

        $templates = $elementor_active
            ? $this->elementor->get_templates()
            : [];

        $selected_template_id = absint(
            get_option('lpg_template_id', 0)
        );

        $settings_updated = isset($_GET['lpg-updated'])
            && '1' === sanitize_text_field(
                wp_unslash($_GET['lpg-updated'])
            );

        $settings_error = isset($_GET['lpg-error'])
            ? sanitize_key(wp_unslash($_GET['lpg-error']))
            : '';

        require LPG_PATH . 'admin/views/dashboard.php';
    }

    public function enqueue_assets($hook_suffix)
    {
        if ($hook_suffix !== $this->page_hook) {
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
     * Enregistre le modèle sélectionné.
     */
    public function save_template()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'Action non autorisée.',
                    'local-page-generator'
                )
            );
        }

        check_admin_referer(
            'lpg_save_template',
            'lpg_nonce'
        );

        $template_id = isset($_POST['lpg_template_id'])
            ? absint(wp_unslash($_POST['lpg_template_id']))
            : 0;

        if (
            !$template_id
            || !$this->elementor->template_exists($template_id)
        ) {
            $this->redirect([
                'lpg-error' => 'invalid-template',
            ]);
        }

        update_option('lpg_template_id', $template_id);

        $this->redirect([
            'lpg-updated' => '1',
        ]);
    }

    /**
     * Redirige vers la page du plugin.
     */
    private function redirect($arguments = [])
    {
        $url = add_query_arg(
            array_merge(
                [
                    'page' => 'local-page-generator',
                ],
                $arguments
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }
}