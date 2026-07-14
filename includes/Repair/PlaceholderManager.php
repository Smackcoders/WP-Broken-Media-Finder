<?php
namespace Smackcoders\BrokenMediaFinder\Repair;

if (!defined('ABSPATH')) {
	exit;
}

class PlaceholderManager
{

	public function get_or_create_placeholder_attachment($post_id)
	{
		$placeholder_url = $this->get_placeholder_url();

		// If a custom placeholder is set and it exists in the Media Library, use its ID directly
		$custom_id = attachment_url_to_postid($placeholder_url);
		if ($custom_id) {
			return $custom_id;
		}

		$existing = get_option('wpbmf_placeholder_attachment_id');
		if ($existing && get_post((int) $existing)) {
			return (int) $existing;
		}

		$placeholder_url = $this->get_placeholder_url();
		$upload_dir = wp_upload_dir();
		$filename = 'wpbmf-placeholder.svg';
		$filepath = $upload_dir['path'] . '/' . $filename;

		$source = WPBMF_PLUGIN_DIR . 'assets/images/placeholder.svg';
		if (!file_exists($filepath)) {
			if (!copy($source, $filepath)) {
				return false;
			}
		}

		$attachment = array(
			'post_mime_type' => 'image/svg+xml',
			'post_title' => __('WP Broken Media Finder Placeholder', 'broken-media-finder'),
			'post_content' => '',
			'post_status' => 'inherit',
		);

		$attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
		if (is_wp_error($attach_id)) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
		wp_update_attachment_metadata($attach_id, $attach_data);

		update_option('wpbmf_placeholder_attachment_id', $attach_id);
		return $attach_id;
	}

	public function get_placeholder_url()
	{
		$settings = get_option('wpbmf_settings', array());
		$source = $settings['placeholder_source'] ?? 'default';

		if ('custom' === $source && !empty($settings['custom_placeholder_url'])) {
			$url = esc_url_raw($settings['custom_placeholder_url']);
		} else {
			$url = WPBMF_PLUGIN_URL . 'assets/images/placeholder.svg';
		}

		return apply_filters('wpbmf_placeholder_url', $url);
	}
}
