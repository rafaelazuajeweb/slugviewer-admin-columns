<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SlugViewer_Admin_Columns
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'slugviewer_enabled_post_types' );
delete_option( 'casv_enabled_post_types' );
delete_option( 'asp_enabled_post_types' );
