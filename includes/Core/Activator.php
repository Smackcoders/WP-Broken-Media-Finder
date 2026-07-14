<?php
namespace Smackcoders\BrokenMediaFinder\Core;

use Smackcoders\BrokenMediaFinder\Scanner\ScanTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	public static function activate() {
		ScanTable::create_table();
		self::set_default_settings();
		flush_rewrite_rules();
	}

	private static function set_default_settings() {
		if ( false !== get_option( 'wpbmf_settings' ) ) {
			return;
		}
		update_option( 'wpbmf_settings', array(
			'enable_plugin'              => '1',
			'post_types'                 => array( 'post', 'page' ),
			'scan_content_images'        => '1',
			'scan_attachment_links'      => '1',
			'scan_featured_images'       => '1',
			'scan_unused_media'          => '1',
			'include_attached_in_unused' => '0',
			'placeholder_source'         => 'default',
			'custom_placeholder_url'     => '',
			'keep_previous_results'      => '1',
			'show_dashboard_widget'      => '1',
			'delete_data_on_uninstall'   => '0',
		) );
	}
}
