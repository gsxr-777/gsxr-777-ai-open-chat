<?php
/**
 * PHPUnit bootstrap for the WordPress test suite.
 */

$tests_dir = getenv('WP_TESTS_DIR');
if (!$tests_dir) {
    $tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($tests_dir . '/includes/functions.php')) {
    fwrite(STDERR, "WordPress test suite not found. Set WP_TESTS_DIR.\n");
    exit(1);
}

if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define(
        'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
        dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills'
    );
}

require_once $tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function() {
    require dirname(__DIR__) . '/gsxr-777-ai-open-chat.php';
});

require $tests_dir . '/includes/bootstrap.php';

if (class_exists('GSXR_777_Core')) {
    GSXR_777_Core::activate();
}
