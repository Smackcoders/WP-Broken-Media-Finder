<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if (!defined('ABSPATH')) {
	exit;
}

class ScanResultsPage
{

	private $repo;

	public function __construct()
	{
		$this->repo = new ScanRepository();
	}

	public function render()
	{
		$filter_nonce = isset($_GET['wpbmf_filter_nonce']) ? sanitize_text_field(wp_unslash($_GET['wpbmf_filter_nonce'])) : '';
		$has_filters = isset($_GET['issue_type']) || isset($_GET['issue_status']) || isset($_GET['severity']) || isset($_GET['search']);
		$use_filters = !$has_filters || wp_verify_nonce($filter_nonce, 'wpbmf_filter_results');
		$issue_type = ($use_filters && isset($_GET['issue_type'])) ? sanitize_text_field(wp_unslash($_GET['issue_type'])) : '';
		$issue_status = ($use_filters && isset($_GET['issue_status'])) ? sanitize_text_field(wp_unslash($_GET['issue_status'])) : '';
		$severity = ($use_filters && isset($_GET['severity'])) ? sanitize_text_field(wp_unslash($_GET['severity'])) : '';
		$search = ($use_filters && isset($_GET['search'])) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
		
		$scan_id = ($use_filters && isset($_GET['scan_id'])) ? sanitize_text_field(wp_unslash($_GET['scan_id'])) : '';
		$filters = compact('issue_type', 'issue_status', 'severity', 'search', 'scan_id');

		$list_table = new ScanResultsListTable($filters, true);
		$list_table->prepare_items();
		$total = $list_table->get_total_issues_count();

		$export_url = wp_nonce_url(add_query_arg(array_merge(array('page' => 'wpbmf-dashboard', 'wpbmf_export_csv' => '1'), $filters), admin_url('tools.php')), 'wpbmf_export_csv');
		$notice_nonce = isset($_GET['wpbmf_notice_nonce']) ? sanitize_text_field(wp_unslash($_GET['wpbmf_notice_nonce'])) : '';
		$msg = (wp_verify_nonce($notice_nonce, 'wpbmf_results_notice') && isset($_GET['wpbmf_msg'])) ? sanitize_text_field(wp_unslash($_GET['wpbmf_msg'])) : '';

		$issue_types = array('missing_image', 'broken_image', 'broken_attachment_url', 'missing_featured_image', 'unused_media');
		$statuses = array('open', 'ignored', 'fixed', 'placeholder_applied');
		$severities = array('high', 'medium', 'low');
		?>
		<div class="wrap wpbmf-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e('Scan Results', 'broken-media-finder'); ?></h1>
			<hr class="wp-header-end">

			<?php if ('replaced' === $msg): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e('Image replaced with placeholder.', 'broken-media-finder'); ?></p>
				</div>
			<?php elseif (!empty($msg) && 'replaced' !== $msg): ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html(urldecode($msg)); ?></p>
				</div>
			<?php endif; ?>

			<div class="wpbmf-toolbar">
				<form method="get" action="" class="wpbmf-filter-form">
					<input type="hidden" name="page" value="wpbmf-results">
					<?php if (!empty($scan_id)): ?>
						<input type="hidden" name="scan_id" value="<?php echo esc_attr($scan_id); ?>">
					<?php endif; ?>
					<?php wp_nonce_field('wpbmf_filter_results', 'wpbmf_filter_nonce'); ?>
					<select name="issue_type">
						<option value=""><?php esc_html_e('All Types', 'broken-media-finder'); ?></option>
						<?php foreach ($issue_types as $t): ?>
							<option value="<?php echo esc_attr($t); ?>" <?php selected($issue_type, $t); ?>>
								<?php echo esc_html(str_replace('_', ' ', ucfirst($t))); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="issue_status">
						<option value=""><?php esc_html_e('All Statuses', 'broken-media-finder'); ?></option>
						<?php foreach ($statuses as $s): ?>
							<option value="<?php echo esc_attr($s); ?>" <?php selected($issue_status, $s); ?>>
								<?php echo esc_html(str_replace('_', ' ', ucfirst($s))); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="severity">
						<option value=""><?php esc_html_e('All Severities', 'broken-media-finder'); ?></option>
						<?php foreach ($severities as $sv): ?>
							<option value="<?php echo esc_attr($sv); ?>" <?php selected($severity, $sv); ?>>
								<?php echo esc_html(ucfirst($sv)); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="search" name="search" value="<?php echo esc_attr($search); ?>"
						placeholder="<?php esc_attr_e('Search URL or message...', 'broken-media-finder'); ?>">
					<input type="submit" class="button" value="<?php esc_attr_e('Filter', 'broken-media-finder'); ?>">
					<a href="<?php echo esc_url(admin_url('tools.php?page=wpbmf-results')); ?>"
						class="button"><?php esc_html_e('Reset', 'broken-media-finder'); ?></a>
				</form>
				<div class="wpbmf-toolbar-actions">
					<a href="<?php echo esc_url($export_url); ?>"
						class="button button-secondary"><?php esc_html_e('Export CSV', 'broken-media-finder'); ?></a>
				</div>
			</div>

			<?php if ('unused_media' === $issue_type || empty($issue_type)): ?>
				<div class="wpbmf-unused-warning notice notice-warning inline">
					<p><?php esc_html_e('Note: Unused media detection may produce false positives. Review results carefully before deleting any files.', 'broken-media-finder'); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ($total === 0): ?>
				<div class="wpbmf-empty-state">
					<p><?php esc_html_e('No issues found. Run a scan from the dashboard.', 'broken-media-finder'); ?></p>
				</div>
			<?php else: ?>
				<form id="wpbmf-results-filter" method="get">
					<input type="hidden" name="page" value="wpbmf-results" />
					<?php $list_table->display(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}
