<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if (!defined('ABSPATH')) {
	exit;
}

class DashboardPage
{
	private function render_badge($type, $value)
	{
		echo wp_kses_post($this->badge($type, $value));
	}

	private function badge($type, $value)
	{
		$map = array(
			'issue_type' => array(
				'missing_image' => array('red', __('Missing Image', 'broken-media-finder')),
				'broken_image' => array('orange', __('Broken Image', 'broken-media-finder')),
				'broken_attachment_url' => array('orange', __('Broken Link', 'broken-media-finder')),
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
		$allowed_html = array(
			'span' => array(
				'class' => array(),
			),
		);
		return wp_kses('<span class="wpbmf-badge wpbmf-badge-' . esc_attr($entry[0]) . '">' . esc_html($entry[1]) . '</span>', $allowed_html);
	}

	public function render()
	{
		$repository = new ScanRepository();
		$latest_scan = $repository->get_latest_scan_id();
		$summary = $latest_scan ? $repository->get_scan_summary($latest_scan) : array('total' => 0, 'missing_image' => 0, 'broken_image' => 0, 'broken_attachment_url' => 0, 'missing_featured_image' => 0, 'unused_media' => 0);
		$history = get_option('wpbmf_scan_history', array());

		$notice_nonce = isset($_GET['wpbmf_notice_nonce']) ? sanitize_text_field(wp_unslash($_GET['wpbmf_notice_nonce'])) : '';
		$show_notice = wp_verify_nonce($notice_nonce, 'wpbmf_dashboard_notice');
		$msg = ($show_notice && isset($_GET['wpbmf_msg'])) ? sanitize_text_field(wp_unslash($_GET['wpbmf_msg'])) : '';
		$total_msg = ($show_notice && isset($_GET['total'])) ? absint(wp_unslash($_GET['total'])) : 0;

		$export_url = wp_nonce_url(add_query_arg(array('page' => 'wpbmf-dashboard', 'wpbmf_export_csv' => '1'), admin_url('admin.php')), 'wpbmf_export_csv');

		// Results table logic
		$issue_type = isset($_GET['issue_type']) ? sanitize_text_field(wp_unslash($_GET['issue_type'])) : '';
		$issue_status = isset($_GET['issue_status']) ? sanitize_text_field(wp_unslash($_GET['issue_status'])) : '';
		$severity = isset($_GET['severity']) ? sanitize_text_field(wp_unslash($_GET['severity'])) : '';
		$search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';


		$paged = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
		$per_page = 20;

		$scan_id = isset($_GET['scan_id']) ? sanitize_text_field(wp_unslash($_GET['scan_id'])) : '';

		$scan_valid = true;
		if (!empty($scan_id)) {
			$found_in_history = false;
			foreach ($history as $h) {
				if (isset($h['scan_id']) && $h['scan_id'] === $scan_id) {
					$found_in_history = true;
					break;
				}
			}
			if (!$found_in_history) {
				$count_for_id = $repository->count_results(array('scan_id' => $scan_id));
				if (0 === $count_for_id) {
					$scan_valid = false;
				}
			}
		} else {
			$scan_id = $latest_scan ?: '';
		}

		$filters = compact('issue_type', 'issue_status', 'severity', 'search', 'scan_id');
		$list_table = new ScanResultsListTable($filters, $scan_valid && !empty($scan_id));
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
			<h1><?php esc_html_e('Broken Media Finder', 'broken-media-finder'); ?></h1>

			<?php if ('scanned' === $msg): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php
					/* translators: %d: Number of issues found. */
					printf(esc_html(_n('Scan complete. %d issue found.', 'Scan complete. %d issues found.', $total_msg, 'broken-media-finder')), (int) $total_msg);
					?></p>
				</div>
			<?php elseif ('cleared' === $msg): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e('Scan results cleared.', 'broken-media-finder'); ?></p>
				</div>
			<?php elseif ('replaced' === $msg): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e('Image replaced with placeholder.', 'broken-media-finder'); ?></p>
				</div>
			<?php elseif (!empty($msg) && 'replaced' !== $msg && 'cleared' !== $msg && 'scanned' !== $msg): ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html(urldecode($msg)); ?></p>
				</div>
			<?php endif; ?>

			<?php if (!empty($msg)): ?>
				<script>
				(function () {
					if (!window.history || !window.history.replaceState) { return; }
					var url = new URL(window.location.href);
					url.searchParams.delete('wpbmf_msg');
					url.searchParams.delete('total');
					url.searchParams.delete('wpbmf_notice_nonce');
					window.history.replaceState({}, document.title, url.toString());
				})();
				</script>
			<?php endif; ?>

			<?php if (!$scan_valid && !empty($_GET['scan_id'])): ?>
				<div class="notice notice-error inline">
					<p><?php esc_html_e('Invalid scan ID or the requested scan results are no longer available.', 'broken-media-finder'); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="wpbmf-actions-bar">
				<form method="post" data-wpbmf-scan>
					<?php wp_nonce_field('wpbmf_run_scan', 'wpbmf_scan_nonce'); ?>
					<button type="submit" name="wpbmf_run_scan" value="1" class="button button-primary button-large">
						<span class="wpbmf-btn-icon">&#9654;</span>
						<span class="wpbmf-btn-text"><?php esc_html_e('Run New Scan', 'broken-media-finder'); ?></span>
					</button>
				</form>
				<div id="wpbmf-scan-progress" style="display:none; margin-top:12px; width:400px; max-width:100%;">
					<div style="background:#e0e0e0; border-radius:4px; height:18px; width:100%;">
						<div id="wpbmf-scan-progress-bar"
							style="background:#2271b1; height:18px; border-radius:4px; width:0%; transition:width 0.4s;"></div>
					</div>
					<p id="wpbmf-scan-progress-msg" style="margin:4px 0 0;"></p>
				</div>
				<a href="<?php echo esc_url($export_url); ?>"
					class="button button-secondary"><?php esc_html_e('Export CSV', 'broken-media-finder'); ?></a>
				<button type="button" class="button button-secondary"
					id="wpbmf-toggle-clear"><?php esc_html_e('Clear Results', 'broken-media-finder'); ?></button>
			</div>

			<div id="wpbmf-clear-panel" style="display:none;" class="wpbmf-clear-panel">
				<form method="post">
					<?php wp_nonce_field('wpbmf_clear_results', 'wpbmf_clear_nonce'); ?>
					<label><input type="checkbox" name="wpbmf_clear_history" value="1">
						<?php esc_html_e('Also clear scan history', 'broken-media-finder'); ?></label>
					<button type="submit" name="wpbmf_clear_results" value="1"
						class="button button-primary wpbmf-confirm-clear"><?php esc_html_e('Confirm — Delete All Results', 'broken-media-finder'); ?></button>
				</form>
			</div>

			<div class="wpbmf-summary-cards">
				<?php
				$cards = array(
					array('count' => $summary['total'], 'label' => __('Total Issues', 'broken-media-finder'), 'class' => 'wpbmf-card-total'),
					array('count' => $summary['missing_image'], 'label' => __('Missing Images', 'broken-media-finder'), 'class' => 'wpbmf-card-error'),
					array('count' => $summary['broken_image'] ?? 0, 'label' => __('Broken Images', 'broken-media-finder'), 'class' => 'wpbmf-card-warn'),
					array('count' => $summary['missing_featured_image'], 'label' => __('Missing Featured', 'broken-media-finder'), 'class' => 'wpbmf-card-error'),
					array('count' => $summary['unused_media'], 'label' => __('Unused Media', 'broken-media-finder'), 'class' => 'wpbmf-card-info'),
				);
				foreach ($cards as $card):
					?>
					<div class="wpbmf-card <?php echo esc_attr($card['class']); ?>">
						<span class="wpbmf-card-count"><?php echo (int) $card['count']; ?></span>
						<span class="wpbmf-card-label"><?php echo esc_html($card['label']); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ($latest_scan): ?>
				<p class="wpbmf-scan-meta"><?php
				$history_first = $history[0] ?? null;
				$scan_date = $history_first ? $history_first['scanned_at'] : '';
				if ($scan_date) {
					/* translators: %s: Last scan date. */
					printf(esc_html__('Last scan: %s', 'broken-media-finder'), '<strong>' . esc_html($scan_date) . '</strong>');
				}
				?></p>

				<h2 style="margin-top: 30px;"><?php esc_html_e('Detailed Results', 'broken-media-finder'); ?></h2>

				<div class="wpbmf-toolbar">
					<form method="get" action="" class="wpbmf-filter-form">
						<input type="hidden" name="page" value="wpbmf-dashboard">
						<?php if (!empty($scan_id) && $scan_valid): ?>
							<input type="hidden" name="scan_id" value="<?php echo esc_attr($scan_id); ?>">
						<?php endif; ?>
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
						<a href="<?php echo esc_url(admin_url('admin.php?page=wpbmf-dashboard')); ?>"
							class="button"><?php esc_html_e('Reset', 'broken-media-finder'); ?></a>
					</form>
				</div>

				<?php if ('unused_media' === $issue_type || empty($issue_type)): ?>
					<div class="wpbmf-unused-warning notice notice-warning inline" style="margin-bottom: 15px;">
						<p><?php esc_html_e('Note: Unused media detection may produce false positives. Review results carefully before deleting any files.', 'broken-media-finder'); ?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ($total === 0): ?>
					<div class="wpbmf-empty-state">
						<p><?php esc_html_e('No issues found for this filter.', 'broken-media-finder'); ?></p>
					</div>
				<?php else: ?>
					<form id="wpbmf-results-filter" method="get">
						<input type="hidden" name="page" value="wpbmf-dashboard" />
						<?php if (!empty($scan_id) && $scan_valid): ?>
							<input type="hidden" name="scan_id" value="<?php echo esc_attr($scan_id); ?>">
						<?php endif; ?>
						<?php $list_table->display(); ?>
					</form>
				<?php endif; ?>

			<?php else: ?>
				<div class="wpbmf-empty-state">
					<p><?php esc_html_e('No scan has been run yet. Click "Run New Scan" to get started.', 'broken-media-finder'); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
