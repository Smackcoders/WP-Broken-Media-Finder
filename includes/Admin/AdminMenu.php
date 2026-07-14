<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

use Smackcoders\BrokenMediaFinder\Export\CsvExporter;
use Smackcoders\BrokenMediaFinder\Repair\MediaReplacer;
use Smackcoders\BrokenMediaFinder\Scanner\ScanManager;
use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;
use Smackcoders\BrokenMediaFinder\Admin\HelpPage;

if (!defined('ABSPATH')) {
	exit;
}

class AdminMenu
{

	public function register()
	{
		add_action('admin_menu', array($this, 'add_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('admin_init', array($this, 'handle_scan'));
		add_action('admin_init', array($this, 'handle_export'));
		add_action('admin_init', array($this, 'handle_status_update'));
		add_action('admin_init', array($this, 'handle_replace'));
		add_action('admin_init', array($this, 'handle_clear'));
		add_action('admin_init', array($this, 'handle_delete_history'));
		add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);

		add_filter('manage_toplevel_page_wpbmf-dashboard_columns', array($this, 'get_results_columns'));
		add_filter('manage_broken-media-finder_page_wpbmf-history_columns', array($this, 'get_history_columns'));
		add_filter('screen_settings', array($this, 'add_view_mode_screen_options'), 10, 2);

		add_action('wp_ajax_wpbmf_start_scan', array($this, 'ajax_start_scan'));
		add_action('wp_ajax_wpbmf_scan_step', array($this, 'ajax_scan_step'));
		add_action('wp_ajax_wpbmf_finish_scan', array($this, 'ajax_finish_scan'));
		add_action('wp_ajax_wpbmf_replace_placeholder', array($this, 'ajax_replace_placeholder'));
		add_action('wp_ajax_wpbmf_submit_pro_request', array($this, 'ajax_submit_pro_request'));

		(new SettingsPage())->register();
	}

	public function add_menu()
	{
		$dashboard_hook = add_menu_page(
			__('Broken Media Finder', 'broken-media-finder'),
			__('Broken Media Finder', 'broken-media-finder'),
			'manage_options',
			'wpbmf-dashboard',
			array($this, 'render_dashboard'),
			'dashicons-search',
			80
		);

		add_submenu_page(
			'wpbmf-dashboard',
			__('Dashboard', 'broken-media-finder'),
			__('Dashboard', 'broken-media-finder'),
			'manage_options',
			'wpbmf-dashboard',
			array($this, 'render_dashboard')
		);

		$history_hook = add_submenu_page(
			'wpbmf-dashboard',
			__('Scan History', 'broken-media-finder'),
			__('Scan History', 'broken-media-finder'),
			'manage_options',
			'wpbmf-history',
			array($this, 'render_history')
		);

		add_submenu_page(
			'wpbmf-dashboard',
			__('Settings', 'broken-media-finder'),
			__('Settings', 'broken-media-finder'),
			'manage_options',
			'wpbmf-settings',
			array($this, 'render_settings')
		);

		add_submenu_page(
			'wpbmf-dashboard',
			__('Help & Support', 'broken-media-finder'),
			__('Help & Support', 'broken-media-finder'),
			'manage_options',
			'wpbmf-help',
			array($this, 'render_help')
		);

		add_action("load-$dashboard_hook", array($this, 'add_dashboard_screen_options'));
		add_action("load-$history_hook", array($this, 'add_history_screen_options'));
	}

	public function add_dashboard_screen_options()
	{
		add_screen_option('per_page', array(
			'label' => __('Number of items per page:', 'broken-media-finder'),
			'default' => 20,
			'option' => 'wpbmf_results_per_page',
		));
	}

	public function add_history_screen_options()
	{
		add_screen_option('per_page', array(
			'label' => __('Number of items per page:', 'broken-media-finder'),
			'default' => 10,
			'option' => 'wpbmf_history_per_page',
		));
	}

	public function get_results_columns()
	{
		return array(
			'issue_type' => __('Type', 'broken-media-finder'),
			'severity' => __('Severity', 'broken-media-finder'),
			'issue_status' => __('Status', 'broken-media-finder'),
			'source_url' => __('Source URL', 'broken-media-finder'),
			'post_id' => __('Post', 'broken-media-finder'),
			'error_type' => __('Error Type', 'broken-media-finder'),
			'created_at' => __('Scan Date', 'broken-media-finder'),
			'actions' => __('Actions', 'broken-media-finder'),
		);
	}

	public function get_history_columns()
	{
		return array(
			'cb' => '<input type="checkbox" />',
			'scanned_at' => __('Date & Time', 'broken-media-finder'),
			'scan_id' => __('Scan ID', 'broken-media-finder'),
			'total' => __('Total Issues', 'broken-media-finder'),
			'missing_image' => __('Missing Images', 'broken-media-finder'),
			'broken_image' => __('Broken Images', 'broken-media-finder'),
			'missing_featured_image' => __('Missing Featured', 'broken-media-finder'),
			'unused_media' => __('Unused Media', 'broken-media-finder'),
			'actions' => __('Actions', 'broken-media-finder'),
		);
	}

	public function add_view_mode_screen_options($settings, $screen)
	{
		if (!in_array($screen->id, array('toplevel_page_wpbmf-dashboard', 'broken-media-finder_page_wpbmf-history'), true)) {
			return $settings;
		}

		$mode = get_user_setting('wpbmf_list_mode', 'list');

		ob_start();
		?>
		<fieldset class="metabox-prefs view-mode">
			<legend><?php _e('View mode'); ?></legend>
			<label for="list-view-mode">
				<input id="list-view-mode" type="radio" name="wpbmf_mode" value="list" <?php checked('list', $mode); ?> />
				<?php _e('Compact view'); ?>
			</label>
			<label for="excerpt-view-mode">
				<input id="excerpt-view-mode" type="radio" name="wpbmf_mode" value="excerpt" <?php checked('excerpt', $mode); ?> />
				<?php _e('Extended view'); ?>
			</label>
		</fieldset>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var radios = document.querySelectorAll('input[name="wpbmf_mode"]');
				radios.forEach(function (radio) {
					radio.addEventListener('change', function () {
						document.cookie = 'wp-settings-' + ajaxurl.split('/wp-admin/')[0] + 'wpbmf_list_mode=' + this.value + '; path=/';
						if (typeof setUserSetting !== 'undefined') {
							setUserSetting('wpbmf_list_mode', this.value);
						}
						// Optional: submit the form to refresh the page
						document.getElementById('adv-settings').submit();
					});
				});
			});
		</script>
		<?php
		$custom_settings = ob_start();
		ob_end_clean();

		// Due to ob_start nesting
		$settings .= ob_get_clean();

		return $settings;
	}

	public function render_dashboard()
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied.', 'broken-media-finder'));
		}
		(new DashboardPage())->render();
	}
	public function render_history()
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied.', 'broken-media-finder'));
		}
		(new ScanHistoryPage())->render();
	}
	public function render_settings()
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied.', 'broken-media-finder'));
		}
		(new SettingsPage())->render();
	}

	public function render_help()
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied.', 'broken-media-finder'));
		}
		(new HelpPage())->render();
	}

	public function enqueue_assets($hook)
	{
		if (false === strpos($hook, 'wpbmf') && $hook !== 'upload.php') {
			return;
		}

		wp_enqueue_style('wpbmf-admin', WPBMF_PLUGIN_URL . 'assets/css/admin.css', array(), WPBMF_VERSION);
		wp_enqueue_media();
		wp_enqueue_script('wpbmf-admin', WPBMF_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WPBMF_VERSION, true);
		wp_localize_script('wpbmf-admin', 'wpbmf_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wpbmf_admin_action'),
			'batch_nonce' => wp_create_nonce('wpbmf_batch_scan'),
			'dashboard_url' => wp_nonce_url(admin_url('admin.php?page=wpbmf-dashboard'), 'wpbmf_dashboard_notice', 'wpbmf_notice_nonce'),
			'confirm_clear' => __('Delete all scan results? This cannot be undone.', 'broken-media-finder'),
			'confirm_replace' => __('Replace this image with the placeholder? This modifies post content.', 'broken-media-finder'),
			'confirm_history' => __('Clear scan history?', 'broken-media-finder'),
		));
	}

	public function handle_scan()
	{
		// Legacy form POST — kept for non-JS fallback but AJAX path is preferred
		if (empty($_POST['wpbmf_run_scan'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_POST['wpbmf_scan_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpbmf_scan_nonce'])), 'wpbmf_run_scan')) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		// JS is present — redirect to dashboard; AJAX will handle actual scanning
		$redirect = wp_nonce_url(add_query_arg(array('page' => 'wpbmf-dashboard', 'wpbmf_msg' => 'scanning'), admin_url('admin.php')), 'wpbmf_dashboard_notice', 'wpbmf_notice_nonce');
		wp_safe_redirect($redirect);
		exit;
	}

	public function ajax_start_scan()
	{
		check_ajax_referer('wpbmf_batch_scan', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied.'));
		}
		$manager = new ScanManager();
		$result = $manager->run_scan();
		wp_send_json_success($result);
	}

	public function ajax_scan_step()
	{
		check_ajax_referer('wpbmf_batch_scan', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied.'));
		}
		$scan_id = isset($_POST['scan_id']) ? sanitize_text_field(wp_unslash($_POST['scan_id'])) : '';
		$step = isset($_POST['step']) ? sanitize_text_field(wp_unslash($_POST['step'])) : '';
		if (!$scan_id || !$step) {
			wp_send_json_error(array('message' => 'Missing parameters.'));
		}
		$manager = new ScanManager();
		$result = $manager->run_scan_step($scan_id, $step);
		if (isset($result['error'])) {
			wp_send_json_error($result);
		}
		wp_send_json_success($result);
	}

	public function ajax_finish_scan()
	{
		check_ajax_referer('wpbmf_batch_scan', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied.'));
		}
		$scan_id = isset($_POST['scan_id']) ? sanitize_text_field(wp_unslash($_POST['scan_id'])) : '';
		if (!$scan_id) {
			wp_send_json_error(array('message' => 'Missing scan_id.'));
		}
		$manager = new ScanManager();
		$result = $manager->finish_scan($scan_id);
		wp_send_json_success($result);
	}

	public function ajax_submit_pro_request()
	{
		check_ajax_referer('wpbmf_batch_scan', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied.'));
		}

		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
		$message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
		$feature = isset($_POST['feature']) ? sanitize_text_field(wp_unslash($_POST['feature'])) : '';

		if (empty($name) || empty($email) || empty($message)) {
			wp_send_json_error(array('message' => 'Please fill all required fields.'));
		}

		$to      = 'dummy@example.com';
		$subject = 'Broken Media Finder Pro Request: ' . $feature;
		$body    = "Name: $name\nEmail: $email\nFeature: $feature\n\nMessage:\n$message";
		$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email );

		wp_mail( $to, $subject, $body, $headers );

		wp_send_json_success( array( 'message' => 'Request submitted successfully!' ) );
	}

	public function handle_export()
	{
		if (empty($_GET['wpbmf_export_csv'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wpbmf_export_csv')) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		$filters = array(
			'issue_type' => isset($_GET['issue_type']) ? sanitize_text_field(wp_unslash($_GET['issue_type'])) : '',
			'issue_status' => isset($_GET['issue_status']) ? sanitize_text_field(wp_unslash($_GET['issue_status'])) : '',
			'search' => isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '',
		);
		(new CsvExporter())->export($filters);
	}

	public function handle_status_update()
	{
		if (empty($_POST['wpbmf_update_status'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'wpbmf_status_action')) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		$id = isset($_POST['result_id']) ? absint(wp_unslash($_POST['result_id'])) : 0;
		$status = sanitize_text_field(wp_unslash($_POST['wpbmf_update_status']));
		if ($id) {
			(new ScanRepository())->update_result_status($id, $status);
		}
		wp_safe_redirect(add_query_arg('page', 'wpbmf-dashboard', admin_url('admin.php')));
		exit;
	}

	public function handle_replace()
	{
		if (empty($_POST['wpbmf_replace_placeholder'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'wpbmf_replace_action')) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		$id = isset($_POST['result_id']) ? absint(wp_unslash($_POST['result_id'])) : 0;
		$result = $id ? (new MediaReplacer())->replace($id) : array('success' => false, 'message' => 'Invalid ID.');
		$msg = $result['success'] ? 'replaced' : urlencode($result['message'] ?? 'error');

		$redirect = add_query_arg(array(
			'page' => 'wpbmf-dashboard',
			'wpbmf_msg' => $msg,
			'wpbmf_notice_nonce' => wp_create_nonce('wpbmf_dashboard_notice')
		), admin_url('admin.php'));

		wp_safe_redirect($redirect);
		exit;
	}

	public function ajax_replace_placeholder()
	{
		check_ajax_referer('wpbmf_replace_action', 'nonce');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied.'));
		}
		$id = isset($_POST['result_id']) ? absint(wp_unslash($_POST['result_id'])) : 0;
		if (!$id) {
			wp_send_json_error(array('message' => 'Invalid ID.'));
		}

		$result = (new MediaReplacer())->replace($id);
		if ($result['success']) {
			wp_send_json_success($result);
		} else {
			wp_send_json_error($result);
		}
	}

	public function handle_clear()
	{
		if (empty($_POST['wpbmf_clear_results'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_POST['wpbmf_clear_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpbmf_clear_nonce'])), 'wpbmf_clear_results')) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		(new ScanRepository())->clear_all_results();
		if (!empty($_POST['wpbmf_clear_history'])) {
			delete_option('wpbmf_scan_history');
		}
		$redirect = wp_nonce_url(add_query_arg(array('page' => 'wpbmf-dashboard', 'wpbmf_msg' => 'cleared'), admin_url('admin.php')), 'wpbmf_dashboard_notice', 'wpbmf_notice_nonce');
		wp_safe_redirect($redirect);
		exit;
	}

	public function handle_delete_history()
	{
		if (empty($_GET['wpbmf_delete_scan'])) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		$scan_id = sanitize_text_field(wp_unslash($_GET['wpbmf_delete_scan']));
		
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wpbmf_delete_scan_' . $scan_id)) {
			wp_die(esc_html__('Security check failed.', 'broken-media-finder'));
		}
		
		if ($scan_id) {
			(new ScanRepository())->delete_results_by_scan_id($scan_id);
			$history = get_option('wpbmf_scan_history', array());
			foreach ($history as $key => $h) {
				if (isset($h['scan_id']) && $h['scan_id'] === $scan_id) {
					unset($history[$key]);
					break;
				}
			}
			update_option('wpbmf_scan_history', array_values($history));
		}
		wp_safe_redirect(add_query_arg('page', 'wpbmf-history', admin_url('admin.php')));
		exit;
	}

	public function set_screen_option($status, $option, $value)
	{
		if ('wpbmf_results_per_page' === $option || 'wpbmf_history_per_page' === $option) {
			return $value;
		}
		return $status;
	}
}
