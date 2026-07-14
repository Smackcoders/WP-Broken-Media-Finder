<?php
namespace Smackcoders\BrokenMediaFinder\Cli;

use Smackcoders\BrokenMediaFinder\Export\CsvExporter;
use Smackcoders\BrokenMediaFinder\Scanner\ScanManager;
use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;
use WP_CLI;

if (!defined('ABSPATH')) {
	exit;
}

class CliCommands
{

	/**
	 * Run a media scan.
	 * ## EXAMPLES
	 *     wp wpbmf scan
	 */
	public function scan($args, $assoc_args)
	{
		WP_CLI::line('Starting scan…');
		$summary = (new ScanManager())->run_scan();
		WP_CLI::success('Scan complete. Total issues: ' . ($summary['total'] ?? 0));
		WP_CLI::line('  Missing images:   ' . ($summary['missing_image'] ?? 0));
		WP_CLI::line('  Broken images:    ' . ($summary['broken_image'] ?? 0));
		WP_CLI::line('  Broken links:     ' . ($summary['broken_attachment_url'] ?? 0));
		WP_CLI::line('  Missing featured: ' . ($summary['missing_featured_image'] ?? 0));
		WP_CLI::line('  Unused media:     ' . ($summary['unused_media'] ?? 0));
	}

	/**
	 * Show latest scan summary.
	 * ## EXAMPLES
	 *     wp wpbmf summary
	 */
	public function summary($args, $assoc_args)
	{
		$repo = new ScanRepository();
		$scan_id = $repo->get_latest_scan_id();
		if (!$scan_id) {
			WP_CLI::warning('No scan found. Run: wp wpbmf scan');
			return;
		}
		$s = $repo->get_scan_summary($scan_id);
		WP_CLI::line("Latest scan: {$scan_id}");
		WP_CLI::line("  Total:            {$s['total']}");
		WP_CLI::line("  Missing images:   {$s['missing_image']}");
		WP_CLI::line("  Broken images:    " . ($s['broken_image'] ?? 0));
		WP_CLI::line("  Broken links:     {$s['broken_attachment_url']}");
		WP_CLI::line("  Missing featured: {$s['missing_featured_image']}");
		WP_CLI::line("  Unused media:     {$s['unused_media']}");
	}

	/**
	 * List scan results.
	 * ## OPTIONS
	 * [--type=<issue_type>]
	 * [--limit=<n>]
	 * ## EXAMPLES
	 *     wp wpbmf results --type=missing_image
	 */
	public function results($args, $assoc_args)
	{
		$repo = new ScanRepository();
		$results = $repo->get_results(array(
			'issue_type' => $assoc_args['type'] ?? '',
			'limit' => absint($assoc_args['limit'] ?? 20),
		));

		if (empty($results)) {
			WP_CLI::line('No results.');
			return;
		}

		foreach ($results as $r) {
			WP_CLI::line("[{$r['id']}] {$r['issue_type']} | {$r['severity']} | {$r['issue_status']} | " . mb_strimwidth($r['source_url'] ?? '', 0, 80, '…'));
		}
	}

	/**
	 * Export scan results to CSV.
	 * ## OPTIONS
	 * [--file=<path>]
	 * ## EXAMPLES
	 *     wp wpbmf export --file=/tmp/report.csv
	 */
	public function export($args, $assoc_args)
	{
		$repo = new ScanRepository();
		$rows = $repo->get_export_rows();

		if (empty($rows)) {
			WP_CLI::warning('No results to export.');
			return;
		}

		$file = $assoc_args['file'] ?? (getcwd() . '/broken-media-report-' . gmdate('Y-m-d') . '.csv');
		$csv_lines = array();
		$csv_lines[] = implode(',', array('ID', 'Issue Type', 'Severity', 'Status', 'Post ID', 'Source URL', 'Message'));
		foreach ($rows as $r) {
			$csv_lines[] = implode(',', array_map(
				function ($val) {
					$val = str_replace('"', '""', (string) $val);
					return '"' . $val . '"';
				},
				array($r['id'], $r['issue_type'], $r['severity'], $r['issue_status'], $r['post_id'], $r['source_url'], $r['message'])
			));
		}
		$csv_content = implode("\n", $csv_lines);

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;
		if (!$wp_filesystem->put_contents($file, $csv_content, FS_CHMOD_FILE)) {
			WP_CLI::error("Cannot write: {$file}");
		}
		WP_CLI::success('Exported ' . count($rows) . " results to {$file}");
	}

	/**
	 * Clear all scan results.
	 * ## OPTIONS
	 * [--yes]
	 * ## EXAMPLES
	 *     wp wpbmf clear --yes
	 */
	public function clear($args, $assoc_args)
	{
		WP_CLI::confirm('Delete ALL scan results?', $assoc_args);
		$count = (new ScanRepository())->clear_all_results();
		WP_CLI::success("Deleted {$count} results.");
	}
}
