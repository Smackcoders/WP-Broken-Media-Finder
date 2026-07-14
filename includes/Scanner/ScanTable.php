<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ScanTable {

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wpbmf_scan_results';
	}

	public static function create_table() {
		global $wpdb;
		$table        = self::get_table_name();
		$charset_coll = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scan_id varchar(100) NOT NULL,
			item_type varchar(50) NOT NULL,
			issue_type varchar(100) NOT NULL,
			issue_status varchar(50) NOT NULL DEFAULT 'open',
			post_id bigint(20) unsigned DEFAULT NULL,
			attachment_id bigint(20) unsigned DEFAULT NULL,
			source_url text DEFAULT NULL,
			resolved_url text DEFAULT NULL,
			file_path text DEFAULT NULL,
			message text DEFAULT NULL,
			severity varchar(50) NOT NULL DEFAULT 'medium',
			error_type varchar(100) DEFAULT NULL,
			created_at datetime NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at datetime DEFAULT NULL,
			updated_at_gmt datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY scan_id (scan_id(50)),
			KEY item_type (item_type),
			KEY issue_type (issue_type),
			KEY issue_status (issue_status),
			KEY post_id (post_id),
			KEY attachment_id (attachment_id),
			KEY created_at_gmt (created_at_gmt)
		) {$charset_coll};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'wpbmf_db_version', WPBMF_DB_VERSION );
	}
}
