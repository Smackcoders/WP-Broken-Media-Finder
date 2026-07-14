<?php
namespace Smackcoders\BrokenMediaFinder\Repair;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if (!defined('ABSPATH')) {
	exit;
}

class MediaReplacer
{

	private $repository;
	private $placeholder;

	public function __construct()
	{
		$this->repository = new ScanRepository();
		$this->placeholder = new PlaceholderManager();
	}

	public function replace($result_id)
	{
		$result = $this->repository->get_result($result_id);


		if (!$result) {
			return array('success' => false, 'message' => __('Scan result not found.', 'broken-media-finder'));
		}

		if (empty($result['post_id'])) {
			return array('success' => false, 'message' => __('Cannot replace: no post ID.', 'broken-media-finder'));
		}
		if (empty($result['source_url']) && !in_array($result['error_type'], array('Missing src', 'Empty src'), true) && $result['issue_type'] !== 'missing_featured_image') {
			return array('success' => false, 'message' => __('Cannot replace: no source URL.', 'broken-media-finder'));
		}

		$post = get_post((int) $result['post_id']);
		if (!$post) {
			return array('success' => false, 'message' => __('Post not found.', 'broken-media-finder'));
		}

		$issue_type = $result['issue_type'] ?? '';
		$placeholder_url = $this->placeholder->get_placeholder_url();
		$old_url = $result['source_url'];

		// Validate placeholder URL
		$plugin_url = trailingslashit(WPBMF_PLUGIN_URL);
		$plugin_dir = trailingslashit(WPBMF_PLUGIN_DIR);
		$upload_dir = wp_upload_dir();
		$upload_base_url = trailingslashit($upload_dir['baseurl']);
		$upload_base_dir = trailingslashit($upload_dir['basedir']);

		if (strpos($placeholder_url, $plugin_url) === 0) {
			$local_path = str_replace($plugin_url, $plugin_dir, $placeholder_url);
			if (!file_exists($local_path)) {
				return array('success' => false, 'message' => __('Placeholder URL is invalid or unreachable. Please update it in Settings.', 'broken-media-finder'));
			}
		} elseif (strpos($placeholder_url, $upload_base_url) === 0) {
			$local_path = str_replace($upload_base_url, $upload_base_dir, $placeholder_url);
			if (!file_exists($local_path)) {
				return array('success' => false, 'message' => __('Placeholder image does not exist in uploads directory. Please update it in Settings.', 'broken-media-finder'));
			}
		} else {
			$ph_response = wp_remote_head($placeholder_url, array('timeout' => 10, 'sslverify' => false));
			if (is_wp_error($ph_response) || wp_remote_retrieve_response_code($ph_response) >= 400) {
				return array('success' => false, 'message' => __('Placeholder URL is invalid or unreachable. Please update it in Settings.', 'broken-media-finder'));
			}
		}

		if ('missing_featured_image' === $issue_type) {
			$placeholder_id = $this->placeholder->get_or_create_placeholder_attachment($post->ID);
			if (!$placeholder_id || is_wp_error($placeholder_id)) {
				return array('success' => false, 'message' => __('Could not create placeholder attachment.', 'broken-media-finder'));
			}
			set_post_thumbnail($post->ID, $placeholder_id);
			$this->repository->update_result_status($result_id, 'placeholder_applied');
			$this->repository->update_source_url($result_id, $placeholder_url);
			do_action('wpbmf_placeholder_applied', $result_id, $post->ID, $old_url, $placeholder_url);
			return array('success' => true, 'message' => __('Featured image replaced with placeholder.', 'broken-media-finder'));
		}

		// Handle Empty src: <img> tag with empty src attribute (src="")
		if ('Empty src' === $result['error_type']) {
			$new_content = preg_replace(
				'/<img([^>]*?)\bsrc\s*=\s*["\']\s*["\']([^>]*)>/i',
				'<img$1src="' . esc_url($placeholder_url) . '"$2>',
				$post->post_content
			);

			if (null === $new_content || $new_content === $post->post_content) {
				return array('success' => false, 'message' => __('No img tag with empty src found in post content.', 'broken-media-finder'));
			}

			$update_result = wp_update_post(array('ID' => $post->ID, 'post_content' => $new_content), true);
			if (is_wp_error($update_result)) {
				return array('success' => false, 'message' => $update_result->get_error_message());
			}

			$this->repository->update_result_status($result_id, 'placeholder_applied');
			$this->repository->update_source_url($result_id, $placeholder_url);
			do_action('wpbmf_placeholder_applied', $result_id, $post->ID, $old_url, $placeholder_url);
			return array('success' => true, 'message' => __('Placeholder src added to image tag.', 'broken-media-finder'));
		}

		// Handle broken attachment URL: replace href in <a> tags pointing to the broken URL
		if ('broken_attachment_url' === $issue_type && !empty($old_url)) {
			// Replace href value in anchor tags
			$new_content = preg_replace_callback(
				'/<a([^>]*)\bhref=["\']' . preg_quote($old_url, '/') . '["\']([^>]*)>/i',
				function ($matches) use ($placeholder_url) {
					return '<a' . $matches[1] . 'href="' . esc_url($placeholder_url) . '"' . $matches[2] . '>';
				},
				$post->post_content
			);

			// Fallback: plain str_replace if regex didn't match
			if (null === $new_content || $new_content === $post->post_content) {
				$new_content = str_replace($old_url, $placeholder_url, $post->post_content);
			}

			if ($new_content === $post->post_content) {
				return array('success' => false, 'message' => __('URL not found in post content.', 'broken-media-finder'));
			}

			$update_result = wp_update_post(array('ID' => $post->ID, 'post_content' => $new_content), true);
			if (is_wp_error($update_result)) {
				return array('success' => false, 'message' => $update_result->get_error_message());
			}

			$this->repository->update_result_status($result_id, 'placeholder_applied');
			$this->repository->update_source_url($result_id, $placeholder_url);
			do_action('wpbmf_placeholder_applied', $result_id, $post->ID, $old_url, $placeholder_url);
			return array('success' => true, 'message' => __('Broken link replaced with placeholder.', 'broken-media-finder'));
		}

		// Default: simple str_replace for missing_image with a known URL
		$new_content = str_replace($old_url, $placeholder_url, $post->post_content);

		if ($new_content === $post->post_content) {
			return array('success' => false, 'message' => __('URL not found in post content.', 'broken-media-finder'));
		}

		$update_result = wp_update_post(array(
			'ID' => $post->ID,
			'post_content' => $new_content,
		), true);

		if (is_wp_error($update_result)) {
			return array('success' => false, 'message' => $update_result->get_error_message());
		}

		$this->repository->update_result_status($result_id, 'placeholder_applied');
		$this->repository->update_source_url($result_id, $placeholder_url);

		do_action('wpbmf_placeholder_applied', $result_id, $post->ID, $old_url, $placeholder_url);

		return array(
			'success' => true,
			'message' => __('Image replaced with placeholder.', 'broken-media-finder'),
		);
	}
}
