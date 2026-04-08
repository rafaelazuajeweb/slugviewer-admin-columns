# Admin Slug Pages - Design Spec

## Overview

A lightweight WordPress plugin that adds a toggleable "Slug" column to admin list tables for Posts, Pages, and any registered Custom Post Types. Users control which post types display the column via a clean, CeleryLinks-branded settings page under **Settings > Admin Slug Pages**.

## Core Features (v1)

### 1. Slug Column

- Adds a **"Slug"** column to admin list tables for enabled post types
- Displays slug inside a `<code>` tag for readability
- **Draft/Pending/Scheduled posts**: slug shown greyed out (not yet a live URL)
- **Published posts**: slug shown in normal weight
- Column inserted **after the Title column**
- Column is **sortable** alphabetically by slug
- Appears in **Screen Options** as a toggleable checkbox (native WP behavior when columns are registered properly)

### 2. Auto-Detection of Post Types

- Uses `get_post_types(['public' => true], 'objects')` to discover all public post types
- Catches: `post`, `page`, and any CPT (`product`, `portfolio`, `event`, etc.)
- Displays each detected post type with its label on the settings page
- **Default on activation**: Posts and Pages enabled. CPTs disabled until explicitly enabled.

### 3. Settings Page

**Location**: `Settings > Admin Slug Pages`

**Design - Light CeleryLinks theme:**
- Primary: `#0DB1B4` (teal)
- Background: `#F7FAFA` (light teal-tinted gray)
- Cards: `#FFFFFF` with `1px solid #E2E8F0` border
- Text: `#1A202C` (near-black)
- Secondary text: `#64748B` (slate gray)
- Success states: `#0DB1B4`
- No external CSS frameworks

**Layout:**
- Plugin header: name + version + brief description
- Card listing all detected post types, each with:
  - Post type label (e.g., "Posts", "Pages", "Products")
  - Dashicon for the post type
  - Toggle switch (on/off) to enable slug column
  - Count of items of that type
- Save Changes button (teal styled)
- Uses WordPress Settings API (`register_setting`, `add_settings_section`)

**Performance:**
- CSS loaded only on the plugin's own settings page (page hook check in `admin_enqueue_scripts`)
- No JavaScript frameworks - pure CSS toggles
- No database queries beyond `get_option()` for settings
- Zero frontend impact - admin only

## Technical Architecture

```
admin-slug-pages/
├── admin-slug-pages.php          # Main plugin file (bootstrap, activation hook)
├── includes/
│   ├── class-settings.php        # Settings page registration & rendering
│   └── class-columns.php         # Column registration & display logic
├── assets/
│   └── css/
│       └── admin-settings.css    # Settings page styles (loaded only on settings page)
├── readme.txt                    # WordPress.org readme
├── LICENSE                       # GPLv2+
└── languages/
    └── admin-slug-pages.pot      # Translation template (future)
```

### Requirements
- PHP 7.4+
- WordPress 5.8+

### Coding Standards
- All functions prefixed `asp_` or in namespaced classes
- Text domain: `admin-slug-pages`
- WordPress Coding Standards compliant
- Proper escaping: `esc_html()`, `esc_attr()` on all output
- Nonce verification on settings save
- Capability check: `manage_options` for settings page

### Database
- Single option: `asp_enabled_post_types` (array of post type slugs)
- Default value set on activation: `['post', 'page']`
- Clean uninstall: option deleted on uninstall

### Screen Options Integration
- Registering columns via `manage_{post_type}_columns` filter automatically adds them to Screen Options
- Column visibility is per-user (stored in user meta by WP core)
- Plugin settings control availability; Screen Options controls per-user visibility

## WordPress.org Repository Requirements

- `readme.txt` in WP.org format (Stable tag, Description, FAQ, Changelog, Screenshots)
- Plugin header with: Plugin Name, Plugin URI, Description, Version, Author, Author URI, License, Text Domain
- GPLv2+ license
- No external API calls
- No tracking/telemetry
- No upsells
- Sanitization on all inputs
- Translation-ready

## Competitor Differentiation

| Feature | Admin Slug Column (5K installs) | Slug Search & Columns (60 installs) | Admin Slug Pages |
|---|---|---|---|
| Per-type toggle | No | Yes | Yes |
| Settings page | No | Yes (generic) | Yes (branded, clean) |
| Draft slug display | Yes (greyed) | No | Yes |
| Sortable | Yes | Yes | Yes |
| Telemetry | No | Yes (opt-in) | Never |
| Auto-detect CPTs | N/A (all auto) | Yes | Yes |

## Future v2 Ideas (NOT in v1)
- Search by slug in admin search box
- ID column toggle
- Full parent/child path display for hierarchical types
- User role permissions for column visibility
- Copy slug to clipboard button
- Bulk slug editor
- Custom column labels
