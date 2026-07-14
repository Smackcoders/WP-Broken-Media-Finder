<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ScanHistoryListTable extends \WP_List_Table {

	private $history_data;

	public function __construct( $history_data = array() ) {
		parent::__construct( array(
			'singular' => 'wpbmf_scan_history',
			'plural'   => 'wpbmf_scan_histories',
			'ajax'     => false,
			'screen'   => 'wpbmf_scan_history',
		) );
		$this->history_data = $history_data;
	}

	public function get_columns() {
		return array(
			'cb'                     => '<input type="checkbox" />',
			'scanned_at'             => __( 'Date & Time', 'broken-media-finder' ),
			'scan_id'                => __( 'Scan ID', 'broken-media-finder' ),
			'total'                  => __( 'Total Issues', 'broken-media-finder' ),
			'missing_image'          => __( 'Missing Images', 'broken-media-finder' ),
			'broken_image'           => __( 'Broken Images', 'broken-media-finder' ),
			'missing_featured_image' => __( 'Missing Featured', 'broken-media-finder' ),
			'unused_media'           => __( 'Unused Media', 'broken-media-finder' ),
			'actions'                => __( 'Actions', 'broken-media-finder' ),
		);
	}

	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'broken-media-finder' ),
		);
	}

	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		$per_page = $this->get_items_per_page( 'wpbmf_history_per_page', 10 );
		$current_page = $this->get_pagenum();

		$total_items = count( $this->history_data );
		$offset = ( $current_page - 1 ) * $per_page;

		$this->items = array_slice( $this->history_data, $offset, $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
	}

	public function column_cb( $item ) {
		return '<input type="checkbox" name="wpbmf_scan_ids[]" value="' . esc_attr( $item['scan_id'] ?? '' ) . '" />';
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'scanned_at':
				return esc_html( $item['scanned_at'] ?? '' );
			case 'scan_id':
				return '<code>' . esc_html( $item['scan_id'] ?? '' ) . '</code>';
			case 'total':
				return '<strong>' . (int) ( $item['total'] ?? 0 ) . '</strong>';
			case 'missing_image':
			case 'broken_image':
			case 'missing_featured_image':
			case 'unused_media':
				return (int) ( $item[ $column_name ] ?? 0 );
			case 'actions':
				$view_url = add_query_arg(
					array(
						'page'                => 'wpbmf-history',
						'scan_id'             => rawurlencode( $item['scan_id'] ?? '' ),
						'wpbmf_history_nonce' => wp_create_nonce( 'wpbmf_history_view' ),
					),
					admin_url( 'admin.php' )
				);

				$html = '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
				$html .= '<a href="' . esc_url( $view_url ) . '" class="button button-small button-secondary">' . esc_html__( 'View Results', 'broken-media-finder' ) . '</a>';
				
				$delete_url = wp_nonce_url(
					add_query_arg(
						array(
							'page'              => 'wpbmf-history',
							'wpbmf_delete_scan' => $item['scan_id'] ?? '',
						),
						admin_url( 'admin.php' )
					),
					'wpbmf_delete_scan_' . ( $item['scan_id'] ?? '' )
				);

				$html .= '<a href="' . esc_url( $delete_url ) . '" class="button button-small button-link-delete" style="color:#b32d2e;" onclick="return confirm(\'' . esc_attr__( 'Are you sure you want to delete this scan and its results? This cannot be undone.', 'broken-media-finder' ) . '\');">' . esc_html__( 'Delete', 'broken-media-finder' ) . '</a>';
				$html .= '</div>';
				$html .= '</div>';

				return $html;
			default:
				return '';
		}
	}
}
