<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class UnusedMediaScanner
{

	private $extractor;
	private $repository;

	public function __construct(UrlExtractor $extractor, ScanRepository $repository)
	{
		$this->extractor = $extractor;
		$this->repository = $repository;
	}

	public function scan($scan_id, $settings = array())
	{
		$found = 0;
		$used_ids = $this->get_used_attachment_ids($settings);

		$per_page = 100;
		$paged = 1;

		do {
			$query = new \WP_Query(array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'posts_per_page' => $per_page,
				'paged' => $paged,
				'no_found_rows' => false,
			));

			if (!$query->have_posts()) {
				break;
			}

			$exclusions = apply_filters('wpbmf_unused_media_exclusions', array());

			foreach ($query->posts as $attachment) {
				if (in_array($attachment->ID, $used_ids, true)) {
					continue;
				}
				if (in_array($attachment->ID, $exclusions, true)) {
					continue;
				}

				// Skip if attached to a parent post and setting excludes them
				if (empty($settings['include_attached_in_unused']) && $attachment->post_parent) {
					continue;
				}

				$url = wp_get_attachment_url($attachment->ID);
				$path = get_attached_file($attachment->ID);

				$this->repository->insert_result(array(
					'scan_id' => $scan_id,
					'item_type' => 'unused_media',
					'issue_type' => 'unused_media',
					'attachment_id' => $attachment->ID,
					'source_url' => $url ?: '',
					'file_path' => $path ?: '',
					'message' => sprintf(
						/* translators: 1: Attachment title, 2: Attachment ID. */
						__('Attachment "%1$s" (#%2$d) appears unused.', 'broken-media-finder'),
						$attachment->post_title ?: $attachment->post_name,
						$attachment->ID
					),
					'severity' => 'low',
				));
				$found++;
			}

			$paged++;
		} while ($paged <= $query->max_num_pages);

		return $found;
	}

	private function get_used_attachment_ids($settings)
	{
		global $wpdb;

		$used_ids = array();

		// Featured images
		$thumbnail_posts = get_posts(array(
			'post_type' => 'any',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
		));
		foreach ($thumbnail_posts as $post_id) {
			$thumb_id = (int) get_post_thumbnail_id($post_id);
			if ($thumb_id) {
				$used_ids[] = $thumb_id;
			}
		}
		// URLs in post content
		$post_types = apply_filters('wpbmf_supported_post_types', array('post', 'page'));
		$posts = array();
		$batch_page = 1;
		$batch_size = 50;
		do {
			$batch = get_posts(array(
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => $batch_size,
				'paged' => $batch_page,
				'fields' => 'all',
			));
			if (empty($batch)) {
				break;
			}
			$posts = array_merge($posts, $batch);
			$batch_page++;
		} while (count($batch) === $batch_size);

		$all_used_urls = array();
		foreach ($posts as $post) {
			if (empty($post->post_content)) {
				continue;
			}
			$urls = $this->extractor->extract_image_urls($post->post_content);
			foreach ($urls as $url) {
				$all_used_urls[] = $url;
			}
			$links = $this->extractor->extract_upload_links($post->post_content);
			foreach ($links as $url) {
				$all_used_urls[] = $url;
			}
		}

		// Map URLs to attachment IDs — single DB query instead of one per URL
		$unique_urls = array_unique($all_used_urls);
		$upload_dir = wp_upload_dir();
		$base_upload = trailingslashit($upload_dir['baseurl']);
		$relative_paths = array();

		foreach ($unique_urls as $url) {
			$clean = preg_replace('/-\d+x\d+(\.[a-z]{2,5})$/i', '$1', $url);
			if (strpos($clean, $base_upload) === 0) {
				$relative_paths[] = str_replace($base_upload, '', $clean);
			}
			if ($url !== $clean && strpos($url, $base_upload) === 0) {
				$relative_paths[] = str_replace($base_upload, '', $url);
			}
		}

		// if (!empty($unique_urls)) {
		// 	foreach ($unique_urls as $url) {
		// 		$id = attachment_url_to_postid($url);
		// 		if ($id) {
		// 			$used_ids[] = (int) $id;
		// 		}
		// 	}
		// }

		return array_unique($used_ids);
	}
}
