<?php
namespace Smackcoders\BrokenMediaFinder\Export;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if (!defined('ABSPATH')) {
	exit;
}

class CsvExporter
{

	private $repository;

	public function __construct()
	{
		$this->repository = new ScanRepository();
	}

	public function export(array $filters = array())
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to export.', 'broken-media-finder'));
		}

		$rows = $this->repository->get_export_rows($filters);
		$columns = apply_filters('wpbmf_csv_export_columns', array(
			'id' => __('Result ID', 'broken-media-finder'),
			'scan_id' => __('Scan ID', 'broken-media-finder'),
			'issue_type' => __('Issue Type', 'broken-media-finder'),
			'item_type' => __('Item Type', 'broken-media-finder'),
			'issue_status' => __('Status', 'broken-media-finder'),
			'severity' => __('Severity', 'broken-media-finder'),
			'post_id' => __('Post ID', 'broken-media-finder'),
			'_post_title' => __('Post Title', 'broken-media-finder'),
			'attachment_id' => __('Attachment ID', 'broken-media-finder'),
			'_attach_title' => __('Attachment Title', 'broken-media-finder'),
			'source_url' => __('Source URL', 'broken-media-finder'),
			'resolved_url' => __('Resolved URL', 'broken-media-finder'),
			'file_path' => __('File Path', 'broken-media-finder'),
			'message' => __('Message', 'broken-media-finder'),
			'created_at' => __('Created At', 'broken-media-finder'),
			'created_at_gmt' => __('Created At GMT', 'broken-media-finder'),
		));

		$date = gmdate('Y-m-d');
		$filename = apply_filters('wpbmf_csv_export_filename', "wp-broken-media-report-{$date}.csv");

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');

		$csv_output = fopen('php://output', 'w');
		fputcsv($csv_output, array_values($columns));

		foreach ($rows as $row) {
			$line = array();
			foreach (array_keys($columns) as $col) {
				if ('_post_title' === $col) {
					$val = !empty($row['post_id']) ? get_the_title((int) $row['post_id']) : '';
				} elseif ('_attach_title' === $col) {
					$val = !empty($row['attachment_id']) ? get_the_title((int) $row['attachment_id']) : '';
				} else {
					$val = $row[$col] ?? '';
				}
				$line[] = $this->sanitize_csv_value((string) $val);
			}
			fputcsv($csv_output, $line);
		}

		exit;
	}

	private function sanitize_csv_value($value)
	{
		$value = str_replace('"', '""', $value);
		if (in_array(substr($value, 0, 1), array('=', '+', '-', '@'), true)) {
			$value = "'" . $value;
		}
		return $value;
	}
}
