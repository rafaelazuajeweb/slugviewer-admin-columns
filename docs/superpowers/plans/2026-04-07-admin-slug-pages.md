# Admin Slug Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a WordPress plugin that adds a sortable, toggleable Slug column to admin list tables for Posts, Pages, and Custom Post Types, with a branded settings page.

**Architecture:** Single-option plugin with two core classes — `ASP_Columns` handles column registration/display per post type, `ASP_Settings` handles the settings page under Settings menu. Main bootstrap file loads classes, sets activation defaults, and registers the uninstall hook. No frontend code, no JS frameworks, no external dependencies.

**Tech Stack:** PHP 7.4+, WordPress 5.8+ (Settings API, admin column hooks), pure CSS for settings styling.

---

## File Structure

| File | Responsibility |
|---|---|
| `admin-slug-pages.php` | Plugin bootstrap: header, constants, activation hook, class loading |
| `includes/class-columns.php` | Column registration, display, and sorting for all enabled post types |
| `includes/class-settings.php` | Settings page: menu registration, form rendering, settings save via WP Settings API |
| `assets/css/admin-settings.css` | CeleryLinks-branded styles for settings page only |
| `uninstall.php` | Clean removal of plugin options on uninstall |
| `readme.txt` | WordPress.org plugin listing |
| `LICENSE` | GPLv2+ license text |

---

### Task 1: Plugin Bootstrap

**Files:**
- Create: `admin-slug-pages.php`

- [ ] **Step 1: Create the main plugin file with header and constants**

```php
<?php
/**
 * Plugin Name: Admin Slug Pages
 * Plugin URI:  https://celeryagency.com/
 * Description: Adds a sortable Slug column to the admin list tables for Posts, Pages, and Custom Post Types. Configure which post types show the column from Settings.
 * Version:     1.0.0
 * Author:      Celery Software
 * Author URI:  https://celeryagency.com/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: admin-slug-pages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASP_VERSION', '1.0.0' );
define( 'ASP_PLUGIN_FILE', __FILE__ );
define( 'ASP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Set default options on activation.
 */
function asp_activate() {
	if ( false === get_option( 'asp_enabled_post_types' ) ) {
		update_option( 'asp_enabled_post_types', array( 'post', 'page' ) );
	}
}
register_activation_hook( __FILE__, 'asp_activate' );

/**
 * Load plugin classes after WordPress is fully loaded.
 */
function asp_init() {
	require_once ASP_PLUGIN_DIR . 'includes/class-columns.php';
	require_once ASP_PLUGIN_DIR . 'includes/class-settings.php';

	new ASP_Columns();
	new ASP_Settings();
}
add_action( 'plugins_loaded', 'asp_init' );
```

- [ ] **Step 2: Verify file structure**

Run: `ls -la admin-slug-pages.php`
Expected: file exists with correct content.

- [ ] **Step 3: Commit**

```bash
git add admin-slug-pages.php
git commit -m "feat: add plugin bootstrap with activation defaults"
```

---

### Task 2: Column Registration and Display

**Files:**
- Create: `includes/class-columns.php`

- [ ] **Step 1: Create the includes directory**

```bash
mkdir -p includes
```

- [ ] **Step 2: Create the ASP_Columns class**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASP_Columns {

	/**
	 * Enabled post types from settings.
	 *
	 * @var array
	 */
	private $enabled_types;

	public function __construct() {
		$this->enabled_types = get_option( 'asp_enabled_post_types', array( 'post', 'page' ) );

		if ( empty( $this->enabled_types ) ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'register_columns' ) );
	}

	/**
	 * Register column hooks for each enabled post type.
	 */
	public function register_columns() {
		foreach ( $this->enabled_types as $post_type ) {
			if ( 'page' === $post_type ) {
				add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
				add_action( 'manage_pages_custom_column', array( $this, 'render_column' ), 10, 2 );
				add_filter( 'manage_edit-page_sortable_columns', array( $this, 'sortable_column' ) );
			} else {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_column' ) );
			}
		}

		add_action( 'pre_get_posts', array( $this, 'handle_sort' ) );
	}

	/**
	 * Insert the Slug column after the Title column.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['asp_slug'] = esc_html__( 'Slug', 'admin-slug-pages' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render the slug value for each row.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		if ( 'asp_slug' !== $column ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$slug  = $post->post_name;
		$style = '';

		if ( in_array( $post->post_status, array( 'draft', 'pending', 'future' ), true ) ) {
			$style = ' style="opacity: 0.5;"';
		}

		printf( '<code%s>%s</code>', $style, esc_html( $slug ) );
	}

	/**
	 * Make the Slug column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_column( $columns ) {
		$columns['asp_slug'] = 'asp_slug';
		return $columns;
	}

	/**
	 * Handle sorting by post_name when our column is the orderby target.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function handle_sort( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'asp_slug' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'post_name' );
		}
	}
}
```

- [ ] **Step 3: Verify class loads without errors**

Activate the plugin in a WordPress test environment. Navigate to Posts and Pages — the Slug column should appear after Title.

- [ ] **Step 4: Commit**

```bash
git add includes/class-columns.php
git commit -m "feat: add slug column with sorting and draft display"
```

---

### Task 3: Settings Page

**Files:**
- Create: `includes/class-settings.php`

- [ ] **Step 1: Create the ASP_Settings class**

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASP_Settings {

	/**
	 * The settings page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_menu() {
		$this->page_hook = add_options_page(
			__( 'Admin Slug Pages', 'admin-slug-pages' ),
			__( 'Admin Slug Pages', 'admin-slug-pages' ),
			'manage_options',
			'admin-slug-pages',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the plugin setting with sanitization.
	 */
	public function register_settings() {
		register_setting(
			'asp_settings_group',
			'asp_enabled_post_types',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => array( 'post', 'page' ),
			)
		);
	}

	/**
	 * Sanitize the submitted post types array.
	 *
	 * @param mixed $input Raw input from form.
	 * @return array Sanitized array of post type slugs.
	 */
	public function sanitize_post_types( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$valid_types = $this->get_public_post_types();
		$valid_slugs = array_keys( $valid_types );

		return array_values( array_intersect(
			array_map( 'sanitize_key', $input ),
			$valid_slugs
		) );
	}

	/**
	 * Load CSS only on this plugin's settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( $this->page_hook !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'asp-admin-settings',
			ASP_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			ASP_VERSION
		);
	}

	/**
	 * Get all public post types as slug => object pairs.
	 *
	 * @return WP_Post_Type[] Associative array of post type objects.
	 */
	private function get_public_post_types() {
		return get_post_types( array( 'public' => true ), 'objects' );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types    = $this->get_public_post_types();
		$enabled_types = get_option( 'asp_enabled_post_types', array( 'post', 'page' ) );

		?>
		<div class="asp-wrap">
			<div class="asp-header">
				<h1><?php esc_html_e( 'Admin Slug Pages', 'admin-slug-pages' ); ?></h1>
				<span class="asp-version"><?php echo esc_html( 'v' . ASP_VERSION ); ?></span>
			</div>
			<p class="asp-description">
				<?php esc_html_e( 'Display a sortable Slug column in your admin list tables. Enable it for each post type below.', 'admin-slug-pages' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'asp_settings_group' ); ?>

				<div class="asp-card">
					<h2 class="asp-card-title"><?php esc_html_e( 'Post Types', 'admin-slug-pages' ); ?></h2>

					<?php foreach ( $post_types as $slug => $type_obj ) :
						$count    = wp_count_posts( $slug );
						$total    = isset( $count->publish ) ? (int) $count->publish : 0;
						$checked  = in_array( $slug, $enabled_types, true );
						$dashicon = '';
						if ( ! empty( $type_obj->menu_icon ) && strpos( $type_obj->menu_icon, 'dashicons-' ) === 0 ) {
							$dashicon = $type_obj->menu_icon;
						} elseif ( 'post' === $slug ) {
							$dashicon = 'dashicons-admin-post';
						} elseif ( 'page' === $slug ) {
							$dashicon = 'dashicons-admin-page';
						} else {
							$dashicon = 'dashicons-admin-generic';
						}
					?>
						<div class="asp-post-type-row">
							<div class="asp-post-type-info">
								<span class="dashicons <?php echo esc_attr( $dashicon ); ?>"></span>
								<span class="asp-post-type-label"><?php echo esc_html( $type_obj->labels->name ); ?></span>
								<span class="asp-post-type-count"><?php echo esc_html( $total ); ?></span>
							</div>
							<label class="asp-toggle">
								<input
									type="checkbox"
									name="asp_enabled_post_types[]"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( $checked ); ?>
								/>
								<span class="asp-toggle-slider"></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Changes', 'admin-slug-pages' ), 'asp-save-btn', 'submit', false ); ?>
			</form>

			<div class="asp-footer">
				<p>
					<?php
					printf(
						/* translators: %s: link to Celery Agency */
						esc_html__( 'Made by %s', 'admin-slug-pages' ),
						'<a href="https://celeryagency.com/" target="_blank" rel="noopener noreferrer">Celery Software</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
```

- [ ] **Step 2: Verify settings page loads**

Navigate to Settings > Admin Slug Pages. The page should render with all public post types listed as toggleable rows.

- [ ] **Step 3: Test save and reload**

Toggle a post type off, click Save Changes, reload. The checkbox state should persist. Navigate to the post type list — the Slug column should be gone for disabled types and present for enabled types.

- [ ] **Step 4: Commit**

```bash
git add includes/class-settings.php
git commit -m "feat: add settings page with per-post-type toggles"
```

---

### Task 4: Settings Page CSS

**Files:**
- Create: `assets/css/admin-settings.css`

- [ ] **Step 1: Create the assets directory**

```bash
mkdir -p assets/css
```

- [ ] **Step 2: Create the CeleryLinks-branded stylesheet**

```css
/* Admin Slug Pages - Settings Page Styles */

.asp-wrap {
	max-width: 680px;
	margin: 20px 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
}

.asp-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 4px;
}

.asp-header h1 {
	font-size: 24px;
	font-weight: 600;
	color: #1A202C;
	margin: 0;
	padding: 0;
}

.asp-version {
	font-size: 12px;
	color: #64748B;
	background: #F0FDFA;
	border: 1px solid #0DB1B4;
	border-radius: 12px;
	padding: 2px 10px;
	font-weight: 500;
}

.asp-description {
	color: #64748B;
	font-size: 14px;
	margin: 0 0 24px 0;
}

/* Card */
.asp-card {
	background: #FFFFFF;
	border: 1px solid #E2E8F0;
	border-radius: 8px;
	padding: 0;
	margin-bottom: 20px;
}

.asp-card-title {
	font-size: 14px;
	font-weight: 600;
	color: #64748B;
	text-transform: uppercase;
	letter-spacing: 0.05em;
	padding: 16px 20px 12px;
	margin: 0;
	border-bottom: 1px solid #E2E8F0;
}

/* Post type rows */
.asp-post-type-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 14px 20px;
	border-bottom: 1px solid #F1F5F9;
}

.asp-post-type-row:last-child {
	border-bottom: none;
}

.asp-post-type-info {
	display: flex;
	align-items: center;
	gap: 10px;
}

.asp-post-type-info .dashicons {
	color: #0DB1B4;
	font-size: 20px;
	width: 20px;
	height: 20px;
}

.asp-post-type-label {
	font-size: 14px;
	font-weight: 500;
	color: #1A202C;
}

.asp-post-type-count {
	font-size: 12px;
	color: #94A3B8;
	background: #F1F5F9;
	border-radius: 10px;
	padding: 1px 8px;
}

/* Toggle switch - pure CSS */
.asp-toggle {
	position: relative;
	display: inline-block;
	width: 44px;
	height: 24px;
	flex-shrink: 0;
}

.asp-toggle input {
	opacity: 0;
	width: 0;
	height: 0;
}

.asp-toggle-slider {
	position: absolute;
	cursor: pointer;
	inset: 0;
	background-color: #CBD5E1;
	border-radius: 24px;
	transition: background-color 0.2s ease;
}

.asp-toggle-slider::before {
	content: "";
	position: absolute;
	height: 18px;
	width: 18px;
	left: 3px;
	bottom: 3px;
	background-color: #FFFFFF;
	border-radius: 50%;
	transition: transform 0.2s ease;
}

.asp-toggle input:checked + .asp-toggle-slider {
	background-color: #0DB1B4;
}

.asp-toggle input:checked + .asp-toggle-slider::before {
	transform: translateX(20px);
}

.asp-toggle input:focus + .asp-toggle-slider {
	box-shadow: 0 0 0 2px rgba(13, 177, 180, 0.25);
}

/* Save button */
.asp-save-btn {
	background-color: #0DB1B4 !important;
	border-color: #0A9A9D !important;
	color: #FFFFFF !important;
	border-radius: 6px !important;
	padding: 6px 24px !important;
	font-size: 14px !important;
	font-weight: 500 !important;
	height: auto !important;
	line-height: 1.6 !important;
	cursor: pointer;
	transition: background-color 0.15s ease;
}

.asp-save-btn:hover,
.asp-save-btn:focus {
	background-color: #0A9A9D !important;
}

/* Footer */
.asp-footer {
	text-align: center;
	padding: 16px 0;
}

.asp-footer p {
	color: #94A3B8;
	font-size: 13px;
	margin: 0;
}

.asp-footer a {
	color: #0DB1B4;
	text-decoration: none;
}

.asp-footer a:hover {
	text-decoration: underline;
}
```

- [ ] **Step 3: Verify styles render correctly**

Navigate to Settings > Admin Slug Pages. Verify: teal toggle switches, white card with border, version badge, proper spacing, branded save button.

- [ ] **Step 4: Commit**

```bash
git add assets/css/admin-settings.css
git commit -m "feat: add CeleryLinks-branded settings page styles"
```

---

### Task 5: Uninstall Handler

**Files:**
- Create: `uninstall.php`

- [ ] **Step 1: Create the uninstall file**

```php
<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'asp_enabled_post_types' );
```

- [ ] **Step 2: Commit**

```bash
git add uninstall.php
git commit -m "feat: add clean uninstall handler"
```

---

### Task 6: WordPress.org Readme and License

**Files:**
- Create: `readme.txt`
- Create: `LICENSE`

- [ ] **Step 1: Create the readme.txt**

```
=== Admin Slug Pages ===
Contributors: celerysoftware
Tags: slug, admin columns, post slug, page slug, custom post types
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a sortable Slug column to the admin list tables for Posts, Pages, and Custom Post Types.

== Description ==

**Admin Slug Pages** adds a clean, sortable "Slug" column to your WordPress admin list tables — for Posts, Pages, and any Custom Post Type on your site.

**Key Features:**

* Slug column for Posts, Pages, and all Custom Post Types
* Per-post-type control — enable or disable individually from Settings
* Auto-detects all public post types including WooCommerce Products, Portfolios, Events, etc.
* Sortable column — click to sort alphabetically by slug
* Draft/Pending/Scheduled posts show greyed-out slugs
* Appears in Screen Options for per-user visibility control
* Clean, branded settings page under Settings > Admin Slug Pages
* Lightweight — no JavaScript frameworks, no external API calls, no tracking
* Zero frontend impact — runs only in wp-admin

**Why This Plugin?**

Most slug column plugins either lack a settings page or are bloated with unnecessary features and telemetry. Admin Slug Pages gives you the control you need with a clean interface and zero overhead.

**Perfect For:**

* SEO professionals auditing URL structures
* Content managers organizing large sites
* Developers working with Custom Post Types
* Agencies managing client WordPress sites

== Installation ==

1. Upload the `admin-slug-pages` directory to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Go to Settings > Admin Slug Pages to configure which post types show the Slug column
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
2. Settings page with per-post-type toggle switches
3. Slug column in Screen Options checkbox

== Changelog ==

= 1.0.0 =
* Initial release
* Sortable Slug column for Posts, Pages, and Custom Post Types
* Per-post-type enable/disable from Settings
* Auto-detection of all public post types
* Draft/Pending/Scheduled slug greyed-out display
* CeleryLinks-branded settings page
* Screen Options integration
* Clean uninstall
```

- [ ] **Step 2: Create the LICENSE file**

Download the standard GPLv2 license text or create it with the standard preamble.

```bash
curl -sL "https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt" -o LICENSE
```

If curl is unavailable, create the file with the standard GPLv2 header:

```
GNU GENERAL PUBLIC LICENSE
Version 2, June 1991

Copyright (C) 1989, 1991 Free Software Foundation, Inc.
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA

Everyone is permitted to copy and distribute verbatim copies
of this license document, but changing it is not allowed.
```

- [ ] **Step 3: Commit**

```bash
git add readme.txt LICENSE
git commit -m "feat: add WordPress.org readme and GPLv2 license"
```

---

### Task 7: Final Integration Verification

- [ ] **Step 1: Verify full file structure**

```bash
find . -type f -not -path './.git/*' | sort
```

Expected output:
```
./admin-slug-pages.php
./assets/css/admin-settings.css
./includes/class-columns.php
./includes/class-settings.php
./LICENSE
./readme.txt
./uninstall.php
```

- [ ] **Step 2: Verify no PHP syntax errors**

```bash
php -l admin-slug-pages.php
php -l includes/class-columns.php
php -l includes/class-settings.php
php -l uninstall.php
```

Expected: `No syntax errors detected` for each file.

- [ ] **Step 3: Manual testing checklist**

1. Activate plugin — no errors
2. Navigate to Posts list — Slug column appears after Title
3. Navigate to Pages list — Slug column appears after Title
4. Click Slug column header — sorting works (A-Z, Z-A)
5. Open Screen Options — "Slug" checkbox is present and toggleable
6. Create a Draft post — slug shows greyed out
7. Navigate to Settings > Admin Slug Pages — page loads with proper styling
8. Disable Posts toggle, Save — Slug column disappears from Posts list
9. Re-enable, Save — column reappears
10. Deactivate + Delete plugin — `asp_enabled_post_types` option is removed from database

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "chore: final integration verification complete"
```
