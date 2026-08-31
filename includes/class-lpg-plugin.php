<?php

defined('ABSPATH') || exit;

require_once LPG_PATH . 'admin/class-lpg-admin.php';

class LPG_Plugin
{
    /**
     * Démarre le plugin.
     */
    public function run()
    {
        if (is_admin()) {
            new LPG_Admin();
        }
    }
}