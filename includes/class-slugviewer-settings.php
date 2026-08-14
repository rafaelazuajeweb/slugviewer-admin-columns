<?php
/**
 * Registers and renders the plugin settings screen.
 *
 * @package SlugViewer_Admin_Columns
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage the SlugViewer settings.
 */
class SlugViewer_Settings {

	/**
	 * Settings page hook suffix.
	 *
	 * @var string|false
	 */
	private $page_hook;

	/**
	 * Register the admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SLUGVIEWER_PLUGIN_FILE ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Add the top-level settings page.
	 *
	 * @return void
	 */
	public function add_menu() {
		$this->page_hook = add_menu_page(
			__( 'SlugViewer – Slugs in Admin Columns', 'slugviewer-admin-columns' ),
			__( 'SlugViewer', 'slugviewer-admin-columns' ),
			'manage_options',
			'slugviewer-admin-columns',
			array( $this, 'render_page' ),
			'dashicons-admin-links',
			81
		);
	}

	/**
	 * Add a Settings shortcut to the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[] Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=slugviewer-admin-columns' ) ),
			esc_html__( 'Settings', 'slugviewer-admin-columns' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register the enabled post types option.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'slugviewer_settings_group',
			'slugviewer_enabled_post_types',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => array( 'post', 'page' ),
			)
		);
	}

	/**
	 * Keep only visible, public post type slugs.
	 *
	 * @param mixed $input Submitted option value.
	 * @return string[] Sanitized post type slugs.
	 */
	public function sanitize_post_types( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$valid_types = $this->get_public_post_types();
		$valid_slugs = array_keys( $valid_types );

		return array_values(
			array_intersect(
				array_map( 'sanitize_key', $input ),
				$valid_slugs
			)
		);
	}

	/**
	 * Load settings styles only on the plugin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( $this->page_hook !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'slugviewer-admin-settings',
			SLUGVIEWER_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			SLUGVIEWER_VERSION
		);
	}

	/**
	 * Return public post types that have an admin interface.
	 *
	 * @return WP_Post_Type[] Post type objects keyed by slug.
	 */
	private function get_public_post_types() {
		return get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types    = $this->get_public_post_types();
		$enabled_types = slugviewer_get_enabled_post_types();

		?>
		<div class="slugviewer-wrap">
			<div class="slugviewer-header">
				<h1><?php esc_html_e( 'SlugViewer', 'slugviewer-admin-columns' ); ?></h1>
				<span class="slugviewer-version"><?php echo esc_html( 'v' . SLUGVIEWER_VERSION ); ?></span>
			</div>
			<p class="slugviewer-description">
				<?php esc_html_e( 'Display a sortable Slug column in your admin list tables. Enable it for each post type below.', 'slugviewer-admin-columns' ); ?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'slugviewer_settings_group' ); ?>

				<div class="slugviewer-card">
					<h2 class="slugviewer-card-title"><?php esc_html_e( 'Post Types', 'slugviewer-admin-columns' ); ?></h2>

					<?php
					foreach ( $post_types as $slug => $type_obj ) :
						$count    = wp_count_posts( $slug );
						$total    = isset( $count->publish ) ? (int) $count->publish : 0;
						$checked  = in_array( $slug, $enabled_types, true );
						$dashicon = '';
						if ( ! empty( $type_obj->menu_icon ) && 0 === strpos( $type_obj->menu_icon, 'dashicons-' ) ) {
							$dashicon = $type_obj->menu_icon;
						} elseif ( 'post' === $slug ) {
							$dashicon = 'dashicons-admin-post';
						} elseif ( 'page' === $slug ) {
							$dashicon = 'dashicons-admin-page';
						} else {
							$dashicon = 'dashicons-admin-generic';
						}
						?>
						<div class="slugviewer-post-type-row">
							<div class="slugviewer-post-type-info">
								<span class="dashicons <?php echo esc_attr( $dashicon ); ?>" aria-hidden="true"></span>
								<span class="slugviewer-post-type-label"><?php echo esc_html( $type_obj->labels->name ); ?></span>
								<span class="slugviewer-post-type-count"><?php echo esc_html( $total ); ?></span>
							</div>
							<label class="slugviewer-toggle">
								<input
									type="checkbox"
									name="slugviewer_enabled_post_types[]"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( $checked ); ?>
								/>
								<span class="screen-reader-text">
									<?php
									printf(
										/* translators: %s: Post type label. */
										esc_html__( 'Enable the Slug column for %s', 'slugviewer-admin-columns' ),
										esc_html( $type_obj->labels->name )
									);
									?>
								</span>
								<span class="slugviewer-toggle-slider" aria-hidden="true"></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Changes', 'slugviewer-admin-columns' ), 'slugviewer-save-btn', 'submit', false ); ?>
			</form>

			<div class="slugviewer-footer">
				<p>
					<?php
					$developer_link = sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( 'https://celeryagency.com/' ),
						esc_html( 'Celery Software LLC' )
					);
					$credit         = sprintf(
						/* translators: %s: Developer company name with link. */
						__( 'Made by %s', 'slugviewer-admin-columns' ),
						$developer_link
					);

					echo wp_kses_post( $credit );
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
