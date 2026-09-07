<?php
/**
 * Plugin Name: Local Page Generator
 * Description: Génère des pages Elementor à partir d’un fichier CSV.
 * Version: 0.6.0
 * Author: Bryan Parisot
 * License: GPL-2.0-or-later
 * Text Domain: local-page-generator
 */

defined('ABSPATH') || exit;

define('LPG_VERSION', '0.6.0');
define('LPG_PATH', plugin_dir_path(__FILE__));
define('LPG_URL', plugin_dir_url(__FILE__));

require_once LPG_PATH . 'includes/class-lpg-plugin.php';

/**
 * Démarre le plugin après le chargement des extensions.
 */
function lpg_run_plugin()
{
    require_once LPG_PATH . 'includes/class-lpg-plugin.php';

    $plugin = new LPG_Plugin();
    $plugin->run();
}

lpg_run_plugin();
