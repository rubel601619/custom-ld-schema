<?php
/**
 * Front-end schema output and SEO plugin suppression.
 *
 * When a custom schema exists for the current page:
 *  - SEO plugin JSON-LD is suppressed through their official filters.
 *  - A single, standardized <script type="application/ld+json"> block is output.
 *
 * When no custom schema exists, SEO plugins keep working normally.
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

class LD_Schema_Output {

	/**
	 * Whether a custom schema is active for the current request.
	 *
	 * @var bool
	 */
	private $active = false;

	/**
	 * Post ID whose custom schema is being output.
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Output buffering level at which the head buffer was started.
	 *
	 * @var int
	 */
	private $head_buffer_level = 0;

	/**
	 * Output buffering level at which the footer buffer was started.
	 *
	 * @var int
	 */
	private $footer_buffer_level = 0;

	/**
	 * Register front-end hooks.
	 */
	public function init() {
		add_action( 'wp', array( $this, 'maybe_activate' ) );
		add_action( 'wp_head', array( $this, 'output_schema' ), 1 );
	}

	/**
	 * Detect a saved custom schema on the current singular request and, if
	 * present, suppress SEO plugin output and render the schema.
	 */
	public function maybe_activate() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post_id = ld_schema_current_post_id();

		if ( ! $post_id ) {
			return;
		}

		if ( ! ld_schema_is_enabled( get_post_type( $post_id ) ) ) {
			return;
		}

		if ( '' === ld_schema_get_custom( $post_id ) ) {
			return;
		}

		$this->active  = true;
		$this->post_id = $post_id;

		$this->suppress_seo_schema();
		$this->maybe_start_strict_mode();
	}

	/**
	 * Output the custom schema using the standardized script block.
	 */
	public function output_schema() {
		if ( ! $this->active ) {
			return;
		}

		$schema = ld_schema_get_custom( $this->post_id );

		if ( '' === $schema ) {
			return;
		}

		$script = sprintf(
			"<script\n\ttype=\"application/ld+json\"\n\tid=\"ld-custom-schema\"\n\tclass=\"ld-custom-schema\">\n%s\n</script>\n",
			ld_schema_escape_json( $schema )
		);

		/**
		 * Filter the final script block before it is printed.
		 *
		 * @param string $script  Complete <script> markup.
		 * @param string $schema  Escaped JSON-LD content.
		 * @param int    $post_id Current post ID.
		 */
		$script = apply_filters( 'ld_schema_output_script', $script, $schema, $this->post_id );

		echo $script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Deliberately raw, validated JSON.
	}

	/**
	 * Register the official SEO plugin filters that suppress their JSON-LD.
	 *
	 * Filters are only registered on requests that actually have a custom
	 * schema, so the suppression is scoped and never leaks to other pages.
	 */
	private function suppress_seo_schema() {
		// Yoast SEO (classic and modern schema output).
		add_filter( 'wpseo_json_ld_output', '__return_empty_string' );
		add_filter( 'wpseo_schema_graph_pieces', '__return_empty_array' );

		// Rank Math.
		add_filter( 'rank_math/json_ld', '__return_empty_array' );

		// All in One SEO.
		add_filter( 'aioseo_schema_output', '__return_false' );

		// The SEO Framework.
		add_filter( 'the_seo_framework_json_ld_output', '__return_empty_array' );

		/**
		 * Allow themes and other plugins to register additional suppression
		 * filters for SEO plugins that do not expose a documented removal hook.
		 *
		 * @param int $post_id Current post ID.
		 */
		do_action( 'ld_schema_suppress_filters', $this->post_id );
	}

	/**
	 * Optionally start output buffering around wp_head and wp_footer to remove
	 * JSON-LD scripts that cannot be suppressed through official filters.
	 *
	 * This is opt-in because output buffering is more fragile than filters.
	 */
	private function maybe_start_strict_mode() {
		if ( ! get_option( LD_SCHEMA_OPTION_STRICT_MODE, 0 ) ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'start_head_buffer' ), 0 );
		add_action( 'wp_head', array( $this, 'end_head_buffer' ), PHP_INT_MAX );
		add_action( 'wp_footer', array( $this, 'start_footer_buffer' ), 0 );
		add_action( 'wp_footer', array( $this, 'end_footer_buffer' ), PHP_INT_MAX );
	}

	/**
	 * Start buffering wp_head output.
	 */
	public function start_head_buffer() {
		$this->head_buffer_level = ob_get_level();
		ob_start( array( $this, 'strip_other_schema' ) );
	}

	/**
	 * Flush the wp_head buffer, running the strip callback.
	 */
	public function end_head_buffer() {
		$this->flush_buffer( $this->head_buffer_level );
	}

	/**
	 * Start buffering wp_footer output.
	 */
	public function start_footer_buffer() {
		$this->footer_buffer_level = ob_get_level();
		ob_start( array( $this, 'strip_other_schema' ) );
	}

	/**
	 * Flush the wp_footer buffer, running the strip callback.
	 */
	public function end_footer_buffer() {
		$this->flush_buffer( $this->footer_buffer_level );
	}

	/**
	 * Flush every buffer started on top of a known level without breaking
	 * buffers registered by other plugins.
	 *
	 * @param int $level Level recorded when the buffer was started.
	 */
	private function flush_buffer( $level ) {
		while ( ob_get_level() > $level ) {
			ob_end_flush();
		}
	}

	/**
	 * Remove JSON-LD scripts that are not our own from a captured buffer.
	 *
	 * @param string $buffer Captured HTML.
	 * @return string
	 */
	public function strip_other_schema( $buffer ) {
		$pattern = '#<script\b[^>]*application/ld\+json[^>]*>.*?</script\s*>#is';

		return preg_replace_callback(
			$pattern,
			static function ( $matches ) {
				// Never strip our own standardized script block.
				if ( false !== strpos( $matches[0], 'ld-custom-schema' ) ) {
					return $matches[0];
				}

				return '';
			},
			$buffer
		);
	}
}
