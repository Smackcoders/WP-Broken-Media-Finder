<?php
namespace Smackcoders\BrokenMediaFinder\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SettingsPage {

	const OPTION = 'wpbmf_settings';

	public function register() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting( 'wpbmf_settings_group', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	public function sanitize( $input ) {
		$s = get_option( self::OPTION, array() );

		$s['enable_plugin']              = ! empty( $input['enable_plugin'] ) ? '1' : '0';
		$s['post_types']                 = array_map( 'sanitize_text_field', (array) ( $input['post_types'] ?? array( 'post', 'page' ) ) );
		$s['scan_content_images']        = ! empty( $input['scan_content_images'] ) ? '1' : '0';
		$s['scan_attachment_links']      = ! empty( $input['scan_attachment_links'] ) ? '1' : '0';
		$s['scan_featured_images']       = ! empty( $input['scan_featured_images'] ) ? '1' : '0';
		$s['scan_unused_media']          = ! empty( $input['scan_unused_media'] ) ? '1' : '0';
		$s['include_attached_in_unused'] = ! empty( $input['include_attached_in_unused'] ) ? '1' : '0';
		$s['placeholder_source']         = in_array( $input['placeholder_source'] ?? '', array( 'default', 'custom' ), true ) ? $input['placeholder_source'] : 'default';
		$s['custom_placeholder_url']     = esc_url_raw( $input['custom_placeholder_url'] ?? '' );
		$s['keep_previous_results']      = ! empty( $input['keep_previous_results'] ) ? '1' : '0';
		$s['show_dashboard_widget']      = ! empty( $input['show_dashboard_widget'] ) ? '1' : '0';
		$s['delete_data_on_uninstall']   = ! empty( $input['delete_data_on_uninstall'] ) ? '1' : '0';

		return $s;
	}

	public function render() {
		$s    = get_option( self::OPTION, array() );
		$pts  = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="wrap wpbmf-settings-wrap">
			<div id="wpbmf-pro-success-msg" style="display: none; background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
				<?php esc_html_e( 'Form submitted successfully!', 'broken-media-finder' ); ?>
			</div>
			<form method="post" action="options.php">
				<?php settings_fields( 'wpbmf_settings_group' ); ?>
				<div class="wpbmf-settings-tabs">
					<nav class="wpbmf-tab-nav">
						<div class="wpbmf-sidebar-brand">
							<div class="wpbmf-sidebar-logo">B</div>
							<div class="wpbmf-sidebar-title-wrap">
								<div class="wpbmf-sidebar-title"><?php esc_html_e( 'Broken Media Finder', 'broken-media-finder' ); ?></div>
								<div class="wpbmf-sidebar-subtitle"><?php esc_html_e( 'Scanner for WordPress', 'broken-media-finder' ); ?></div>
							</div>
						</div>
						<a href="#tab-scan" class="wpbmf-tab-link active">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
							<?php esc_html_e( 'Scan', 'broken-media-finder' ); ?>
						</a>
						<a href="#tab-repair" class="wpbmf-tab-link">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
							<?php esc_html_e( 'Repair', 'broken-media-finder' ); ?>
						</a>
						<a href="#tab-advanced" class="wpbmf-tab-link">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
							<?php esc_html_e( 'Advanced', 'broken-media-finder' ); ?>
						</a>
						<a href="#tab-pro-wc" class="wpbmf-tab-link wpbmf-pro-tab">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
							<?php esc_html_e( 'WooCommerce & CPT', 'broken-media-finder' ); ?> <span class="wpbmf-pro-badge">PRO</span>
						</a>
						<a href="#tab-pro-autorepair" class="wpbmf-tab-link wpbmf-pro-tab">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
							<?php esc_html_e( 'Auto-Repair', 'broken-media-finder' ); ?> <span class="wpbmf-pro-badge">PRO</span>
						</a>
						<a href="#tab-pro-cloud" class="wpbmf-tab-link wpbmf-pro-tab">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg>
							<?php esc_html_e( 'Cloud Media', 'broken-media-finder' ); ?> <span class="wpbmf-pro-badge">PRO</span>
						</a>
						<a href="#tab-pro-cdn" class="wpbmf-tab-link wpbmf-pro-tab">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
							<?php esc_html_e( 'CDN Validation', 'broken-media-finder' ); ?> <span class="wpbmf-pro-badge">PRO</span>
						</a>
						<a href="#tab-pro-bulk" class="wpbmf-tab-link wpbmf-pro-tab">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; flex-shrink: 0;"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
							<?php esc_html_e( 'Bulk Replacement', 'broken-media-finder' ); ?> <span class="wpbmf-pro-badge">PRO</span>
						</a>
					</nav>

					<div class="wpbmf-main-content">
						<div id="tab-scan" class="wpbmf-tab-panel active">
							<div class="wpbmf-page-header" style="margin-bottom: 24px; padding-bottom: 0; border-bottom: none;">
								<h2 style="margin: 0; font-size: 22px; letter-spacing: -0.02em; font-weight: 800; line-height: 1.2; color: #0f172a;"><?php esc_html_e( 'Scan Settings', 'broken-media-finder' ); ?></h2>
								<p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b; font-weight: 500;"><?php esc_html_e( 'Configure how Broken Media Finder scans your site for missing media.', 'broken-media-finder' ); ?></p>
							</div>

							<div class="wpbmf-tab-panel-content">
								<div class="wpbmf-setting-group">
								<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Enable Plugin', 'broken-media-finder' ); ?></h3>
								<div class="wpbmf-setting-content">
									<label class="wpbmf-toggle-label">
										<div class="wpbmf-toggle">
											<input type="checkbox" name="wpbmf_settings[enable_plugin]" value="1" <?php checked( '1', $s['enable_plugin'] ?? '1' ); ?>>
											<span class="wpbmf-toggle-slider"></span>
										</div>
										<span class="wpbmf-toggle-text"><?php esc_html_e( 'Enable Broken Media Finder', 'broken-media-finder' ); ?></span>
									</label>
								</div>
							</div>

							<div class="wpbmf-setting-group">
								<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Post Types', 'broken-media-finder' ); ?></h3>
								<div class="wpbmf-setting-content">
									<?php $selected_pts = (array) ( $s['post_types'] ?? array( 'post', 'page' ) );
									foreach ( $pts as $pt ) :
										if ( in_array( $pt->name, array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' ), true ) ) continue; ?>
										<label class="wpbmf-toggle-label">
											<div class="wpbmf-toggle">
												<input type="checkbox" name="wpbmf_settings[post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $selected_pts, true ) ); ?>>
												<span class="wpbmf-toggle-slider"></span>
											</div>
											<span class="wpbmf-toggle-text"><?php echo esc_html( $pt->labels->singular_name ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="wpbmf-setting-group">
								<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Scan Types', 'broken-media-finder' ); ?></h3>
								<div class="wpbmf-setting-content">
									<?php $scan_types = array( 'scan_content_images' => __( 'Scan content for missing images', 'broken-media-finder' ), 'scan_attachment_links' => __( 'Scan content for broken attachment links', 'broken-media-finder' ), 'scan_featured_images' => __( 'Detect missing featured image files', 'broken-media-finder' ), 'scan_unused_media' => __( 'List unused media attachments', 'broken-media-finder' ) );
									foreach ( $scan_types as $key => $label ) : ?>
										<label class="wpbmf-toggle-label">
											<div class="wpbmf-toggle">
												<input type="checkbox" name="wpbmf_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( '1', $s[ $key ] ?? '1' ); ?>>
												<span class="wpbmf-toggle-slider"></span>
											</div>
											<span class="wpbmf-toggle-text"><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="wpbmf-setting-group">
								<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Unused Media Options', 'broken-media-finder' ); ?></h3>
								<div class="wpbmf-setting-content">
									<label class="wpbmf-toggle-label">
										<div class="wpbmf-toggle">
											<input type="checkbox" name="wpbmf_settings[include_attached_in_unused]" value="1" <?php checked( '1', $s['include_attached_in_unused'] ?? '0' ); ?>>
											<span class="wpbmf-toggle-slider"></span>
										</div>
										<span class="wpbmf-toggle-text"><?php esc_html_e( 'Include media attached to a parent post in unused scan', 'broken-media-finder' ); ?></span>
									</label>
								</div>
							</div>
							</div>
					</div>

					<div id="tab-repair" class="wpbmf-tab-panel">
							<div class="wpbmf-page-header" style="margin-bottom: 24px; padding-bottom: 0; border-bottom: none;">
								<h2 style="margin: 0; font-size: 22px; letter-spacing: -0.02em; font-weight: 800; line-height: 1.2; color: #0f172a;"><?php esc_html_e( 'Repair Options', 'broken-media-finder' ); ?></h2>
								<p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b; font-weight: 500;"><?php esc_html_e( 'Manage default placeholders and media repair behavior.', 'broken-media-finder' ); ?></p>
							</div>

							<div class="wpbmf-tab-panel-content">
								<table class="form-table" role="presentation">
							<tr><th style="vertical-align: top; padding-top: 24px;"><?php esc_html_e( 'Placeholder Source', 'broken-media-finder' ); ?></th>
								<td>
									<div style="display: flex; flex-direction: column; gap: 12px;">
										<label class="wpbmf-radio-label">
											<input type="radio" class="wpbmf-radio" name="wpbmf_settings[placeholder_source]" value="default" <?php checked( 'default', $s['placeholder_source'] ?? 'default' ); ?>>
											<span><?php esc_html_e( 'Use default plugin placeholder', 'broken-media-finder' ); ?></span>
										</label>
										<label class="wpbmf-radio-label">
											<input type="radio" class="wpbmf-radio" name="wpbmf_settings[placeholder_source]" value="custom" <?php checked( 'custom', $s['placeholder_source'] ?? 'default' ); ?>>
											<span><?php esc_html_e( 'Use custom URL', 'broken-media-finder' ); ?></span>
										</label>
									</div>
								</td></tr>
							<tr id="wpbmf_custom_url_row" style="<?php echo ( 'custom' === ( $s['placeholder_source'] ?? 'default' ) ) ? '' : 'display: none;'; ?>"><th><label for="wpbmf_custom_ph"><?php esc_html_e( 'Custom Placeholder URL', 'broken-media-finder' ); ?></label></th>
								<td>
									<div style="display: flex; align-items: center; gap: 12px;">
										<input type="hidden" id="wpbmf_custom_ph_id" name="wpbmf_settings[custom_placeholder_id]" value="">
										<input type="text" id="wpbmf_custom_ph" name="wpbmf_settings[custom_placeholder_url]" value="<?php echo esc_attr( $s['custom_placeholder_url'] ?? '' ); ?>" class="wpbmf-input" placeholder="https://" style="flex: 1; max-width: 400px;">
										<button type="button" class="wpbmf-btn-secondary" id="wpbmf_upload_placeholder"><?php esc_html_e( 'Choose Image', 'broken-media-finder' ); ?></button>
									</div>
									<p class="description"><?php esc_html_e( 'Select from Media Library or enter a URL.', 'broken-media-finder' ); ?></p>
								</td></tr>
								</table>
							</div>
					</div>

					<div id="tab-advanced" class="wpbmf-tab-panel">
							<div class="wpbmf-page-header" style="margin-bottom: 24px; padding-bottom: 0; border-bottom: none;">
								<h2 style="margin: 0; font-size: 22px; letter-spacing: -0.02em; font-weight: 800; line-height: 1.2; color: #0f172a;"><?php esc_html_e( 'Advanced Settings', 'broken-media-finder' ); ?></h2>
								<p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b; font-weight: 500;"><?php esc_html_e( 'Control dashboard widgets, history retention, and uninstallation behavior.', 'broken-media-finder' ); ?></p>
							</div>

							<div class="wpbmf-tab-panel-content">
								<div class="wpbmf-setting-group">
							<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Keep Previous Results', 'broken-media-finder' ); ?></h3>
							<div class="wpbmf-setting-content">
								<label class="wpbmf-toggle-label">
									<div class="wpbmf-toggle">
										<input type="checkbox" name="wpbmf_settings[keep_previous_results]" value="1" <?php checked( '1', $s['keep_previous_results'] ?? '1' ); ?>>
										<span class="wpbmf-toggle-slider"></span>
									</div>
									<span class="wpbmf-toggle-text"><?php esc_html_e( 'Keep previous scan results when running a new scan', 'broken-media-finder' ); ?></span>
								</label>
							</div>
						</div>

						<div class="wpbmf-setting-group">
							<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Dashboard Widget', 'broken-media-finder' ); ?></h3>
							<div class="wpbmf-setting-content">
								<label class="wpbmf-toggle-label">
									<div class="wpbmf-toggle">
										<input type="checkbox" name="wpbmf_settings[show_dashboard_widget]" value="1" <?php checked( '1', $s['show_dashboard_widget'] ?? '1' ); ?>>
										<span class="wpbmf-toggle-slider"></span>
									</div>
									<span class="wpbmf-toggle-text"><?php esc_html_e( 'Show scan summary widget on dashboard', 'broken-media-finder' ); ?></span>
								</label>
							</div>
						</div>

						<div class="wpbmf-setting-group">
							<h3 class="wpbmf-setting-title"><?php esc_html_e( 'Delete Data on Uninstall', 'broken-media-finder' ); ?></h3>
							<div class="wpbmf-setting-content">
								<label class="wpbmf-toggle-label">
									<div class="wpbmf-toggle">
										<input type="checkbox" name="wpbmf_settings[delete_data_on_uninstall]" value="1" <?php checked( '1', $s['delete_data_on_uninstall'] ?? '0' ); ?>>
										<span class="wpbmf-toggle-slider"></span>
									</div>
									<span class="wpbmf-toggle-text"><?php esc_html_e( 'Delete all plugin data when uninstalling', 'broken-media-finder' ); ?></span>
								</label>
							</div>
						</div>
						</div>
					</div>

					<div id="tab-pro-wc" class="wpbmf-tab-panel wpbmf-pro-panel" style="padding: 30px; text-align: center; background: #fff; border: 1px solid #c3c4c7; border-top: none;">
						<div class="wpbmf-pro-notice">
							<h2><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></h2>
							<p style="font-size: 16px; margin-bottom: 20px;"><?php esc_html_e( 'Unlock advanced scanning for WooCommerce products, custom fields, and Custom Post Types (CPT). Upgrade to Pro to access these powerful features.', 'broken-media-finder' ); ?></p>
							<a href="#" class="button button-primary button-large"><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></a>
						</div>
					</div>

					<div id="tab-pro-autorepair" class="wpbmf-tab-panel wpbmf-pro-panel" style="padding: 30px; text-align: center; background: #fff; border: 1px solid #c3c4c7; border-top: none;">
						<div class="wpbmf-pro-notice">
							<h2><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></h2>
							<p style="font-size: 16px; margin-bottom: 20px;"><?php esc_html_e( 'Automatically detect and repair media that has been moved or renamed. Save hours of manual fixing by upgrading to Pro.', 'broken-media-finder' ); ?></p>
							<a href="#" class="button button-primary button-large"><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></a>
						</div>
					</div>

					<div id="tab-pro-cloud" class="wpbmf-tab-panel wpbmf-pro-panel" style="padding: 30px; text-align: center; background: #fff; border: 1px solid #c3c4c7; border-top: none;">
						<div class="wpbmf-pro-notice">
							<h2><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></h2>
							<p style="font-size: 16px; margin-bottom: 20px;"><?php esc_html_e( 'Seamlessly integrate and scan media hosted on cloud platforms like AWS S3, Google Cloud, and DigitalOcean Spaces. Get the Pro version today.', 'broken-media-finder' ); ?></p>
							<a href="#" class="button button-primary button-large"><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></a>
						</div>
					</div>

					<div id="tab-pro-cdn" class="wpbmf-tab-panel wpbmf-pro-panel" style="padding: 30px; text-align: center; background: #fff; border: 1px solid #c3c4c7; border-top: none;">
						<div class="wpbmf-pro-notice">
							<h2><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></h2>
							<p style="font-size: 16px; margin-bottom: 20px;"><?php esc_html_e( 'Validate external images and media served via CDNs. Ensure your fast-loading assets are always available with Broken Media Finder Pro.', 'broken-media-finder' ); ?></p>
							<a href="#" class="button button-primary button-large"><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></a>
						</div>
					</div>

					<div id="tab-pro-bulk" class="wpbmf-tab-panel wpbmf-pro-panel" style="padding: 30px; text-align: center; background: #fff; border: 1px solid #c3c4c7; border-top: none;">
						<div class="wpbmf-pro-notice">
							<h2><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></h2>
							<p style="font-size: 16px; margin-bottom: 20px;"><?php esc_html_e( 'Replace missing images and broken links across your entire site in bulk. Upgrade to Pro for powerful bulk media replacement tools.', 'broken-media-finder' ); ?></p>
							<a href="#" class="button button-primary button-large"><?php esc_html_e( 'Upgrade to Pro', 'broken-media-finder' ); ?></a>
						</div>
					</div>
					<div class="wpbmf-footer-actions">
						<input type="submit" name="submit" id="submit" class="wpbmf-btn-primary" value="<?php esc_attr_e( 'Save Settings', 'broken-media-finder' ); ?>">
					</div>
					</div> <!-- End .wpbmf-main-content -->
				</div>
			</form>
		</div>

		<!-- Pro Upgrade Modal -->
		<div id="wpbmf-pro-modal" class="wpbmf-pro-modal">
			<div class="wpbmf-pro-modal-backdrop" id="wpbmf-pro-modal-backdrop"></div>
			<div class="wpbmf-pro-modal-container">
				<button type="button" class="wpbmf-pro-modal-close-icon wslh-modal-close" id="wpbmf-pro-modal-close-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
				
				<div id="wpbmf-pro-promo-view" style="text-align: center;">
					<div class="wpbmf-pro-modal-icon">&#11088;</div>
					<h3 class="wpbmf-dynamic-title" style="margin-top: 0; font-size: 20px;"><?php esc_html_e( 'Pro Feature', 'broken-media-finder' ); ?></h3>
					<p style="margin-bottom: 20px; color: #64748b;"><?php esc_html_e( 'This feature is available in the Pro version of Broken Media Finder. Upgrade to unlock bulk replacement, automated repairs, cloud support, and more.', 'broken-media-finder' ); ?></p>
					<div class="wpbmf-pro-modal-footer">
						<button type="button" class="wpbmf-pro-btn-cancel wslh-modal-close"><?php esc_html_e( 'Maybe Later', 'broken-media-finder' ); ?></button>
						<button type="button" id="wpbmf-show-form-btn" class="wpbmf-pro-btn-upgrade"><?php esc_html_e( 'Upgrade Now', 'broken-media-finder' ); ?></button>
					</div>
				</div>

				<div id="wpbmf-pro-form-view" style="display: none;">
					<h3 style="margin-top: 0; font-size: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px;"><?php esc_html_e( 'Request Pro Feature', 'broken-media-finder' ); ?></h3>
					
					<form id="wpbmf-pro-request-form">
						<div style="margin-bottom: 16px;">
							<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;"><?php esc_html_e( 'Name', 'broken-media-finder' ); ?></label>
							<input type="text" id="wpbmf_pro_req_name" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Your Name">
						</div>
						<div style="margin-bottom: 16px;">
							<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;"><?php esc_html_e( 'Email', 'broken-media-finder' ); ?></label>
							<input type="email" id="wpbmf_pro_req_email" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Your Email">
						</div>
						<div style="margin-bottom: 24px;">
						<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;"><?php esc_html_e( 'Request Message', 'broken-media-finder' ); ?></label>
						<textarea id="wpbmf_pro_req_msg" required rows="3" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
					</div>
					<div class="wpbmf-pro-modal-footer" style="margin-top: 20px;">
						<input type="hidden" id="wpbmf_pro_req_feature" value="">
						<button type="button" class="wpbmf-pro-btn-cancel wslh-modal-close"><?php esc_html_e( 'Cancel', 'broken-media-finder' ); ?></button>
						<button type="submit" class="wpbmf-pro-btn-upgrade" id="wpbmf-pro-btn-submit"><?php esc_html_e( 'Submit Request', 'broken-media-finder' ); ?></button>
					</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
