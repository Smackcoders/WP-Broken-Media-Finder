<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

class ScanResultsListTable extends \WP_List_Table {

	private $repo;
	private $filters;
	private $scan_valid = true;
	private $total_issues_count = 0;

	public function __construct( $filters = array(), $scan_valid = true ) {
		parent::__construct( array(
			'singular' => 'wpbmf_scan_result',
			'plural'   => 'wpbmf_scan_results',
			'ajax'     => false,
			'screen'   => 'wpbmf_scan_results',
		) );
		$this->repo = new ScanRepository();
		$this->filters = wp_parse_args( $filters, array(
			'issue_type'   => '',
			'issue_status' => '',
			'severity'     => '',
			'search'       => '',
			'scan_id'      => '',
		) );
		$this->scan_valid = $scan_valid;
	}

	public function get_columns() {
		return array(
			'issue_type'   => __( 'Type', 'broken-media-finder' ),
			'severity'     => __( 'Severity', 'broken-media-finder' ),
			'issue_status' => __( 'Status', 'broken-media-finder' ),
			'source_url'   => __( 'Source URL', 'broken-media-finder' ),
			'post_id'      => __( 'Post', 'broken-media-finder' ),
			'error_type'   => __( 'Error Type', 'broken-media-finder' ),
			'created_at'   => __( 'Scan Date', 'broken-media-finder' ),
			'actions'      => __( 'Actions', 'broken-media-finder' ),
		);
	}

	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		$per_page = $this->get_items_per_page( 'wpbmf_results_per_page', 20 );
		$current_page = $this->get_pagenum();

		if ( ! $this->scan_valid || ( empty( $this->filters['scan_id'] ) && ! empty( $_GET['scan_id'] ) ) ) {
			$this->items = array();
			$this->set_pagination_args( array(
				'total_items' => 0,
				'per_page'    => $per_page,
			) );
			return;
		}

		$total_items = $this->repo->count_results( $this->filters );
		$this->total_issues_count = $total_items;

		$this->items = $this->repo->get_results( array_merge( $this->filters, array(
			'limit'  => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		) ) );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
	}

	public function get_total_issues_count() {
		return $this->total_issues_count;
	}

	private function badge( $type, $value ) {
		$map = array(
			'issue_type' => array(
				'missing_image'          => array( 'red', __( 'Missing Image', 'broken-media-finder' ) ),
				'broken_image'           => array( 'orange', __( 'Broken Image', 'broken-media-finder' ) ),
				'broken_attachment_url'  => array( 'orange', __( 'Broken Link', 'broken-media-finder' ) ),
				'missing_featured_image' => array( 'red', __( 'Missing Featured', 'broken-media-finder' ) ),
				'unused_media'           => array( 'grey', __( 'Unused Media', 'broken-media-finder' ) ),
			),
			'severity' => array(
				'high'   => array( 'red', __( 'High', 'broken-media-finder' ) ),
				'medium' => array( 'orange', __( 'Medium', 'broken-media-finder' ) ),
				'low'    => array( 'grey', __( 'Low', 'broken-media-finder' ) ),
			),
			'status' => array(
				'open'                => array( 'blue', __( 'Open', 'broken-media-finder' ) ),
				'ignored'             => array( 'grey', __( 'Ignored', 'broken-media-finder' ) ),
				'fixed'               => array( 'green', __( 'Fixed', 'broken-media-finder' ) ),
				'placeholder_applied' => array( 'green', __( 'Placeholder Applied', 'broken-media-finder' ) ),
			),
		);
		$entry = $map[ $type ][ $value ] ?? array( 'grey', esc_html( $value ) );
		return '<span class="wpbmf-badge wpbmf-badge-' . esc_attr( $entry[0] ) . '">' . esc_html( $entry[1] ) . '</span>';
	}

	public function column_default( $item, $column_name ) {
		$mode = get_user_setting('wpbmf_list_mode', 'list');

		switch ( $column_name ) {
			case 'issue_type':
				return $this->badge( 'issue_type', $item['issue_type'] );
			case 'severity':
				return $this->badge( 'severity', $item['severity'] );
			case 'issue_status':
				return $this->badge( 'status', $item['issue_status'] );
			case 'source_url':
				if ('excerpt' === $mode) {
					return '<span title="' . esc_attr( $item['source_url'] ) . '" style="word-break: break-all;">' . esc_html( $item['source_url'] ) . '</span>';
				}
				return '<span title="' . esc_attr( $item['source_url'] ) . '">' . esc_html( mb_strimwidth( $item['source_url'] ?? '', 0, 80, '…' ) ) . '</span>';
			case 'post_id':
				if ( ! empty( $item['post_id'] ) ) {
					$pt = get_post_type( $item['post_id'] );
					$pt_label = $pt ? ucfirst( $pt ) : 'Post';
					return '<a href="' . esc_url( get_edit_post_link( $item['post_id'] ) ) . '">' .
						'<strong>' . esc_html( $pt_label ) . ' #' . (int) $item['post_id'] . '</strong><br>' .
						esc_html( mb_strimwidth( get_the_title( $item['post_id'] ), 0, 30, '…' ) ) .
					'</a>';
				} elseif ( ! empty( $item['attachment_id'] ) ) {
					return '<a href="' . esc_url( get_edit_post_link( $item['attachment_id'] ) ) . '">' .
						/* translators: %d: Attachment ID. */
						sprintf( esc_html__( 'Attachment #%d', 'broken-media-finder' ), (int) $item['attachment_id'] ) .
					'</a>';
				}
				return '—';
			case 'error_type':
				$error = esc_html( ! empty( $item['error_type'] ) ? $item['error_type'] : __( 'N/A', 'broken-media-finder' ) );
				if ('excerpt' === $mode && !empty($item['message'])) {
					$error .= '<br><span style="color:#666; font-size:12px;">' . esc_html( $item['message'] ) . '</span>';
				}
				return $error;
			case 'created_at':
				return esc_html( $item['created_at'] );
			case 'actions':
				$html = '';
				if ( 'open' === $item['issue_status'] ) {
					$html .= '<form method="post" style="display:inline;">';
					$html .= wp_nonce_field( 'wpbmf_status_action', '_wpnonce', true, false );
					$html .= '<input type="hidden" name="result_id" value="' . esc_attr( $item['id'] ) . '">';
					$html .= '<button type="submit" name="wpbmf_update_status" value="ignored" class="button-link">' . esc_html__( 'Ignore', 'broken-media-finder' ) . '</button>';
					$html .= '&nbsp;|&nbsp;';
					$html .= '<button type="submit" name="wpbmf_update_status" value="fixed" class="button-link">' . esc_html__( 'Mark Fixed', 'broken-media-finder' ) . '</button>';
					$html .= '</form>';
				} else {
					$html .= $this->badge( 'status', $item['issue_status'] );
				}
				if ( ! empty( $item['post_id'] ) && 'placeholder_applied' !== $item['issue_status'] && 'unused_media' !== $item['issue_type'] && 'Missing src' !== $item['error_type'] ) {
					$html .= '&nbsp;|&nbsp;';
					$html .= '<form method="post" style="display:inline;">';
					$html .= wp_nonce_field( 'wpbmf_replace_action', '_wpnonce', true, false );
					$html .= '<input type="hidden" name="result_id" value="' . esc_attr( $item['id'] ) . '">';
					$html .= '<button type="submit" name="wpbmf_replace_placeholder" value="1" class="button-link wpbmf-confirm-replace">' . esc_html__( 'Use Placeholder', 'broken-media-finder' ) . '</button>';
					$html .= '</form>';
				}
				return $html;
			default:
				return '';
		}
	}
}
