<?php
/**
 * Adds and renders the Slug column in supported admin list tables.
 *
 * @package SlugViewer_Admin_Columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage the Slug column for posts, pages, media, and public post types.
 */
class SlugViewer_Columns {

	/**
	 * Register the admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_columns' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Register column hooks for each enabled, visible post type.
	 *
	 * @return void
	 */
	public function register_columns() {
		$enabled_types   = slugviewer_get_enabled_post_types();
		$available_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);
		$enabled_types   = array_intersect( $enabled_types, $available_types );

		if ( empty( $enabled_types ) ) {
			return;
		}

		foreach ( $enabled_types as $post_type ) {
			if ( 'attachment' === $post_type ) {
				add_filter( 'manage_media_columns', array( $this, 'add_column' ) );
				add_action( 'manage_media_custom_column', array( $this, 'render_column' ), 10, 2 );
				add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable_column' ) );
			} elseif ( 'page' === $post_type ) {
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
	 * Insert the Slug column after the title column.
	 *
	 * @param string[] $columns Existing columns.
	 * @return string[] Columns with the Slug column included.
	 */
	public function add_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['slugviewer_slug'] = esc_html__( 'Slug', 'slugviewer-admin-columns' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render a post slug in the custom column.
	 *
	 * @param string $column  Current column key.
	 * @param int    $post_id Current post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 'slugviewer_slug' !== $column ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$classes = array( 'slugviewer-slug' );

		if ( in_array( $post->post_status, array( 'draft', 'pending', 'future' ), true ) ) {
			$classes[] = 'slugviewer-slug--unpublished';
		}

		printf(
			'<code class="%1$s">%2$s</code>',
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $post->post_name )
		);
	}

	/**
	 * Mark the Slug column as sortable.
	 *
	 * @param string[] $columns Sortable columns.
	 * @return string[] Updated sortable columns.
	 */
	public function sortable_column( $columns ) {
		$columns['slugviewer_slug'] = 'slugviewer_slug';
		return $columns;
	}

	/**
	 * Sort the current admin list query by post slug.
	 *
	 * @param WP_Query $query Current query instance.
	 * @return void
	 */
	public function handle_sort( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'slugviewer_slug' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'post_name' );
		}
	}

	/**
	 * Load the small column stylesheet only on supported list screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'edit.php', 'upload.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, slugviewer_get_enabled_post_types(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'slugviewer-admin-columns',
			SLUGVIEWER_PLUGIN_URL . 'assets/css/admin-columns.css',
			array(),
			SLUGVIEWER_VERSION
		);
	}
}
