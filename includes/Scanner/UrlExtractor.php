<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class UrlExtractor
{

	public function extract_image_urls($content)
	{
		$urls = array();

		// Extract img src
		if (preg_match_all('/<img\s[^>]*?src\s*=\s*["\']([^"\']+)["\']/i', $content, $matches)) {
			foreach ($matches[1] as $url) {
				$urls[] = $this->normalize_url($url);
			}
		}

		// Extract srcset
		if (preg_match_all('/<img\s[^>]*?srcset\s*=\s*["\']([^"\']+)["\']/i', $content, $matches)) {
			foreach ($matches[1] as $srcset) {
				foreach ($this->extract_srcset_urls($srcset) as $url) {
					$urls[] = $url;
				}
			}
		}

		// Gutenberg block url attributes (generic, matches local or external)
		if (preg_match_all('/"(url|src)"\s*:\s*"(https?:[^"]+)"/i', $content, $matches)) {
			foreach ($matches[2] as $url) {
				$urls[] = $this->normalize_url($url);
			}
		}

		// Raw URLs embedded in content
		if (preg_match_all('/(https?:\/\/[^\s\'"<>\(\)]+\.(?:jpe?g|png|gif|webp|avif|svg))/i', $content, $matches)) {
			foreach ($matches[1] as $url) {
				$urls[] = $this->normalize_url($url);
			}
		}

		return array_values(array_unique(array_filter($urls)));
	}

	public function extract_srcset_urls($srcset)
	{
		$urls = array();
		$parts = preg_split('/,\s*/', trim($srcset));
		foreach ($parts as $part) {
			$part = trim($part);
			if (empty($part)) {
				continue;
			}
			$pieces = preg_split('/\s+/', $part);
			if (!empty($pieces[0])) {
				$urls[] = $this->normalize_url($pieces[0]);
			}
		}
		return array_values(array_unique(array_filter($urls)));
	}

	public function extract_upload_links($content)
	{
		$urls = array();
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];

		if (preg_match_all('/<a[^>]+href=["\']([^"\']*\/uploads\/[^"\']+)["\'][^>]*>/i', $content, $matches)) {
			foreach ($matches[1] as $url) {
				$urls[] = $this->normalize_url($url);
			}
		}

		// Direct file links that aren't images
		if (preg_match_all('/"([^"]*\/uploads\/[^"]+\.(pdf|zip|doc|docx|xls|xlsx|ppt|pptx|mp4|mp3|mov|avi|wav|ogg|svg|webp|avif))"/i', $content, $matches)) {
			foreach ($matches[1] as $url) {
				$urls[] = $this->normalize_url($url);
			}
		}

		return array_values(array_unique(array_filter($urls, array($this, 'is_internal_media_url'))));
	}

	public function normalize_url($url)
	{
		$url = trim($url);
		if (empty($url)) {
			return '';
		}
		// Unescape JSON-escaped slashes
		$url = str_replace('\/', '/', $url);
		// Strip ?v=xxx or #anchor
		$url = preg_replace('/[?#].*$/', '', $url);
		// Normalise protocol-relative
		if (0 === strpos($url, '//')) {
			$url = (is_ssl() ? 'https:' : 'http:') . $url;
		}
		return $url;
	}

	public function is_internal_media_url($url)
	{
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];

		// Path-only comparison: handles domain mismatches (e.g. dev.smackcoders.com → localhost).
		$url_path = (string) wp_parse_url($url, PHP_URL_PATH);
		$base_path = (string) wp_parse_url($base_url, PHP_URL_PATH);

		if ('' !== $url_path && '' !== $base_path && 0 === strpos($url_path, $base_path)) {
			return apply_filters('wpbmf_is_internal_media_url', true, $url);
		}

		// Fallback: domain-exact comparison.
		$url_stripped = preg_replace('#^https?://#', '', $url);
		$base_stripped = preg_replace('#^https?://#', '', $base_url);

		return apply_filters('wpbmf_is_internal_media_url', 0 === strpos($url_stripped, $base_stripped), $url);
	}

	public function url_to_file_path($url)
	{
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_dir = $upload_dir['basedir'];

		// Path-only comparison: handles domain mismatches (e.g. dev.smackcoders.com → localhost).
		$url_path = (string) wp_parse_url($url, PHP_URL_PATH);
		$base_path = (string) wp_parse_url($base_url, PHP_URL_PATH);

		if ('' !== $url_path && '' !== $base_path && 0 === strpos($url_path, $base_path)) {
			$relative = substr($url_path, strlen($base_path));
			$path = $base_dir . $relative;
			return apply_filters('wpbmf_upload_url_to_path', $path, $url);
		}

		// Fallback: domain-exact comparison (normal case where URL already matches local site).
		$url_stripped = preg_replace('#^https?://#', '', $url);
		$base_stripped = preg_replace('#^https?://#', '', $base_url);

		if (0 === strpos($url_stripped, $base_stripped)) {
			$relative = substr($url_stripped, strlen($base_stripped));
			$path = $base_dir . $relative;
			return apply_filters('wpbmf_upload_url_to_path', $path, $url);
		}

		return '';
	}
}
