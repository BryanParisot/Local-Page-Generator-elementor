<?php

defined('ABSPATH') || exit;

class LPG_Admin
{
    /**
     * Identifiant de la page d'administration.
     *
     * @var string
     */
    private $page_hook = '';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Ajoute le menu Page Generator dans WordPress.
     */
    public function register_menu()
    {
        $this->page_hook = add_menu_page(
            __('Local Page Generator', 'local-page-generator'),
            __('Page Generator', 'local-page-generator'),
            'manage_options',
            'local-page-generator',
            [$this, 'render_dashboard'],
            'dashicons-admin-page',
            25
        );
    }

    /**
     * Affiche la page principale du plugin.
     */
    public function render_dashboard()
    {
        require LPG_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Charge les styles uniquement sur la page du plugin.
     */
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
}