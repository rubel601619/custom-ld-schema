<?php
/**
 * Adds the "LD Schema" meta box to every enabled post type and saves its value.
 *
 * Accepts either a complete <script type="application/ld+json"> block or raw
 * JSON-LD. The <script> wrapper is stripped before the JSON is stored.
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

class LD_Schema_Metabox {

	/**
	 * Nonce action for the meta box.
	 *
	 * @var string
	 */
	private $nonce_action = 'ld_schema_metabox';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	private $nonce_field = 'ld_schema_nonce';

	/**
	 * Field name of the textarea.
	 *
	 * @var string
	 */
	private $field_name = 'ld_schema_json';

	/**
	 * Register admin hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the meta box for every enabled public post type.
	 */
	public function register_meta_boxes() {
		foreach ( ld_schema_get_public_post_types() as $post_type => $object ) {
			if ( ! ld_schema_is_enabled( $post_type ) ) {
				continue;
			}

			add_meta_box(
				'ld-schema-metabox',
				__( 'LD Schema', 'custom-ld-schema' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render( $post ) {
		wp_nonce_field( $this->nonce_action, $this->nonce_field );

		$schema = ld_schema_get_custom( $post->ID );
		$name   = $this->field_name;

		echo '<p class="description">' . esc_html__( 'Paste your JSON-LD schema. You may include the surrounding <script> tags or submit raw JSON only.', 'custom-ld-schema' ) . '</p>';

		printf(
			'<textarea id="ld-schema-json" class="large-text code" rows="12" name="%1$s" spellcheck="false">%2$s</textarea>',
			esc_attr( $name ),
			esc_textarea( $schema )
		);

		echo '<p class="ld-schema-validation-message" aria-live="polite"></p>';

		printf(
			'<p><button type="button" class="button button-secondary ld-schema-validate">%1$s</button> <a class="button" href="%2$s">%3$s</a></p>',
			esc_html__( 'Validate JSON', 'custom-ld-schema' ),
			esc_url( admin_url( 'admin.php?page=' . LD_SCHEMA_SETTINGS_PAGE ) ),
			esc_html__( 'Manage post types', 'custom-ld-schema' )
		);
	}

	/**
	 * Save the custom schema when the post is updated.
	 *
	 * @param int      $post_id Post ID.
	 * @param WP_Post  $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST[ $this->nonce_field ] ) ) {
			return;
		}

		$nonce = sanitize_key( wp_unslash( $_POST[ $this->nonce_field ] ) );

		if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $post->post_type ) || ! ld_schema_is_enabled( $post->post_type ) ) {
			return;
		}

		if ( ! isset( $_POST[ $this->field_name ] ) ) {
			return;
		}

		$raw     = wp_unslash( $_POST[ $this->field_name ] );
		$content = ld_schema_sanitize_json( $raw );

		if ( false === $content ) {
			$this->notify_invalid_json();
			return;
		}

		if ( '' === $content ) {
			delete_post_meta( $post_id, LD_SCHEMA_META_KEY );
			return;
		}

		update_post_meta( $post_id, LD_SCHEMA_META_KEY, $content );
	}

	/**
	 * Store an admin notice for the invalid JSON rejection.
	 */
	private function notify_invalid_json() {
		set_transient(
			'ld_schema_admin_notice',
			array(
				'error',
				__( 'LD Schema: the markup was not saved because it is not valid JSON.', 'custom-ld-schema' ),
			),
			60
		);
	}

	/**
	 * Load meta box assets only on post edit screens for enabled post types.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || 'post' !== $screen->base ) {
			return;
		}

		if ( ! ld_schema_is_enabled( $screen->post_type ) ) {
			return;
		}

		wp_enqueue_style( 'ld-schema-admin', LD_SCHEMA_URL . 'assets/css/admin.css', array(), LD_SCHEMA_VERSION );
		wp_enqueue_script( 'ld-schema-admin', LD_SCHEMA_URL . 'assets/js/admin.js', array(), LD_SCHEMA_VERSION, true );

		wp_localize_script(
			'ld-schema-admin',
			'ldSchemaAdminLabels',
			array(
				'valid'   => __( 'Valid JSON-LD', 'custom-ld-schema' ),
				'invalid' => __( 'Invalid JSON-LD:', 'custom-ld-schema' ),
			)
		);
	}
}
