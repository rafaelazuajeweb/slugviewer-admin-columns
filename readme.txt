=== SlugViewer – Slugs in Admin Columns ===
Contributors: celerysoftware
Tags: slug, admin columns, post slug, page slug, custom post types
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show sortable slug columns for posts, pages, media, and custom post types, with simple per-type controls.

== Description ==

**SlugViewer** adds a clean, sortable "Slug" column to your WordPress admin list tables — for Posts, Pages, Media, and any Custom Post Type on your site.

**Key Features:**

* Slug column for Posts, Pages, and all Custom Post Types
* Per-post-type control — enable or disable individually from Settings
* Auto-detects all public post types including WooCommerce Products, Portfolios, Events, etc.
* Sortable column — click to sort alphabetically by slug
* Draft/Pending/Scheduled posts show greyed-out slugs
* Appears in Screen Options for per-user visibility control
* Clean settings page in the SlugViewer menu
* Lightweight — no JavaScript frameworks, no external API calls, no tracking
* Zero frontend impact — runs only in wp-admin

**Why This Plugin?**

SlugViewer gives you per-post-type control with a clean interface and no unnecessary overhead.

**Perfect For:**

* SEO professionals auditing URL structures
* Content managers organizing large sites
* Developers working with Custom Post Types
* Agencies managing client WordPress sites

== Installation ==

1. Upload the `slugviewer-admin-columns` directory to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Open SlugViewer in the admin menu to configure which post types show the Slug column
4. Posts and Pages are enabled by default

== Frequently Asked Questions ==

= Does this work with Custom Post Types? =

Yes! The plugin auto-detects all public post types on your site, including WooCommerce Products, Portfolios, Events, and any other registered CPT. Enable them individually from the settings page.

= Will this appear in Screen Options? =

Yes. Once enabled for a post type, the Slug column appears as a checkbox in Screen Options, just like the built-in Author or Date columns. Each user can show/hide it independently.

= Does this affect my site's frontend performance? =

No. The plugin only loads in the WordPress admin area. It adds zero code, styles, or scripts to the frontend.

= What happens if I uninstall the plugin? =

All plugin settings are cleanly removed from the database. Your posts and content are never modified.

= Can I sort by slug? =

Yes! Click the Slug column header to sort alphabetically, just like sorting by Title or Date.

== Screenshots ==

1. Slug column displayed in the Pages list with sortable header
2. Slug column displayed in the Posts list
3. Slug checkbox in Screen Options panel
4. Settings page with per-post-type toggle switches

== Changelog ==

= 1.0.2 =
* Renamed the plugin to SlugViewer – Slugs in Admin Columns
* Updated the plugin slug, text domain, and identifiers for WordPress.org review
* Added automatic migration from previous option names

= 1.0.1 =
* Improved compatibility with WordPress coding standards
* Added working Slug columns for the Media list screen
* Improved accessibility of post type toggles
* Fixed draft, pending, and scheduled slug styling

= 1.0.0 =
* Initial release
* Sortable Slug column for Posts, Pages, and Custom Post Types
* Per-post-type enable/disable from Settings
* Auto-detection of all public post types
* Draft/Pending/Scheduled slug greyed-out display
* Clean settings page
* Screen Options integration
* Clean uninstall
