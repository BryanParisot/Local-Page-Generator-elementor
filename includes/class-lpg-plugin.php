<?php

defined('ABSPATH') || exit;

require_once LPG_PATH . 'includes/class-lpg-elementor.php';
require_once LPG_PATH . 'includes/class-lpg-template-variables.php';
require_once LPG_PATH . 'includes/class-lpg-csv-importer.php';
require_once LPG_PATH . 'includes/class-lpg-page-generator.php';
require_once LPG_PATH . 'admin/class-lpg-admin.php';

class LPG_Plugin
{
    public function run()
    {
        $elementor = new LPG_Elementor();
        $elementor->register_hooks();

        if (is_admin()) {
            $template_variables = new LPG_Template_Variables();
            $csv_importer       = new LPG_CSV_Importer();
            $page_generator     = new LPG_Page_Generator();

            $admin = new LPG_Admin(
                $elementor,
                $template_variables,
                $csv_importer,
                $page_generator
            );

            $admin->register_hooks();
        }
    }
}
