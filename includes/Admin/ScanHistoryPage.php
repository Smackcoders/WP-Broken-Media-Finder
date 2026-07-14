<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

if (!defined('ABSPATH')) {
	exit;
}

class ScanHistoryPage
{

	public function render()
	{
		$this->handle_bulk_delete();
		$history = get_option('wpbmf_scan_history', array());
		$nonce = isset($_GET['wpbmf_history_nonce']) ? sanitize_text_field(wp_unslash($_GET['wpbmf_history_nonce'])) : '';
		$verified = wp_verify_nonce($nonce, 'wpbmf_history_view');
		$scan_id = ($verified && isset($_GET['scan_id'])) ? sanitize_text_field(wp_unslash($_GET['scan_id'])) : '';

		if (!empty($scan_id)) {
			$this->render_results($scan_id, $history);
			return;
		}

		$list_table = new ScanHistoryListTable($history);
		$list_table->prepare_items();
		$total_items = count($history);

		?>
		<div class="wrap wpbmf-wrap">
			<h1><?php esc_html_e('Scan History', 'broken-media-finder'); ?></h1>

			<?php if (empty($history)): ?>
				<div class="wpbmf-empty-state">
					<p><?php esc_html_e('No scan history found. Run a new scan from the dashboard.', 'broken-media-finder'); ?>
					</p>
					<p><a href="<?php echo esc_url(admin_url('admin.php?page=wpbmf-dashboard')); ?>"
							class="button button-primary"><?php esc_html_e('Go to Dashboard', 'broken-media-finder'); ?></a></p>
				</div>
			<?php else: ?>
				<p class="wpbmf-count"><?php
				/* translators: %d: Number of scans. */
				printf(esc_html(_n('%d scan recorded', '%d scans recorded', $total_items, 'broken-media-finder')), (int) $total_items);
				?></p>

				<form method="post" id="wpbmf-history-form">
					<input type="hidden" name="page" value="wpbmf-history" />
					<?php $list_table->display(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}


	private function handle_bulk_delete()
	{
		$action = (isset($_POST['action']) && $_POST['action'] !== '-1') ? sanitize_text_field(wp_unslash($_POST['action'])) : (isset($_POST['action2']) && $_POST['action2'] !== '-1' ? sanitize_text_field(wp_unslash($_POST['action2'])) : '');
		if ('delete' !== $action) {
			return;
		}
		
		$nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'bulk-wpbmf_scan_histories')) {
			return;
		}
		$scan_ids = isset($_POST['wpbmf_scan_ids']) ? array_map('sanitize_text_field', wp_unslash((array) $_POST['wpbmf_scan_ids'])) : array();
		if (empty($scan_ids)) {
			return;
		}
		$repo = new \Smackcoders\BrokenMediaFinder\Scanner\ScanRepository();
		$history = get_option('wpbmf_scan_history', array());
		foreach ($scan_ids as $scan_id) {
			$repo->delete_results_by_scan_id($scan_id);
			$history = array_filter($history, function ($h) use ($scan_id) {
				return ($h['scan_id'] ?? '') !== $scan_id;
			});
		}
		update_option('wpbmf_scan_history', array_values($history));
	}

	private function render_results($scan_id, $history)
	{
		$repo = new \Smackcoders\BrokenMediaFinder\Scanner\ScanRepository();
		$back_url = admin_url('admin.php?page=wpbmf-history');

		$nonce = isset($_GET['wpbmf_history_nonce']) ? sanitize_text_field(wp_unslash($_GET['wpbmf_history_nonce'])) : '';
		$verified = wp_verify_nonce($nonce, 'wpbmf_history_view');
		$issue_type = ($verified && isset($_GET['issue_type'])) ? sanitize_text_field(wp_unslash($_GET['issue_type'])) : '';
		$issue_status = ($verified && isset($_GET['issue_status'])) ? sanitize_text_field(wp_unslash($_GET['issue_status'])) : '';
		$severity = ($verified && isset($_GET['severity'])) ? sanitize_text_field(wp_unslash($_GET['severity'])) : '';
		$search = ($verified && isset($_GET['search'])) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
		$paged = ($verified && isset($_GET['paged'])) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
		$per_page = 20;

		$scan_label = '';
		foreach ($history as $h) {
			if (isset($h['scan_id']) && $h['scan_id'] === $scan_id) {
				$scan_label = $h['scanned_at'] ?? '';
				break;
			}
		}

		$filters = compact('issue_type', 'issue_status', 'severity', 'search', 'scan_id');
		$list_table = new ScanResultsListTable($filters, true);
		$list_table->prepare_items();
		$total = $list_table->get_total_issues_count();

		$issue_type_labels = array(
			'missing_image' => __('Missing Image', 'broken-media-finder'),
			'broken_image' => __('Broken Image', 'broken-media-finder'),
			'missing_featured_image' => __('Missing Featured', 'broken-media-finder'),
			'unused_media' => __('Unused Media', 'broken-media-finder'),
		);
		$status_labels = array(
			'open' => __('Open', 'broken-media-finder'),
			'ignored' => __('Ignored', 'broken-media-finder'),
			'fixed' => __('Fixed', 'broken-media-finder'),
			'placeholder_applied' => __('Placeholder Applied', 'broken-media-finder'),
		);
		$severity_labels = array(
			'high' => __('High', 'broken-media-finder'),
			'medium' => __('Medium', 'broken-media-finder'),
			'low' => __('Low', 'broken-media-finder'),
		);
		?>
		<div class="wrap wpbmf-wrap">
			<h1>
				<a href="<?php echo esc_url($back_url); ?>" class="button button-secondary" style="margin-right:12px;">&#8592;
					<?php esc_html_e('Back to History', 'broken-media-finder'); ?></a>
				<?php
				if ($scan_label) {
					/* translators: %s: Scan date. */
					printf(esc_html__('Scan Results — %s', 'broken-media-finder'), esc_html($scan_label));
				} else {
					esc_html_e('Scan Results', 'broken-media-finder');
				}
				?>
			</h1>

			<div class="wpbmf-toolbar">
				<form method="get" action="" class="wpbmf-filter-form">
					<input type="hidden" name="page" value="wpbmf-history">
					<input type="hidden" name="scan_id" value="<?php echo esc_attr($scan_id); ?>">
					<input type="hidden" name="wpbmf_history_nonce"
						value="<?php echo esc_attr(wp_create_nonce('wpbmf_history_view')); ?>">
					<select name="issue_type">
						<option value=""><?php esc_html_e('All Types', 'broken-media-finder'); ?></option>
						<?php foreach ($issue_type_labels as $val => $label): ?>
							<option value="<?php echo esc_attr($val); ?>" <?php selected($issue_type, $val); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="issue_status">
						<option value=""><?php esc_html_e('All Statuses', 'broken-media-finder'); ?></option>
						<?php foreach ($status_labels as $val => $label): ?>
							<option value="<?php echo esc_attr($val); ?>" <?php selected($issue_status, $val); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="severity">
						<option value=""><?php esc_html_e('All Severities', 'broken-media-finder'); ?></option>
						<?php foreach ($severity_labels as $val => $label): ?>
							<option value="<?php echo esc_attr($val); ?>" <?php selected($severity, $val); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="search" name="search" value="<?php echo esc_attr($search); ?>"
						placeholder="<?php esc_attr_e('Search URL or message...', 'broken-media-finder'); ?>">
					<input type="submit" class="button" value="<?php esc_attr_e('Filter', 'broken-media-finder'); ?>">
					<a href="<?php echo esc_url(add_query_arg(array('page' => 'wpbmf-history', 'scan_id' => rawurlencode($scan_id), 'wpbmf_history_nonce' => wp_create_nonce('wpbmf_history_view')), admin_url('admin.php'))); ?>"
						class="button">
						<?php esc_html_e('Reset', 'broken-media-finder'); ?>
					</a>
				</form>
			</div>

			<?php if ($total === 0): ?>
				<div class="wpbmf-empty-state">
					<p><?php esc_html_e('No issues found for this filter.', 'broken-media-finder'); ?></p>
				</div>
			<?php else: ?>
				<p class="wpbmf-count"><?php
				/* translators: %d: Number of issues. */
				printf(esc_html(_n('%d issue found', '%d issues found', $total, 'broken-media-finder')), (int) $total);
				?></p>
				<form id="wpbmf-results-filter" method="get">
					<input type="hidden" name="page" value="wpbmf-history" />
					<input type="hidden" name="scan_id" value="<?php echo esc_attr($scan_id); ?>" />
					<?php $list_table->display(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private function badge($type, $value)
	{
		$map = array(
			'issue_type' => array(
				'missing_image' => array('red', __('Missing Image', 'broken-media-finder')),
				'broken_image' => array('orange', __('Broken Image', 'broken-media-finder')),
				'missing_featured_image' => array('red', __('Missing Featured', 'broken-media-finder')),
				'unused_media' => array('grey', __('Unused Media', 'broken-media-finder')),
			),
			'severity' => array(
				'high' => array('red', __('High', 'broken-media-finder')),
				'medium' => array('orange', __('Medium', 'broken-media-finder')),
				'low' => array('grey', __('Low', 'broken-media-finder')),
			),
			'status' => array(
				'open' => array('blue', __('Open', 'broken-media-finder')),
				'ignored' => array('grey', __('Ignored', 'broken-media-finder')),
				'fixed' => array('green', __('Fixed', 'broken-media-finder')),
				'placeholder_applied' => array('green', __('Placeholder Applied', 'broken-media-finder')),
			),
		);
		$entry = $map[$type][$value] ?? array('grey', esc_html($value));
		return '<span class="wpbmf-badge wpbmf-badge-' . esc_attr($entry[0]) . '">' . esc_html($entry[1]) . '</span>';
	}
}
