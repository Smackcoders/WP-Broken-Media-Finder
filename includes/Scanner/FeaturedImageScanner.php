<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class FeaturedImageScanner
{

	private $repository;

	public function __construct(ScanRepository $repository)
	{
		$this->repository = $repository;
	}

	private function is_url_broken($url)
	{
		if (empty($url)) {
			return true;
		}
		$response = wp_remote_head($url, array('timeout' => 10, 'sslverify' => false));
		if (is_wp_error($response)) {
			return true;
		}
		$code = wp_remote_retrieve_response_code($response);
		return $code >= 400;
	}

	public function scan($scan_id, $post_types = array('post', 'page'))
	{
		$found = 0;
		$per_page = 100;
		$paged = 1;

		do {
			$query = new \WP_Query(array(
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'paged' => $paged,
				'no_found_rows' => false,
			));

			if (!$query->have_posts()) {
				break;
			}

			foreach ($query->posts as $post) {
				$thumb_id = (int) get_post_thumbnail_id($post->ID);

				// No featured image assigned at all — report as missing
				if (!$thumb_id) {
					$previous = $this->repository->get_previous_status('', 'missing_featured_image', $post->ID);
					$status   = in_array($previous, array('ignored', 'fixed'), true) ? $previous : 'open';
					$this->repository->insert_result(array(
						'scan_id'      => $scan_id,
						'item_type'    => 'featured_image',
						'issue_type'   => 'missing_featured_image',
						'issue_status' => $status,
						'post_id'      => $post->ID,
						'source_url'   => '',
						'file_path'    => '',
						'message'      => sprintf(
							/* translators: %s: Post title. */
							__('No featured image assigned for "%s".', 'broken-media-finder'),
							$post->post_title ?: ('#' . $post->ID)
						),
						'severity'     => 'medium',
					));
					$found++;
					continue;
				}

				$file_path = get_attached_file($thumb_id);
				$url       = wp_get_attachment_url($thumb_id);
				$is_broken = false;

				if (!$file_path || !file_exists($file_path)) {
					$is_broken = true;
				}

				if ($is_broken) {
					$this->repository->insert_result(array(
						'scan_id'      => $scan_id,
						'item_type'    => 'featured_image',
						'issue_type'   => 'missing_featured_image',
						'post_id'      => $post->ID,
						'attachment_id' => $thumb_id,
						'source_url'   => $url ?: '',
						'file_path'    => $file_path ?: '',
						'message'      => sprintf(
							/* translators: 1: Post title, 2: Attachment ID. */
							__('Missing featured image file for "%1$s" (attachment #%2$d).', 'broken-media-finder'),
							$post->post_title,
							$thumb_id
						),
						'severity'     => 'high',
					));
					$found++;
				}
			}

			$paged++;
		} while ($paged <= $query->max_num_pages);

		return $found;
	}
}
