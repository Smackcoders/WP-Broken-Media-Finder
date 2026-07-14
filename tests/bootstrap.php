<?php
if (!defined('ABSPATH')) {
	if (!getenv('WP_TESTS_DIR')) {
		exit;
	}
}

$wpbmf_tests_dir = getenv('WP_TESTS_DIR') ?: rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
if (!file_exists($wpbmf_tests_dir . '/includes/functions.php')) {

	echo 'WP test suite not found at: ' . htmlspecialchars($wpbmf_tests_dir, ENT_QUOTES, 'UTF-8') . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Test bootstrap only, no WP functions available.
	exit(1);
}
require_once $wpbmf_tests_dir . '/includes/functions.php';
function wpbmf_load_plugin()
{
	require dirname(__DIR__) . '/broken-media-finder.php';
}
tests_add_filter('muplugins_loaded', 'wpbmf_load_plugin');
require $wpbmf_tests_dir . '/includes/bootstrap.php';
