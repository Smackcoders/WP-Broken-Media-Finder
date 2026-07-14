<?php
namespace Smackcoders\BrokenMediaFinder\Core;

use Smackcoders\BrokenMediaFinder\Admin\AdminMenu;
use Smackcoders\BrokenMediaFinder\Admin\DashboardWidget;
use Smackcoders\BrokenMediaFinder\Admin\MediaColumns;
use Smackcoders\BrokenMediaFinder\Scanner\ScanTable;

if (!defined('ABSPATH')) {
	exit;
}

class Plugin
{

	private static $instance = null;

	private function __construct()
	{
	}

	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function run()
	{

		if (is_admin()) {
			(new AdminMenu())->register();
			(new DashboardWidget())->register();
			(new MediaColumns())->register();
			add_action('admin_init', array($this, 'check_db_upgrade'));
		}
	}

	public function check_db_upgrade()
	{
		if (get_option('wpbmf_db_version') !== WPBMF_DB_VERSION) {
			ScanTable::create_table();
		}
	}


}
