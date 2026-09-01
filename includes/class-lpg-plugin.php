<?php

defined('ABSPATH') || exit;

require_once LPG_PATH . 'includes/class-lpg-elementor.php';
require_once LPG_PATH . 'admin/class-lpg-admin.php';

class LPG_Plugin
{
    public function run()
    {
        if (!is_admin()) {
            return;
        }

        $elementor = new LPG_Elementor();

        new LPG_Admin($elementor);
    }
}