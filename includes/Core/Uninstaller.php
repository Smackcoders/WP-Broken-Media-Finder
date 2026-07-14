<?php
namespace Smackcoders\BrokenMediaFinder\Core;

if (!defined('ABSPATH')) {
	exit;
}

class Uninstaller
{

	public static function uninstall()
	{
		$settings = get_option('wpbmf_settings', array());
		if (empty($settings['delete_data_on_uninstall'])) {
			return;
		}
		global $wpdb;
		delete_option('wpbmf_settings');
		delete_option('wpbmf_db_version');
		delete_option('wpbmf_scan_history');
		$table = $wpdb->prefix . 'wpbmf_scan_results';

		$table_clean = esc_sql($table);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query("DROP TABLE IF EXISTS " . $table_clean);
	}
}
