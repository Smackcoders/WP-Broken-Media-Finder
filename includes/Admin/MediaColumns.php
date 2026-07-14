<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class MediaColumns {

	private $latest_scan_id = null;

	public function register() {
		add_filter( 'manage_media_columns', array( $this, 'add_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	public function add_column( $columns ) {
		$columns['wpbmf_status'] = __( 'Media Scan', 'broken-media-finder' );
		return $columns;
	}

	public function render_column( $column, $attachment_id ) {
		if ( 'wpbmf_status' !== $column ) {
			return;
		}

		if ( null === $this->latest_scan_id ) {
			$repo                  = new ScanRepository();
			$this->latest_scan_id  = $repo->get_latest_scan_id() ?: '';
		}

		if ( empty( $this->latest_scan_id ) ) {
			echo '<span class="wpbmf-badge wpbmf-badge-grey">' . esc_html__( 'Not scanned', 'broken-media-finder' ) . '</span>';
			return;
		}

		$repo   = new ScanRepository();
		$result = $repo->get_result_by_attachment( $attachment_id, $this->latest_scan_id );

		if ( ! $result ) {
			echo '<span class="wpbmf-badge wpbmf-badge-green">' . esc_html__( 'Used', 'broken-media-finder' ) . '</span>';
			return;
		}

		$issue = $result['issue_type'];

		if ( 'unused_media' === $issue ) {
			echo '<span class="wpbmf-badge wpbmf-badge-grey">' . esc_html__( 'Unused', 'broken-media-finder' ) . '</span>';
		} elseif ( in_array( $issue, array( 'missing_featured_image', 'missing_image' ), true ) ) {
			echo '<span class="wpbmf-badge wpbmf-badge-red">' . esc_html__( 'Missing file', 'broken-media-finder' ) . '</span>';
		} else {
			echo '<span class="wpbmf-badge wpbmf-badge-orange">' . esc_html( str_replace( '_', ' ', ucfirst( $issue ) ) ) . '</span>';
		}
	}
}
