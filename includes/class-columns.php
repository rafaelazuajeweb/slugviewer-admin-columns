<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASP_Columns {

	private $enabled_types;

	public function __construct() {
		$this->enabled_types = get_option( 'asp_enabled_post_types', array( 'post', 'page' ) );

		if ( empty( $this->enabled_types ) ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'register_columns' ) );
	}

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

	public function sortable_column( $columns ) {
		$columns['asp_slug'] = 'asp_slug';
		return $columns;
	}

	public function handle_sort( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'asp_slug' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'post_name' );
		}
	}
}
