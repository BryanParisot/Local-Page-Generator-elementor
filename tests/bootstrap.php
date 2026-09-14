<?php
/**
 * Charge WordPress puis le plugin pour la suite PHPUnit.
 */

$tests_dir = getenv('WP_TESTS_DIR');

if (!$tests_dir) {
    $tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($composer_autoload)) {
    echo "Les dépendances Composer sont absentes. Exécutez `composer install`." . PHP_EOL;
    exit(1);
}

require_once $composer_autoload;

if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define(
        'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
        dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills'
    );
}

if (!file_exists($tests_dir . '/includes/functions.php')) {
    echo "La suite de tests WordPress est absente. Exécutez `composer test:install`." . PHP_EOL;
    exit(1);
}

require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
    'muplugins_loaded',
    static function () {
        require dirname(__DIR__) . '/local-page-generator.php';
    }
);

require $tests_dir . '/includes/bootstrap.php';

