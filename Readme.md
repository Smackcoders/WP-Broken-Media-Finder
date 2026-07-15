# Broken Media Finder

Finds broken images, dead attachment links, missing featured images, and unused media across your site.

![License](https://img.shields.io/badge/license-GPLv2%20or%20later-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-21759B.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration & Setup](#configuration--setup)
- [Usage](#usage)
- [Supported Integrations](#supported-integrations)
- [Screenshots](#screenshots)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Disclaimer](#disclaimer)
- [Author](#author)

## Overview

Broken Media Finder is a media audit plugin for WordPress admins, agencies, bloggers, and WooCommerce store owners who need to know when a media library has quietly broken. Migrations, theme switches, and routine content edits leave behind dead links and orphaned files more often than most site owners realize — the industry calls this link rot, and it usually surfaces only after a visitor, or Google, trips over it first.

The plugin works as a content scanner: it walks posts, pages, reusable blocks, custom fields (including data stored by page builders like Elementor and Divi), and sidebar widgets, checking every image and attachment reference it finds. Local files are checked against disk; images hosted elsewhere are checked with a live HTTP request, so a broken external image URL gets caught the same way a missing local file does. Results land in a single dashboard where they can be filtered, exported to CSV, replaced with a placeholder, or driven entirely through WP-CLI for scheduled audits.

Broken Media Finder only reports and marks issues — it never deletes anything on its own. Cleanup stays a deliberate, reviewable step for whoever runs the scan.

## Key Features

- **Broken image & empty src detection** — finds `<img>` tags with missing or empty `src` attributes, plus image URLs that no longer resolve, whether the file lives on your server or off-site
- **HTTP-based external link checking** — off-site image URLs are validated with a live request that distinguishes 404s, 410s, DNS failures, and timeouts, rather than being skipped
- **Attachment link scanner** — flags broken links to PDFs, ZIPs, and other non-image files stored in your uploads directory
- **Missing featured image detection** — flags posts and pages whose featured image attachment file no longer exists on disk
- **Unused media scanner** — lists attachments not used as a featured image or referenced in scanned post/page content, so storage doesn't quietly fill up with orphaned files
- **Page builder & widget content scanning** — reads custom field values (including Elementor- and Divi-style post meta) and classic, custom HTML, media image, and block sidebar widgets, not just the main post content; reusable blocks are scanned automatically alongside your selected post types
- **One-click placeholder replacement** — swaps a broken image, missing featured image, or dead attachment link for a configurable placeholder, while keeping the original URL on record in the scan result
- **CSV export of scan results** — exports full scan data with formula-injection protection built in
- **Scan history admin page** — retains up to 100 past scans with issue counts for before/after comparison
- **Dashboard, results, settings, and help admin pages** — a dedicated top-level "Broken Media Finder" menu with summary cards, filterable results (issue type, severity, status, search), a tabbed settings screen, and a help/support screen
- **Media Library scan status column** — shows Used, Unused, Missing file, or Not scanned status per attachment right in the Media Library
- **Dashboard widget** — surfaces the latest scan summary on the WordPress dashboard without opening the plugin
- **WP-CLI scan commands** — `scan`, `summary`, `results`, `export`, and `clear` commands for automation and cron-driven audits
- **Developer hooks across the scan lifecycle** — before/after scan, scan failed, result inserted, and placeholder applied actions, plus filters for post types, scan types, query args, and CSV output
- **Translation-ready plugin** — built with a proper text domain and shipped with 19 bundled language translations
- **Security hardened** — nonces, capability checks, prepared SQL statements, and sanitized/escaped output throughout

## Use Cases

- **Post-migration site audits** — agencies and developers verifying that images and attachment links survived a domain, host, or CMS migration
- **Pre-launch or pre-redesign checks** — catching missing images and broken links before a redesign or relaunch goes live
- **Ongoing media library cleanup** — content managers and SEO teams periodically clearing unused media and orphaned attachments to keep storage lean
- **Theme or template changes** — finding images that broke after a theme switch or template restructuring
- **Client maintenance retainers** — support teams running a quick broken-media scan as part of routine site health checks
- **SEO content audits** — surfacing broken images and dead links that could weigh on page quality or crawl signals

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 5.0 or later |
| PHP | 7.4 or later |
| External services | None — scanning runs entirely against your own database and uploads directory |

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

## Configuration & Setup

1. After activation, open the **Broken Media Finder** menu in the WordPress admin sidebar and run your first scan from the Dashboard.
2. Go to **Broken Media Finder → Settings** to choose which scan types and post types run, and to set the placeholder image used for replacements.
3. Use the Settings screen's Advanced tab to toggle the dashboard summary widget, decide whether previous scan results are kept, and control whether plugin data is removed on uninstall — media files themselves are never deleted, uninstall only clears the plugin's own database table and options if you opt in.
4. Open the **Help & Support** screen inside the plugin for a quick reference of available features and WP-CLI commands.

## Usage

1. From the Dashboard, click the manual scan button to check your site for missing images, broken attachment links, missing featured images, and unused media.
2. Review results on the Scan Results page — filter by issue type, severity, or status, and search for specific items.
3. Mark individual results as **Ignored** or **Fixed**, or replace a broken image, missing featured image, or dead attachment link with your configured placeholder directly from the results table.
4. Check the Media Library's scan status column at any time to see whether an attachment is Used, Unused, or has a missing file.
5. Export the current results to CSV for a client report or further review.
6. Automate scans on a schedule using cron and the WP-CLI commands below:

   ```bash
   wp wpbmf scan
   wp wpbmf summary
   wp wpbmf results --type=missing_image
   wp wpbmf export --file=/path/report.csv
   wp wpbmf clear --yes
   ```

## Supported Integrations

- **WP-CLI** — full command-line support for scanning, exporting, and clearing results
- **Page builder content** — scans Elementor- and Divi-style post meta fields in addition to standard post content
- **Classic & block widgets** — scans text, custom HTML, media image, and block widgets in active sidebars
- **WooCommerce sites** — safe to run on WooCommerce stores today; dedicated product image scanning is planned for a future Pro release (see Roadmap)

## Screenshots

Screenshots of the Dashboard, Scan Results page, Settings screen, and Media Library status column are available on the plugin's WordPress.org listing.

## Documentation

In-plugin documentation is available from the **Help & Support** page inside the **Broken Media Finder** menu after activation, covering feature usage and the full WP-CLI command reference. Developer hooks and filters are documented inline in the plugin source under `includes/`.

## FAQ

### Does this plugin delete unused media automatically?

No. It only lists and marks unused media as part of the cleanup workflow; deleting files is always a manual step. Review results carefully before deleting — unused-media detection checks featured images and content in scanned post types, so an attachment referenced only through a widget or a page-builder field can be flagged as unused even though it's still in use.

### Can it replace missing images?

Yes. Broken or missing images in post content, missing featured images, and broken attachment links can each be replaced with a configured placeholder. The original URL stays on record in the scan result alongside the replacement, so the change can be reviewed or manually reversed.

### Does it scan page builder or widget content?

Yes. The content scanner reads custom field values — including data stored by builders like Elementor and Divi — as well as classic, custom HTML, media image, and block sidebar widgets, not just standard post content.

### Does it scan external image URLs?

Yes. Image URLs found in content that don't point to your own uploads directory are checked with a live HTTP request, which catches 404s, 410s, timeouts, and DNS failures rather than skipping them. Non-image attachment links, such as PDFs or ZIPs, are currently checked only when they point to your own uploads directory; broader external link validation for those file types is planned for Pro.

### Does it support WooCommerce product images?

Not yet as a dedicated feature — general page and post scanning already covers WooCommerce content pages. Dedicated WooCommerce product image scanning is planned for Pro.

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
- Cloud media support (AWS S3, Google Cloud, and DigitalOcean Spaces)
- WooCommerce product image scan
- Scheduled weekly scan with email summary
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
- ContentScanner: scan post/page content, page builder meta, and widgets for missing local and broken external image URLs.
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

Broken Media Finder is built with nonce verification, capability checks, prepared SQL statements, and sanitized/escaped input and output throughout, including CSV formula-injection protection on exports. The plugin never deletes media files automatically — it only reports and marks issues, leaving destructive actions to the site admin. An optional "Delete data on uninstall" setting removes the plugin's own database table and options when uninstalled; it does not touch your media library either way.

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

Broken Media Finder is an independent plugin developed by Smackcoders and is not affiliated with, endorsed by, or sponsored by WordPress.org, Automattic, WooCommerce, Elementor, Divi/Elegant Themes, Amazon Web Services, Google Cloud, or DigitalOcean. All product names, logos, and brands mentioned (including planned Pro integrations) are property of their respective owners and are referenced solely to describe compatibility or planned functionality.

## Author

Developed and maintained by **[Smackcoders](https://smackcoders.com/)**.
