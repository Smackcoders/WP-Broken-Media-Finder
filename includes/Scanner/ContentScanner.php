<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class ContentScanner
{

	private $extractor;
	private $repository;
	private $validated_urls_cache = array();

	public function __construct(UrlExtractor $extractor, ScanRepository $repository)
	{
		$this->extractor = $extractor;
		$this->repository = $repository;
	}

	private function validate_url($url)
	{
		if (isset($this->validated_urls_cache[$url])) {
			return $this->validated_urls_cache[$url];
		}

		if (empty($url) || !is_string($url)) {
			$result = array('status' => 'broken', 'error_type' => 'Invalid URL', 'message' => __('Empty or invalid URL', 'broken-media-finder'));
			$this->validated_urls_cache[$url] = $result;
			return $result;
		}

		// ── Internal uploads URL: check file on disk (fast, no HTTP) ─────────
		if ($this->extractor->is_internal_media_url($url)) {
			$path = $this->extractor->url_to_file_path($url);
			if (!empty($path)) {
				if (file_exists($path)) {
					$result = array('status' => 'valid', 'file_path' => $path);
					$this->validated_urls_cache[$url] = $result;
					return $result;
				}
				$result = array(
					'status'     => 'broken',
					'error_type' => 'File Missing',
					'message'    => __('Local file does not exist', 'broken-media-finder'),
					'file_path'  => $path,
				);
				$this->validated_urls_cache[$url] = $result;
				return $result;
			}
		}

		// ── Same-site URL (non-uploads path): resolve to ABSPATH and check disk
		$url_host  = (string) wp_parse_url($url, PHP_URL_HOST);
		$site_host = (string) wp_parse_url(site_url(), PHP_URL_HOST);

		if ($url_host !== '' && $url_host === $site_host) {
			$url_path  = (string) wp_parse_url($url, PHP_URL_PATH);
			$site_path = rtrim((string) wp_parse_url(site_url(), PHP_URL_PATH), '/');
			$relative  = ($site_path !== '' && strpos($url_path, $site_path) === 0)
				? substr($url_path, strlen($site_path))
				: $url_path;
			$local_path = untrailingslashit(ABSPATH) . $relative;
			if (file_exists($local_path)) {
				$result = array('status' => 'valid', 'file_path' => $local_path);
				$this->validated_urls_cache[$url] = $result;
				return $result;
			}
			$result = array(
				'status'     => 'broken',
				'error_type' => 'File Missing',
				'message'    => __('Internal file does not exist on server', 'broken-media-finder'),
				'file_path'  => $local_path,
			);
			$this->validated_urls_cache[$url] = $result;
			return $result;
		}

		// ── External URL: validate format then make HTTP request ──────────────
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			$result = array('status' => 'broken', 'error_type' => 'Invalid URL', 'message' => __('Invalid URL format', 'broken-media-finder'));
			$this->validated_urls_cache[$url] = $result;
			return $result;
		}

		$parsed = wp_parse_url($url);
		if (empty($parsed['host'])) {
			$result = array('status' => 'broken', 'error_type' => 'Invalid URL', 'message' => __('Invalid host', 'broken-media-finder'));
			$this->validated_urls_cache[$url] = $result;
			return $result;
		}

		$args = array(
			'timeout'    => 10,
			'user-agent' => 'Mozilla/5.0 (compatible; BrokenMediaFinder; WordPress/' . get_bloginfo('version') . ')',
			'sslverify'  => false,
		);

		$response = wp_remote_head($url, $args);

		if (is_wp_error($response) || in_array(wp_remote_retrieve_response_code($response), array(400, 403, 405, 500, 501), true)) {
			$response = wp_remote_get($url, array_merge($args, array('limit_response_size' => 1024)));
		}

		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			if (stripos($error_message, 'timed out') !== false || stripos($error_message, 'timeout') !== false) {
				$error_type = 'Timeout';
				$error_msg  = __('Request timed out', 'broken-media-finder');
			} elseif (
				stripos($error_message, 'Could not resolve') !== false ||
				stripos($error_message, 'Name or service not known') !== false ||
				stripos($error_message, 'getaddrinfo') !== false
			) {
				$error_type = 'DNS Failure';
				$error_msg  = __('DNS resolution failed', 'broken-media-finder');
			} elseif (stripos($error_message, 'Connection refused') !== false) {
				$error_type = 'Connection Refused';
				$error_msg  = __('Connection refused by remote server', 'broken-media-finder');
			} else {
				$error_type = 'HTTP Error';
				$error_msg  = $error_message;
			}
			$result = array('status' => 'broken', 'error_type' => $error_type, 'message' => $error_msg);
			$this->validated_urls_cache[$url] = $result;
			return $result;
		}

		$code = (int) wp_remote_retrieve_response_code($response);

		if (404 === $code) {
			$result = array('status' => 'broken', 'error_type' => '404', 'message' => __('404 Not Found', 'broken-media-finder'));
		} elseif (410 === $code) {
			$result = array('status' => 'broken', 'error_type' => '410', 'message' => __('410 Gone', 'broken-media-finder'));
		} elseif ($code >= 400) {
			// translators: %d: HTTP status code.
			$result = array('status' => 'broken', 'error_type' => (string) $code, 'message' => sprintf(__('HTTP Error %d', 'broken-media-finder'), $code));
		} else {
			$result = array('status' => 'valid', 'code' => $code);
		}

		$this->validated_urls_cache[$url] = $result;
		return $result;
	}

	public function scan($scan_id, $post_types = array('post', 'page'))
	{
		$found = 0;
		$per_page = 20;
		$paged = 1;

		$scan_post_types = (array) $post_types;
		if (!in_array('wp_block', $scan_post_types, true)) {
			$scan_post_types[] = 'wp_block';
		}

		do {
			$query = new \WP_Query(apply_filters('wpbmf_scan_query_args', array(
				'post_type' => $scan_post_types,
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'paged' => $paged,
				'no_found_rows' => false,
				'fields' => 'all',
			)));

			if (!$query->have_posts()) {
				break;
			}

			foreach ($query->posts as $post) {
				if (empty($post->post_content)) {
					continue;
				}

				// Check for img tags with missing/empty src
				if (preg_match_all('/<img[^>]*>/i', $post->post_content, $img_matches)) {
					foreach ($img_matches[0] as $img_tag) {
						$has_src_value = preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $img_tag);
						if (!$has_src_value) {
							// It's either missing or empty. Let's check if 'src=' exists.
							$has_src_attr = preg_match('/\bsrc\s*=/i', $img_tag);
							
							$error_type = $has_src_attr ? 'Empty src' : 'Missing src';
							$msg = $has_src_attr 
								? __('Image tag with empty src in "%s"', 'broken-media-finder')
								: __('Image tag with missing src in "%s"', 'broken-media-finder');

							$this->repository->insert_result(array(
								'scan_id' => $scan_id,
								'item_type' => 'post_content_image',
								'issue_type' => 'broken_image',
								'post_id' => $post->ID,
								'source_url' => '',
								'error_type' => $error_type,
								'message' => sprintf(
									$msg,
									$post->post_title ?: ('#' . $post->ID)
								),
								'severity' => 'high',
							));
							$found++;
						}
					}
				}

				$content_to_scan = $post->post_content;

				// Elementor / Divi / other builder support: extract URLs from post meta
				$meta_values = get_post_meta($post->ID);
				foreach ($meta_values as $key => $values) {
					// Skip internal WP meta starting with _wp_
					if (strpos($key, '_wp_') === 0 || strpos($key, '_edit_') === 0) {
						continue;
					}
					foreach ($values as $val) {
						if (is_string($val) && strpos($val, 'http') !== false) {
							$content_to_scan .= "\n" . $val;
						}
					}
				}

				$urls = $this->extractor->extract_image_urls($content_to_scan);
				$seen = array();

				foreach ($urls as $url) {
					if (isset($seen[$url])) {
						continue;
					}
					$seen[$url] = true;

					$validation = $this->validate_url($url);

					if ('broken' === $validation['status']) {
						$missing_errors = array('404', '410', 'File Missing');
						$resolved_issue_type = in_array($validation['error_type'], $missing_errors, true) ? 'missing_image' : 'broken_image';

						$previous = $this->repository->get_previous_status($url, $resolved_issue_type, $post->ID);
						$status = in_array($previous, array('ignored', 'fixed'), true) ? $previous : 'open';

						if ('missing_image' === $resolved_issue_type) {
							// translators: 1: Post title, 2: Image URL, 3: Error type.
							$msg_format = __('Missing image in "%1$s": %2$s (Error: %3$s)', 'broken-media-finder');
						} else {
							// translators: 1: Post title, 2: Image URL, 3: Error type.
							$msg_format = __('Broken image URL in "%1$s": %2$s (Error: %3$s)', 'broken-media-finder');
						}

						$this->repository->insert_result(array(
							'issue_status' => $status,
							'scan_id' => $scan_id,
							'item_type' => 'post_content_image',
							'issue_type' => $resolved_issue_type,
							'post_id' => $post->ID,
							'source_url' => $url,
							'file_path' => $validation['file_path'] ?? null,
							'error_type' => $validation['error_type'],
							'message' => sprintf(
								$msg_format,
								$post->post_title ?: ('#' . $post->ID),
								$url,
								$validation['error_type']
							),
							'severity' => 'high',
						));
						$found++;
					}
				}
			}

			$paged++;
		} while ($paged <= $query->max_num_pages);

		// Scan widgets
		$sidebars = get_option('sidebars_widgets', array());
		if (is_array($sidebars)) {
			foreach ($sidebars as $sidebar_id => $widgets) {
				if ('wp_inactive_widgets' === $sidebar_id || !is_array($widgets)) {
					continue;
				}
				foreach ($widgets as $widget_id) {
					if (!preg_match('/^([a-zA-Z0-9\-_]+)-(\d+)$/', $widget_id, $matches)) {
						continue;
					}
					$type = $matches[1];
					$number = (int) $matches[2];
					$option_values = get_option('widget_' . $type);
					if (!is_array($option_values) || !isset($option_values[$number])) {
						continue;
					}
					$widget_settings = $option_values[$number];
					$content = '';
					if ('text' === $type && isset($widget_settings['text'])) {
						$content = $widget_settings['text'];
					} elseif ('custom_html' === $type && isset($widget_settings['content'])) {
						$content = $widget_settings['content'];
					} elseif ('block' === $type && isset($widget_settings['content'])) {
						$content = $widget_settings['content'];
					} elseif ('media_image' === $type && isset($widget_settings['url'])) {
						$content = '<img src="' . esc_url($widget_settings['url']) . '">';
					}
					if (empty($content)) {
						continue;
					}

					$urls = $this->extractor->extract_image_urls($content);
					$seen = array();
					foreach ($urls as $url) {
						if (isset($seen[$url])) {
							continue;
						}
						$seen[$url] = true;

						$validation = $this->validate_url($url);
						if ('broken' === $validation['status']) {
							$missing_errors = array('404', '410', 'File Missing');
							$widget_issue_type = in_array($validation['error_type'], $missing_errors, true) ? 'missing_image' : 'broken_image';

							if ('missing_image' === $widget_issue_type) {
								// translators: 1: Widget Type, 2: Widget ID, 3: Image URL, 4: Error type.
								$widget_msg_format = __('Missing image in Widget [%1$s #%2$d]: %3$s (Error: %4$s)', 'broken-media-finder');
							} else {
								// translators: 1: Widget Type, 2: Widget ID, 3: Image URL, 4: Error type.
								$widget_msg_format = __('Broken image URL in Widget [%1$s #%2$d]: %3$s (Error: %4$s)', 'broken-media-finder');
							}

							$this->repository->insert_result(array(
								'scan_id' => $scan_id,
								'item_type' => 'widget_image',
								'issue_type' => $widget_issue_type,
								'post_id' => null,
								'source_url' => $url,
								'file_path' => $validation['file_path'] ?? null,
								'error_type' => $validation['error_type'],
								'message' => sprintf(
									$widget_msg_format,
									ucfirst($type),
									$number,
									$url,
									$validation['error_type']
								),
								'severity' => 'high',
							));
							$found++;
						}
					}
				}
			}
		}

		return $found;
	}
}
