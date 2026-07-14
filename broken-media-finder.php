<?php
/**
 * Plugin Name:       Broken Media Finder
 * Plugin URI:        https://wordpress.org/plugins/broken-media-finder/
 * Description:       Find missing images, broken attachment links, unused media files, and export broken media reports from your WordPress site.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Smackcoders
 * Author URI:        https://smackcoders.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       broken-media-finder
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPBMF_VERSION', '1.0.1' );
define( 'WPBMF_DB_VERSION', '1.0.1' );
define( 'WPBMF_PLUGIN_FILE', __FILE__ );
define( 'WPBMF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPBMF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPBMF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WPBMF_PLUGIN_DIR . 'vendor/autoload.php';

use Smackcoders\BrokenMediaFinder\Core\Activator;
use Smackcoders\BrokenMediaFinder\Core\Deactivator;
use Smackcoders\BrokenMediaFinder\Core\Plugin;

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'wpbmf', 'Smackcoders\BrokenMediaFinder\Cli\CliCommands' );
}

function wpbmf_run() {
	Plugin::get_instance()->run();
}

add_action( 'plugins_loaded', 'wpbmf_load_text_domain' );
function wpbmf_load_text_domain() {
	load_plugin_textdomain( 'broken-media-finder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

wpbmf_run();
