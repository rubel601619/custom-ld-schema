<?php
/**
 * Admin helpers: text domain, notices and plugin action links.
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

class LD_Schema_Admin {

	/**
	 * Register admin hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_filter( 'plugin_action_links_' . LD_SCHEMA_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Load the plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'custom-ld-schema', false, dirname( LD_SCHEMA_BASENAME ) . '/languages' );
	}

	/**
	 * Display one-time admin notices (e.g. rejected invalid JSON).
	 */
	public function render_admin_notices() {
		$notice = get_transient( 'ld_schema_admin_notice' );

		if ( ! is_array( $notice ) || ! isset( $notice[0], $notice[1] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice[0] ),
			wp_kses_post( $notice[1] )
		);

		delete_transient( 'ld_schema_admin_notice' );
	}

	/**
	 * Add a settings link to the plugin row.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		$settings_link = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . LD_SCHEMA_SETTINGS_PAGE ) ) . '">' . esc_html__( 'Settings', 'custom-ld-schema' ) . '</a>',
		);

		return array_merge( $settings_link, $links );
	}
}
