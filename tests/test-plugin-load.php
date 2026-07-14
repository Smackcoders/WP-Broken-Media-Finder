<?php
class PluginLoadTest extends WP_UnitTestCase
{

	public function test_constants()
	{
		$this->assertTrue(defined('WPBMF_VERSION'));
		$this->assertTrue(defined('WPBMF_PLUGIN_DIR'));
		$this->assertTrue(defined('WPBMF_DB_VERSION'));
	}

	public function test_table_created_on_activation()
	{
		global $wpdb;
		\Smackcoders\BrokenMediaFinder\Core\Activator::activate();
		$table = $wpdb->prefix . 'wpbmf_scan_results';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
		$this->assertEquals($table, $exists);
	}

	public function test_default_settings()
	{
		\Smackcoders\BrokenMediaFinder\Core\Activator::activate();
		$s = get_option('wpbmf_settings');
		$this->assertIsArray($s);
		$this->assertEquals('1', $s['scan_content_images']);
		$this->assertEquals('1', $s['scan_featured_images']);
		$this->assertEquals('1', $s['scan_unused_media']);
		$this->assertEquals('default', $s['placeholder_source']);
	}
}
