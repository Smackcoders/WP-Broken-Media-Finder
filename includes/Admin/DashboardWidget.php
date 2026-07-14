<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

use Smackcoders\BrokenMediaFinder\Scanner\ScanRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class DashboardWidget {

	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
	}

	public function add_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = get_option( 'wpbmf_settings', array() );
		if ( empty( $settings['show_dashboard_widget'] ) ) {
			return;
		}
		wp_add_dashboard_widget( 'wpbmf_widget', __( 'Broken Media Status', 'broken-media-finder' ), array( $this, 'render' ) );
	}

	public function render() {
		$repo    = new ScanRepository();
		$scan_id = $repo->get_latest_scan_id();
		$history = get_option( 'wpbmf_scan_history', array() );
		$last    = $history[0] ?? null;

		if ( ! $scan_id ) {
			echo '<p>' . esc_html__( 'No scan has been run yet.', 'broken-media-finder' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wpbmf-dashboard' ) ) . '" class="button">' . esc_html__( 'Go to Broken Media Finder', 'broken-media-finder' ) . '</a></p>';
			return;
		}

		$summary = $repo->get_scan_summary( $scan_id );
		?>
		<div class="wpbmf-widget">
			<?php if ( $last ) : ?>
				<p class="wpbmf-widget-meta"><?php
				/* translators: %s: Last scan date. */
				printf( esc_html__( 'Last scan: %s', 'broken-media-finder' ), esc_html( $last['scanned_at'] ) );
			?></p>
			<?php endif; ?>
			<ul class="wpbmf-widget-list">
				<li><strong><?php echo (int) $summary['total']; ?></strong> <?php esc_html_e( 'total issues', 'broken-media-finder' ); ?></li>
				<li><?php echo (int) $summary['missing_image']; ?> <?php esc_html_e( 'missing images', 'broken-media-finder' ); ?></li>
				<li><?php echo (int) $summary['broken_attachment_url']; ?> <?php esc_html_e( 'broken links', 'broken-media-finder' ); ?></li>
				<li><?php echo (int) $summary['missing_featured_image']; ?> <?php esc_html_e( 'missing featured', 'broken-media-finder' ); ?></li>
				<li><?php echo (int) $summary['unused_media']; ?> <?php esc_html_e( 'unused media', 'broken-media-finder' ); ?></li>
			</ul>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbmf-dashboard' ) ); ?>"><?php esc_html_e( 'View Dashboard', 'broken-media-finder' ); ?> &rarr;</a></p>
		</div>
		<?php
	}
}
