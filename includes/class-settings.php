<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASP_Settings {

	private $page_hook;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function add_menu() {
		$this->page_hook = add_options_page(
			__( 'Admin Slug Pages', 'admin-slug-pages' ),
			__( 'Admin Slug Pages', 'admin-slug-pages' ),
			'manage_options',
			'admin-slug-pages',
			array( $this, 'render_page' )
		);
	}

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

	private function get_public_post_types() {
		return get_post_types( array( 'public' => true ), 'objects' );
	}

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
