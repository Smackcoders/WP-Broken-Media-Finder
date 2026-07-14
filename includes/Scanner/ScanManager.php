<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class ScanManager
{

	private $repository;
	private $extractor;

	public function __construct()
	{
		$this->repository = new ScanRepository();
		$this->extractor = new UrlExtractor();
	}

	public function run_scan($args = array())
	{
		$settings = get_option('wpbmf_settings', array());

		if (isset($settings['enable_plugin']) && '1' !== $settings['enable_plugin']) {
			return array('error' => __('Plugin is disabled in settings. Please enable it to run a scan.', 'broken-media-finder'));
		}

		$scan_id = $this->generate_scan_id();
		$post_types = apply_filters('wpbmf_supported_post_types', $settings['post_types'] ?? array('post', 'page'));

		do_action('wpbmf_before_scan_started', $scan_id, $args);

		if (empty($settings['keep_previous_results'])) {
			$this->repository->clear_all_results();
			delete_option('wpbmf_scan_history');
		}

		// Save scan_id so AJAX steps can continue with same ID
		update_option('wpbmf_current_scan_id', $scan_id);
		update_option('wpbmf_current_scan_settings', $settings);
		update_option('wpbmf_current_scan_post_types', $post_types);

		return array('scan_id' => $scan_id, 'started' => true);
	}

	public function run_scan_step($scan_id, $step)
	{
		$settings = get_option('wpbmf_current_scan_settings', array());
		$post_types = get_option('wpbmf_current_scan_post_types', array('post', 'page'));

		$scan_types = apply_filters('wpbmf_enabled_scan_types', array(
			'content_images' => isset($settings['scan_content_images']) ? !empty($settings['scan_content_images']) : true,
			'attachment_links' => isset($settings['scan_attachment_links']) ? !empty($settings['scan_attachment_links']) : true,
			'featured_images' => isset($settings['scan_featured_images']) ? !empty($settings['scan_featured_images']) : true,
			'unused_media' => isset($settings['scan_unused_media']) ? !empty($settings['scan_unused_media']) : false,
		));

		$count = 0;

		try {
			if ('content_images' === $step && !empty($scan_types['content_images'])) {
				$count = (new ContentScanner($this->extractor, $this->repository))->scan($scan_id, $post_types);
			} elseif ('attachment_links' === $step && !empty($scan_types['attachment_links'])) {
				$count = (new AttachmentScanner($this->extractor, $this->repository))->scan($scan_id, $post_types);
			} elseif ('featured_images' === $step && !empty($scan_types['featured_images'])) {
				$count = (new FeaturedImageScanner($this->repository))->scan($scan_id, $post_types);
			} elseif ('unused_media' === $step && !empty($scan_types['unused_media'])) {
				// set_time_limit(300);
				$count = (new UnusedMediaScanner($this->extractor, $this->repository))->scan($scan_id, $settings);
			}
		} catch (\Exception $e) {
			do_action('wpbmf_scan_failed', $scan_id, $e->getMessage());
			return array('error' => $e->getMessage());
		}

		return array('scan_id' => $scan_id, 'step' => $step, 'count' => $count, 'done' => false);
	}

	public function finish_scan($scan_id)
	{
		$summary = $this->repository->get_scan_summary($scan_id);
		$summary['scan_id'] = $scan_id;
		$summary['scanned_at'] = current_time('mysql');

		$this->save_scan_history($summary);

		delete_option('wpbmf_current_scan_id');
		delete_option('wpbmf_current_scan_settings');
		delete_option('wpbmf_current_scan_post_types');

		do_action('wpbmf_after_scan_completed', $scan_id, $summary);

		return array_merge($summary, array('done' => true));
	}

	public function generate_scan_id()
	{
		return 'scan_' . time() . '_' . wp_generate_password(8, false);
	}

	private function save_scan_history($summary)
	{
		$history = get_option('wpbmf_scan_history', array());
		array_unshift($history, $summary);

		// If history exceeds 100, delete DB results for the oldest scans being dropped.
		if (count($history) > 100) {
			$dropped = array_slice($history, 100);
			foreach ($dropped as $old) {
				if (!empty($old['scan_id'])) {
					$this->repository->delete_results_by_scan_id($old['scan_id']);
				}
			}
		}

		$history = array_slice($history, 0, 100);
		update_option('wpbmf_scan_history', $history);
	}
}
