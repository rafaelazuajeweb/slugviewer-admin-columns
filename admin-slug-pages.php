<?php
/**
 * Plugin Name: Admin Slug Pages
 * Plugin URI:  https://celeryagency.com/
 * Description: Adds a sortable Slug column to the admin list tables for Posts, Pages, and Custom Post Types. Configure which post types show the column from Settings.
 * Version:     1.0.0
 * Author:      Celery Software LLC
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

function asp_activate() {
	if ( false === get_option( 'asp_enabled_post_types' ) ) {
		update_option( 'asp_enabled_post_types', array( 'post', 'page' ) );
	}
}
register_activation_hook( __FILE__, 'asp_activate' );

function asp_init() {
	require_once ASP_PLUGIN_DIR . 'includes/class-columns.php';
	require_once ASP_PLUGIN_DIR . 'includes/class-settings.php';

	new ASP_Columns();
	new ASP_Settings();
}
add_action( 'plugins_loaded', 'asp_init' );
