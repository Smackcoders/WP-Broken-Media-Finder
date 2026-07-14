=== Broken Media Finder ===
Contributors: smackcoders
Donate link: https://smackcoders.com/
Tags: broken images, missing media, unused media, media cleanup, attachment links
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Find missing images, broken attachment links, unused media files, and export broken media reports from your WordPress site.

== Description ==

**Broken Media Finder** helps WordPress admins, agencies, bloggers, and WooCommerce store owners detect missing images, broken attachment links, and unused media files.

The plugin provides a lightweight maintenance tool that scans site content, reports broken media references, helps replace missing images with a placeholder, and exports scan results for cleanup work.

= What It Scans =

* **Missing images** — images inside post/page content whose files no longer exist on disk
* **Broken attachment links** — links to PDFs, ZIPs, and other upload files that no longer exist
* **Missing featured images** — posts/pages whose featured image attachment file is gone
* **Unused media** — attachments not used as featured images or in post/page content

= Key Features =

* Manual scan button — run a scan whenever you need it
* Dashboard with 5 summary cards: total, missing images, broken links, missing featured, unused
* Detailed results page with filters: issue type, severity, status, search
* Mark results as Ignored or Fixed
* Replace a missing image URL with a placeholder in post content
* Default built-in placeholder image (customisable in settings)
* Export scan results as CSV (formula injection protected)
* Scan history: last 20 scans with issue counts
* Media Library column showing Unused / Missing file / Used status per attachment
* Dashboard widget with latest scan summary
* WP-CLI commands for automation
* Developer hooks and filters for custom integrations
* Translation-ready
* Security hardened: nonces, capability checks, prepared SQL, CSV formula injection protection

**Important:** Broken Media Finder only reports and marks issues. It never deletes media files automatically. Always review unused media results carefully before deleting any files — false positives are possible.

= Who Is This For? =

* Agencies running maintenance for client sites after migrations
* Bloggers who have changed domain or SSL certificate
* Developers cleaning up after a site rebuild
* SEO teams auditing content quality
* Content managers after a media library cleanup

= Pro Features (Coming Soon) =

* Auto-repair moved media (match by filename, suggest URL swap)
* Cloud media support (Amazon S3, Cloudflare R2, Bunny CDN)
* WooCommerce product image scan
* Scheduled weekly scan with email summary
* External URL validation
* CDN media validation
* Bulk replacement rules
* Client-ready PDF reports
* Xapplets automation integration

== Installation ==

1. Upload the plugin to `/wp-content/plugins/broken-media-finder/`, or install through the WordPress plugins screen.
2. Activate the plugin.
3. Go to **Tools > Broken Media** to run your first scan.
4. Go to **Settings > Broken Media Finder** to configure scan types and placeholder image.

== Frequently Asked Questions ==

= Does this plugin delete unused media automatically? =
No. The plugin only lists and marks unused media. Admins must review results carefully and delete files manually if needed. False positives are possible.

= Can it replace missing images? =
Yes. Missing images found in post content can be replaced with a configured placeholder image. This modifies post_content — the original URL is saved in the scan result.

= Does it scan external image URLs? =
The free version focuses on internal WordPress uploads only. External URL checking is planned for Pro.

= Does it support WooCommerce product images? =
WooCommerce product image scanning is planned for the Pro version.

= Can I export scan results? =
Yes. Scan results can be exported as CSV from the dashboard or results page.

= Are WP-CLI commands available? =
Yes:
- `wp wpbmf scan` — Run a full scan
- `wp wpbmf summary` — View latest scan summary
- `wp wpbmf results --type=missing_image` — List results
- `wp wpbmf export --file=/path/report.csv` — Export to CSV
- `wp wpbmf clear --yes` — Clear all results

= Can developers extend the plugin? =
Yes. Available hooks:
- `wpbmf_before_scan_started` — Fires before scan begins
- `wpbmf_after_scan_completed` — Fires after scan with summary
- `wpbmf_scan_failed` — Fires on scan error
- `wpbmf_result_inserted` — Fires after each result is saved
- `wpbmf_placeholder_applied` — Fires after placeholder replacement
- `wpbmf_results_cleared` — Fires after results are cleared
- `wpbmf_supported_post_types` — Customize scanned post types
- `wpbmf_enabled_scan_types` — Enable/disable scan types
- `wpbmf_upload_url_to_path` — Override URL-to-path mapping
- `wpbmf_is_internal_media_url` — Override internal URL check
- `wpbmf_scan_query_args` — Modify WP_Query args for scanning
- `wpbmf_result_data_before_insert` — Modify result before saving
- `wpbmf_csv_export_columns` — Customize CSV columns
- `wpbmf_csv_export_filename` — Customize export filename
- `wpbmf_placeholder_url` — Override placeholder URL
- `wpbmf_unused_media_exclusions` — Add exclusion IDs from unused scan

== Screenshots ==

1. Dashboard with summary cards and scan history.
2. Scan results page with issue type/severity/status badges, filters, and row actions.
3. Replace missing image with placeholder — before and after.
4. Settings page showing scan types and placeholder configuration.
5. Media Library with the Media Scan status column.
6. Dashboard widget with scan summary.

== Changelog ==

= 1.0.0 (2026-06-04) =
* Initial release.
* Plugin scaffold with PSR-4 autoloader, constants, activation/deactivation hooks.
* Custom database table (wpbmf_scan_results) with dbDelta and 7 indexes.
* ScanRepository with full CRUD, status update, summary, and export methods.
* UrlExtractor: img src, srcset, upload link extraction, URL-to-path mapping.
* ContentScanner: scan post/page content for missing local image files.
* AttachmentScanner: scan post/page content for broken document/file links.
* FeaturedImageScanner: detect posts/pages whose featured image file is missing.
* UnusedMediaScanner: list attachments not used in content or as featured images.
* ScanManager: orchestrate all scanners, generate scan ID, save scan history.
* DashboardPage: summary cards, actions bar, scan history table.
* ScanResultsPage: paginated results with 5 filters, badges, row actions.
* PlaceholderManager: resolve placeholder URL from settings or default SVG.
* MediaReplacer: replace missing image URL in post content with placeholder.
* CsvExporter: all result fields, formula injection protection, filter-aware.
* SettingsPage: tabbed UI for scan types, post types, placeholder, advanced.
* MediaColumns: Media Library scan status column per attachment.
* DashboardWidget: latest scan summary and quick links.
* AdminMenu: wires scan, export, status update, replace, clear actions.
* WP-CLI commands: scan, summary, results, export, clear.
* Developer hooks and filters throughout scan lifecycle.
* PHPUnit setup + tests for plugin load, repository, URL extractor, CSV exporter.
* Translation-ready with broken-media-finder text domain.
* Default placeholder.svg bundled.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
