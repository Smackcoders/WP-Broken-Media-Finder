<?php
namespace Smackcoders\BrokenMediaFinder\Scanner;

if (!defined('ABSPATH')) {
	exit;
}

class ScanRepository
{

	private $table;

	public function __construct()
	{
		$this->table = ScanTable::get_table_name();
	}

	public function insert_result(array $data)
	{
		global $wpdb;
		$db = $wpdb;
		$now = current_time('mysql');
		$now_gmt = current_time('mysql', true);

		$prev_status = $this->get_previous_status(
			isset($data['source_url']) ? esc_url_raw($data['source_url']) : '',
			sanitize_text_field($data['issue_type'] ?? ''),
			isset($data['post_id']) ? absint($data['post_id']) : null
		);
		$row = array(
			'scan_id' => sanitize_text_field($data['scan_id'] ?? ''),
			'item_type' => sanitize_text_field($data['item_type'] ?? ''),
			'issue_type' => sanitize_text_field($data['issue_type'] ?? ''),
			'issue_status' => $prev_status,
			'post_id' => isset($data['post_id']) ? absint($data['post_id']) : null,
			'attachment_id' => isset($data['attachment_id']) ? absint($data['attachment_id']) : null,
			'source_url' => isset($data['source_url']) ? esc_url_raw($data['source_url']) : null,
			'resolved_url' => isset($data['resolved_url']) ? esc_url_raw($data['resolved_url']) : null,
			'file_path' => isset($data['file_path']) ? sanitize_text_field($data['file_path']) : null,
			'message' => sanitize_textarea_field($data['message'] ?? ''),
			'severity' => in_array($data['severity'] ?? '', array('low', 'medium', 'high'), true) ? $data['severity'] : 'medium',
			'error_type' => isset($data['error_type']) ? sanitize_text_field($data['error_type']) : null,
			'created_at' => $now,
			'created_at_gmt' => $now_gmt,
		);

		$row = apply_filters('wpbmf_result_data_before_insert', $row);

		$result = $db->insert($this->table, $row);
		$id = $result ? (int) $db->insert_id : false;

		if ($id) {
			wp_cache_delete('wpbmf_latest_scan_id', 'wpbmf');
			wp_cache_delete('wpbmf_scan_summary_' . ($row['scan_id'] ?? ''), 'wpbmf');
			do_action('wpbmf_result_inserted', $id, $row);
		}

		return $id;
	}

	public function get_result($id)
	{
		global $wpdb;
		$db = $wpdb;
		$cache_key = 'wpbmf_result_' . (int) $id;
		$cached = wp_cache_get($cache_key, 'wpbmf');
		if (false !== $cached) {
			return $cached;
		}
		$result = $db->get_row(
			$db->prepare("SELECT * FROM {$wpdb->prefix}wpbmf_scan_results WHERE id = %d", (int) $id),
			ARRAY_A
		);
		wp_cache_set($cache_key, $result, 'wpbmf');
		return $result;
	}

	public function get_results(array $args = array())
	{
		global $wpdb;
		$db = $wpdb;

		$defaults = array(
			'scan_id' => '',
			'item_type' => '',
			'issue_type' => '',
			'issue_status' => '',
			'post_id' => 0,
			'attachment_id' => 0,
			'severity' => '',
			'search' => '',
			'date_from' => '',
			'date_to' => '',
			'limit' => 20,
			'offset' => 0,
			'orderby' => 'created_at_gmt',
			'order' => 'DESC',
		);

		$args = wp_parse_args($args, $defaults);
		$where = array('1=1');
		$prepare = array();

		if (!empty($args['scan_id'])) {
			$where[] = 'scan_id = %s';
			$prepare[] = sanitize_text_field($args['scan_id']);
		}
		if (!empty($args['item_type'])) {
			$where[] = 'item_type = %s';
			$prepare[] = sanitize_text_field($args['item_type']);
		}
		if (!empty($args['issue_type'])) {
			$where[] = 'issue_type = %s';
			$prepare[] = sanitize_text_field($args['issue_type']);
		}
		if (!empty($args['issue_status'])) {
			$where[] = 'issue_status = %s';
			$prepare[] = sanitize_text_field($args['issue_status']);
		}
		if (!empty($args['post_id'])) {
			$where[] = 'post_id = %d';
			$prepare[] = (int) $args['post_id'];
		}
		if (!empty($args['attachment_id'])) {
			$where[] = 'attachment_id = %d';
			$prepare[] = (int) $args['attachment_id'];
		}
		if (!empty($args['severity'])) {
			$where[] = 'severity = %s';
			$prepare[] = sanitize_text_field($args['severity']);
		}
		if (!empty($args['search'])) {
			$where[] = '(source_url LIKE %s OR message LIKE %s)';
			$like = '%' . $db->esc_like(sanitize_text_field($args['search'])) . '%';
			$prepare[] = $like;
			$prepare[] = $like;
		}
		if (!empty($args['date_from'])) {
			$where[] = 'created_at_gmt >= %s';
			$prepare[] = sanitize_text_field($args['date_from']) . ' 00:00:00';
		}
		if (!empty($args['date_to'])) {
			$where[] = 'created_at_gmt <= %s';
			$prepare[] = sanitize_text_field($args['date_to']) . ' 23:59:59';
		}

		$allowed = array('id', 'scan_id', 'issue_type', 'severity', 'issue_status', 'post_id', 'created_at_gmt');
		$orderby = in_array($args['orderby'], $allowed, true) ? $args['orderby'] : 'created_at_gmt';
		$order = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';
		$limit = max(1, (int) $args['limit']);
		$offset = max(0, (int) $args['offset']);
		$where_sql = implode(' AND ', $where);
		$sql = "SELECT * FROM `{$wpdb->prefix}wpbmf_scan_results` WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$prepare[] = $limit;
		$prepare[] = $offset;

		$prepared_query = $db->prepare($sql, ...$prepare);
		return $db->get_results($prepared_query, ARRAY_A) ?: array();
	}

	public function count_results(array $args = array())
	{
		global $wpdb;
		$db = $wpdb;
		$args['limit'] = 99999;
		$args['offset'] = 0;
		// Re-use filter logic via a slim path
		$defaults = array('scan_id' => '', 'item_type' => '', 'issue_type' => '', 'issue_status' => '', 'search' => '', 'date_from' => '', 'date_to' => '');
		$args = wp_parse_args($args, $defaults);
		$where = array('1=1');
		$prepare = array();
		if (!empty($args['scan_id'])) {
			$where[] = 'scan_id = %s';
			$prepare[] = sanitize_text_field($args['scan_id']);
		}
		if (!empty($args['item_type'])) {
			$where[] = 'item_type = %s';
			$prepare[] = sanitize_text_field($args['item_type']);
		}
		if (!empty($args['issue_type'])) {
			$where[] = 'issue_type = %s';
			$prepare[] = sanitize_text_field($args['issue_type']);
		}
		if (!empty($args['issue_status'])) {
			$where[] = 'issue_status = %s';
			$prepare[] = sanitize_text_field($args['issue_status']);
		}
		if (!empty($args['search'])) {
			$where[] = '(source_url LIKE %s OR message LIKE %s)';
			$like = '%' . $db->esc_like(sanitize_text_field($args['search'])) . '%';
			$prepare[] = $like;
			$prepare[] = $like;
		}
		$where_sql = implode(' AND ', $where);
		$sql = "SELECT COUNT(*) FROM `{$wpdb->prefix}wpbmf_scan_results` WHERE {$where_sql}";
		if (!empty($prepare)) {
			return (int) $db->get_var($db->prepare($sql, ...$prepare));
		}
		return (int) $db->get_var($db->prepare($sql));
	}

	public function update_result_status($id, $status)
	{
		global $wpdb;
		$db = $wpdb;
		$allowed = array('open', 'ignored', 'fixed', 'placeholder_applied');
		if (!in_array($status, $allowed, true)) {
			return false;
		}
		$result = (bool) $db->update(
			$this->table,
			array(
				'issue_status' => $status,
				'updated_at' => current_time('mysql'),
				'updated_at_gmt' => current_time('mysql', true),
			),
			array('id' => (int) $id)
		);
		if ($result) {
			wp_cache_delete('wpbmf_result_' . (int) $id, 'wpbmf');
		}
		return $result;
	}

	public function delete_result($id)
	{
		global $wpdb;
		$db = $wpdb;
		$result = (bool) $db->delete($this->table, array('id' => (int) $id), array('%d'));
		if ($result) {
			wp_cache_delete('wpbmf_result_' . (int) $id, 'wpbmf');
			wp_cache_delete('wpbmf_latest_scan_id', 'wpbmf');
		}
		return $result;
	}

	public function delete_results_by_scan_id($scan_id)
	{
		global $wpdb;
		$db = $wpdb;
		$result = (int) $db->delete($this->table, array('scan_id' => sanitize_text_field($scan_id)), array('%s'));
		if ($result) {
			wp_cache_delete('wpbmf_scan_summary_' . sanitize_text_field($scan_id), 'wpbmf');
			wp_cache_delete('wpbmf_latest_scan_id', 'wpbmf');
		}
		return $result;
	}

	public function clear_all_results()
	{
		global $wpdb;
		$db = $wpdb;
		$result = (int) $db->query("DELETE FROM {$wpdb->prefix}wpbmf_scan_results");
		if ($result) {
			wp_cache_flush();
		}
		return $result;
	}

	public function get_latest_scan_id()
	{
		global $wpdb;
		$db = $wpdb;
		$cached = wp_cache_get('wpbmf_latest_scan_id', 'wpbmf');
		if (false !== $cached) {
			return $cached;
		}
		$result = $db->get_var("SELECT scan_id FROM {$wpdb->prefix}wpbmf_scan_results ORDER BY created_at_gmt DESC LIMIT 1");
		wp_cache_set('wpbmf_latest_scan_id', $result, 'wpbmf');
		return $result;
	}

	public function get_scan_summary($scan_id)
	{
		global $wpdb;
		$db = $wpdb;
		$cache_key = 'wpbmf_scan_summary_' . sanitize_text_field($scan_id);
		$cached = wp_cache_get($cache_key, 'wpbmf');
		if (false !== $cached) {
			return $cached;
		}
		$rows = $db->get_results(
			$db->prepare(
				"SELECT issue_type, COUNT(*) as count FROM {$wpdb->prefix}wpbmf_scan_results WHERE scan_id = %s GROUP BY issue_type",
				sanitize_text_field($scan_id)
			),
			ARRAY_A
		) ?: array();

		$summary = array(
			'total' => 0,
			'missing_image' => 0,
			'broken_image' => 0,
			'broken_attachment_url' => 0,
			'missing_featured_image' => 0,
			'unused_media' => 0,
		);

		foreach ($rows as $row) {
			$summary[$row['issue_type']] = (int) $row['count'];
			$summary['total'] += (int) $row['count'];
		}

		wp_cache_set($cache_key, $summary, 'wpbmf');
		return $summary;
	}

	public function get_previous_status($source_url, $issue_type, $post_id = null)
	{
		global $wpdb;
		$cache_key = 'wpbmf_prev_status_' . md5($source_url . '_' . $issue_type . '_' . (int) $post_id);
		$cached = wp_cache_get($cache_key, 'wpbmf');
		if (false !== $cached) {
			return $cached;
		}
		$args = array(
			'issue_type' => $issue_type,
			'issue_status' => '',
			'search' => '',
			'limit' => 50,
			'offset' => 0,
			'order' => 'DESC',
			'orderby' => 'created_at_gmt',
		);
		if ($post_id) {
			$args['post_id'] = (int) $post_id;
		}
		$results = $this->get_results($args);
		$status = 'open';
		foreach ($results as $row) {
			if ($row['source_url'] === $source_url) {
				if (in_array($row['issue_status'], array('ignored', 'fixed', 'placeholder_applied'), true)) {
					$status = $row['issue_status'];
					break;
				}
			}
		}
		wp_cache_set($cache_key, $status, 'wpbmf', 300);
		return $status;
	}

	public function get_export_rows(array $args = array())
	{
		$args['limit'] = 99999;
		$args['offset'] = 0;
		return $this->get_results($args);
	}

	public function get_result_by_attachment($attachment_id, $scan_id = '')
	{
		global $wpdb;
		$db = $wpdb;
		$cache_key = 'wpbmf_attachment_' . (int) $attachment_id . '_' . sanitize_text_field($scan_id);
		$cached = wp_cache_get($cache_key, 'wpbmf');
		if (false !== $cached) {
			return $cached;
		}
		if ($scan_id) {
			$result = $db->get_row(
				$db->prepare("SELECT * FROM {$wpdb->prefix}wpbmf_scan_results WHERE attachment_id = %d AND scan_id = %s ORDER BY id DESC LIMIT 1", (int) $attachment_id, sanitize_text_field($scan_id)),
				ARRAY_A
			);
		} else {
			$result = $db->get_row(
				$db->prepare("SELECT * FROM {$wpdb->prefix}wpbmf_scan_results WHERE attachment_id = %d ORDER BY created_at_gmt DESC LIMIT 1", (int) $attachment_id),
				ARRAY_A
			);
		}
		wp_cache_set($cache_key, $result, 'wpbmf');
		return $result;
	}

	public function update_source_url($id, $new_url)
	{
		global $wpdb;
		$db = $wpdb;
		$result = $db->update(
			$this->table,
			array('resolved_url' => esc_url_raw($new_url), 'issue_status' => 'placeholder_applied', 'updated_at_gmt' => current_time('mysql', true)),
			array('id' => (int) $id)
		);
		if ($result) {
			wp_cache_delete('wpbmf_result_' . (int) $id, 'wpbmf');
		}
		return $result;
	}
}
