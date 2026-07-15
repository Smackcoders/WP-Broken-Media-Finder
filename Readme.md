# Broken Media Finder

A WordPress broken image finder and media scanner plugin that detects missing images, broken attachment links, missing featured images, and unused media files — then helps you clean them up.

## Overview

Broken Media Finder is a lightweight WordPress media audit plugin built for admins, agencies, bloggers, and WooCommerce store owners who need a dependable way to keep a media library healthy. Site migrations, theme changes, and content edits routinely leave behind dead links, orphaned media, and missing images that quietly hurt user experience and SEO — this is often called link rot, and it tends to go unnoticed until a visitor (or Google) finds it first.

As a WordPress content scanner and attachment link scanner, the plugin scans posts, pages, widgets, and page builder content (including Elementor- and Divi-style meta fields) for broken references, then reports everything in a single dashboard. It acts as a WordPress dead link detector for media: catching broken image src attributes, broken PDF/ZIP attachment links, missing featured images, and attachments that aren't used anywhere on the site. Results can be reviewed, marked, exported to CSV, replaced with a placeholder image, or automated entirely through WP-CLI — making it equally useful as a one-off site health check before a launch and as a recurring digital housekeeping tool for ongoing media cleanup.

**Important:** Broken Media Finder only reports and marks issues. It never deletes media files automatically. Always review unused media results carefully before deleting any files — false positives are possible.

## Key Features

- **Broken image & empty src detection** — finds images inside post/page content whose files no longer exist on disk, including empty or malformed `src` attributes
- **Attachment link scanner** — detects broken links to PDFs, ZIPs, and other non-image upload files (non-image broken link detection)
- **Missing featured image detection** — flags posts/pages whose featured image attachment file is missing
- **Unused media scanner** — lists attachments that aren't used as a featured image or anywhere in post/page content, so you can reclaim wasted storage
- **Page builder & widget content scanning** — reads Elementor/Divi-style post meta fields and classic/block sidebar widgets, not just `post_content`
- **One-click placeholder replacement** — swaps a missing image URL in post content for a configurable placeholder image, while preserving the original URL in the scan result
- **CSV export of scan results** — exports full scan data with CSV formula-injection protection built in
- **Scan history admin page** — keeps the last 20 scans with issue counts for quick before/after comparison
- **Dashboard, results, settings, and help admin pages** — a dedicated plugin menu with summary cards, filterable results (issue type, severity, status, search), a settings screen, and a help/support screen
- **Media Library scan status column** — shows Unused / Missing file / Used status per attachment right in the Media Library
- **Dashboard widget** — surfaces the latest scan summary without leaving wp-admin
- **WP-CLI scan commands** — `scan`, `summary`, `results`, `export`, and `clear` commands for automation and cron-driven audits
- **Developer hooks across the scan lifecycle** — before/after scan, scan failed, result inserted, and placeholder applied actions, plus filters for post types, scan types, query args, and CSV output
- **Translation-ready plugin** — built with a proper text domain and shipped with 19 bundled language translations
- **Security hardened** — nonces, capability checks, prepared SQL, and sanitized/escaped output throughout

## Use Cases

- **Post-migration site audits** — agencies and developers verifying that images and attachment links survived a domain, host, or CMS migration
- **Pre-launch or pre-redesign checks** — catching missing images and broken links before a redesign or relaunch goes live
- **Ongoing media library cleanup** — content managers and SEO teams periodically clearing unused media and orphaned attachments to keep storage lean
- **Theme or template changes** — finding images that broke after a theme switch or template restructuring
- **Client maintenance retainers** — support teams running a quick broken-media scan as part of routine site health maintenance
- **SEO content audits** — identifying broken images and dead links that could affect page quality or crawl signals

## Requirements

- **WordPress:** 5.0 or later
- **PHP:** 7.4 or later
- **Other requirements:** No external services or API keys required — all scanning runs against your own WordPress database and uploads directory

## Installation

### Install from WordPress

1. Download the plugin ZIP file.
2. Go to **WordPress Admin → Plugins → Add New**.
3. Upload the ZIP file, then click **Install Now**.
4. Activate the plugin.

### Manual Installation

1. Download or clone this repository.
2. Upload the `broken-media-finder` folder to `/wp-content/plugins/`.
3. Activate the plugin from **WordPress Admin → Plugins**.

## Configuration / Setup

1. After activation, go to **Tools → Broken Media** to run your first scan.
2. Go to **Settings → Broken Media Finder** to configure which scan types run, which post types are included, and the placeholder image used for missing-image replacement.
3. Optionally enable or disable the dashboard summary widget from the settings screen.
4. Review the **Help** tab inside the plugin for a quick reference of available features and WP-CLI commands.

## Usage

1. From the dashboard, click the manual scan button to scan your site for missing images, broken attachment links, missing featured images, and unused media.
2. Review results on the Scan Results page — filter by issue type, severity, or status, and search for specific items.
3. Mark individual results as **Ignored** or **Fixed**, or replace a missing image with your configured placeholder directly from the results table.
4. Check the Media Library's status column at any time to see whether an attachment is Used, Unused, or has a missing file.
5. Export the current results to CSV for a client report or further review.
6. Automate scans on a schedule (e.g. via cron) using the WP-CLI commands below:
   ```bash
   wp wpbmf scan
   wp wpbmf summary
   wp wpbmf results --type=missing_image
   wp wpbmf export --file=/path/report.csv
   wp wpbmf clear --yes
   ```

## Supported Integrations

- **WP-CLI** — full command-line support for scanning, exporting, and clearing results
- **Page builder content** — scans Elementor/Divi-style post meta fields in addition to standard post content
- **Classic & block widgets** — scans text, custom HTML, image, and block widgets in active sidebars
- **WooCommerce sites** — safe to run on WooCommerce stores today (dedicated product image scanning is planned for a future Pro release; see Roadmap)

## Screenshots / Demo

1. Dashboard with summary cards and scan history.
2. Scan results page with issue type/severity/status badges, filters, and row actions.
3. Replace missing image with placeholder — before and after.
4. Settings page showing scan types and placeholder configuration.
5. Media Library with the Media Scan status column.
6. Dashboard widget with scan summary.

## Documentation

In-plugin documentation is available from the **Help** page inside **Tools → Broken Media** after activation, covering feature usage and the full WP-CLI command reference. Developer hooks and filters are documented inline in the plugin source under `includes/`.

## Frequently Asked Questions

### Does this plugin delete unused media automatically?
No. The plugin only lists and marks unused media as part of its wordpress media cleanup workflow. Admins must review results carefully and delete files manually if needed — false positives are possible.

### Can it replace missing images?
Yes. Missing images found in post content can be replaced with a configured placeholder image. This modifies `post_content`, and the original URL is saved in the scan result so the change can be reviewed or reversed.

### Does it scan page builder or widget content?
Yes. The content scanner reads Elementor/Divi-style post meta fields as well as classic and block sidebar widgets, not just standard post content.

### Does it scan external image URLs?
The free version focuses on internal WordPress uploads only. External URL validation is planned for a future Pro release.

### Does it support WooCommerce product images?
Not yet as a dedicated feature — general page/post scanning still covers WooCommerce content pages today. Dedicated WooCommerce product image scanning is planned for Pro.

### Can I export scan results to CSV?
Yes. Scan results can be exported as CSV from the dashboard or results page, with formula-injection protection built in.

### Are WP-CLI commands available?
Yes:
- `wp wpbmf scan` — Run a full scan
- `wp wpbmf summary` — View latest scan summary
- `wp wpbmf results --type=missing_image` — List results
- `wp wpbmf export --file=/path/report.csv` — Export to CSV
- `wp wpbmf clear --yes` — Clear all results

### Can developers extend the plugin?
Yes. Available hooks include:
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

## Roadmap

The following are planned for a future Pro release and are not available yet:

- Auto-repair moved media (match by filename, suggest URL swap)
- Cloud media support (Amazon S3, Cloudflare R2, Bunny CDN)
- WooCommerce product image scan
- Scheduled weekly scan with email summary
- External URL validation
- CDN media validation
- Bulk replacement rules
- Client-ready PDF reports

## Changelog

### 1.0.0 (2026-06-04)
- Initial release.
- Plugin scaffold with PSR-4 autoloader, constants, activation/deactivation hooks.
- Custom database table (`wpbmf_scan_results`) with dbDelta and 7 indexes.
- ScanRepository with full CRUD, status update, summary, and export methods.
- UrlExtractor: img src, srcset, upload link extraction, URL-to-path mapping.
- ContentScanner: scan post/page content, page builder meta, and widgets for missing local image files.
- AttachmentScanner: scan post/page content for broken document/file links.
- FeaturedImageScanner: detect posts/pages whose featured image file is missing.
- UnusedMediaScanner: list attachments not used in content or as featured images.
- ScanManager: orchestrate all scanners, generate scan ID, save scan history.
- DashboardPage: summary cards, actions bar, scan history table.
- ScanResultsPage: paginated results with 5 filters, badges, row actions.
- PlaceholderManager: resolve placeholder URL from settings or default SVG.
- MediaReplacer: replace missing image URL in post content with placeholder.
- CsvExporter: all result fields, formula injection protection, filter-aware.
- SettingsPage: tabbed UI for scan types, post types, placeholder, advanced.
- MediaColumns: Media Library scan status column per attachment.
- DashboardWidget: latest scan summary and quick links.
- AdminMenu: wires scan, export, status update, replace, clear actions.
- WP-CLI commands: scan, summary, results, export, clear.
- Developer hooks and filters throughout scan lifecycle.
- PHPUnit setup and tests for plugin load, repository, URL extractor, CSV exporter.
- Translation-ready with `broken-media-finder` text domain and 19 bundled language translations.
- Default `placeholder.svg` bundled.

## Security

Broken Media Finder is built with nonce verification, capability checks, prepared SQL statements, and sanitized/escaped input and output throughout, including CSV formula-injection protection on exports. The plugin never deletes media files automatically — it only reports and marks issues, leaving destructive actions to the site admin.

If you discover a security vulnerability, please do not disclose it publicly in a GitHub issue. Instead, report it privately to the maintainer at [smackcoders.com](https://smackcoders.com/) so it can be investigated and patched before public disclosure.

## Contributing

Contributions are welcome. If you'd like to report a bug, suggest a feature, or submit a pull request:

1. Check existing issues before opening a new one.
2. Fork the repository and create a feature branch.
3. Follow the existing code style and PSR-4 structure under `includes/`.
4. Add or update PHPUnit tests where relevant (`tests/`).
5. Submit a pull request describing the change and its motivation.

## Support

- **GitHub Issues** — for bugs, feature requests, and development discussion on this repository.
- **Official support** — visit [smackcoders.com](https://smackcoders.com/) for direct support from the plugin author.

## License

Licensed under the **GPLv2 or later**.
See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) for full license text.

Copyright © Smackcoders.

## Disclaimer

Broken Media Finder is an independent plugin developed by Smackcoders and is not affiliated with, endorsed by, or sponsored by WordPress.org, Automattic, WooCommerce, Elementor, Divi/Elegant Themes, Amazon Web Services, Cloudflare, or Bunny.net. All product names, logos, and brands mentioned (including planned Pro integrations) are property of their respective owners and are referenced solely to describe compatibility or planned functionality.

## Author / Maintainer

Developed and maintained by **[Smackcoders](https://smackcoders.com/)**.
