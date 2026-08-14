<?php
/**
 * Plugin Name: SlugViewer – Slugs in Admin Columns
 * Description: Show sortable slug columns for posts, pages, media, and custom post types, with simple per-type controls.
 * Version:     1.0.2
 * Author:      Celery Software LLC
 * Author URI:  https://celeryagency.com/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: slugviewer-admin-columns
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package SlugViewer_Admin_Columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SLUGVIEWER_VERSION', '1.0.2' );
define( 'SLUGVIEWER_PLUGIN_FILE', __FILE__ );
define( 'SLUGVIEWER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SLUGVIEWER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Return the post types enabled for the Slug column.
 *
 * Previous option names are migrated automatically for existing installs.
 *
 * @return string[] Enabled post type slugs.
 */
function slugviewer_get_enabled_post_types() {
	$enabled_types = get_option( 'slugviewer_enabled_post_types', false );

	if ( false === $enabled_types ) {
		$enabled_types = get_option( 'casv_enabled_post_types', false );
	}

	if ( false === $enabled_types ) {
		$enabled_types = get_option( 'asp_enabled_post_types', false );
	}

	if ( false === get_option( 'slugviewer_enabled_post_types', false ) ) {
		$enabled_types = is_array( $enabled_types ) ? $enabled_types : array( 'post', 'page' );
		$enabled_types = array_values( array_unique( array_map( 'sanitize_key', $enabled_types ) ) );

		add_option( 'slugviewer_enabled_post_types', $enabled_types );
	}

	return is_array( $enabled_types ) ? $enabled_types : array( 'post', 'page' );
}

/**
 * Set the plugin defaults on activation.
 *
 * @return void
 */
function slugviewer_activate() {
	slugviewer_get_enabled_post_types();
}
register_activation_hook( __FILE__, 'slugviewer_activate' );

/**
 * Load the plugin in the WordPress administration area.
 *
 * @return void
 */
function slugviewer_init() {
	if ( ! is_admin() ) {
		return;
	}

	require_once SLUGVIEWER_PLUGIN_DIR . 'includes/class-slugviewer-columns.php';
	require_once SLUGVIEWER_PLUGIN_DIR . 'includes/class-slugviewer-settings.php';

	new SlugViewer_Columns();
	new SlugViewer_Settings();
}
add_action( 'plugins_loaded', 'slugviewer_init' );
