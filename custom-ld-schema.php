<?php
/**
 * Plugin Name:       Custom LD Schema
 * Plugin URI:        https://example.com/plugins/custom-ld-schema/
 * Description:       Assign custom JSON-LD schema markup to any supported post type while staying compatible with popular SEO plugins such as Yoast SEO, Rank Math, SEOPress and All in One SEO.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Custom LD Schema
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-ld-schema
 * Domain Path:       /languages
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version.
 */
define( 'LD_SCHEMA_VERSION', '1.0.0' );

/**
 * Absolute path to the main plugin file.
 */
define( 'LD_SCHEMA_FILE', __FILE__ );

/**
 * Absolute plugin directory path (with trailing slash).
 */
define( 'LD_SCHEMA_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL (with trailing slash).
 */
define( 'LD_SCHEMA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'LD_SCHEMA_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Post meta key that stores the custom schema JSON.
 */
define( 'LD_SCHEMA_META_KEY', '_ld_custom_schema' );

/**
 * Settings option that stores the enabled post types.
 */
define( 'LD_SCHEMA_OPTION_POST_TYPES', 'ld_schema_enabled_post_types' );

/**
 * Settings option that enables strict JSON-LD removal via output buffering.
 */
define( 'LD_SCHEMA_OPTION_STRICT_MODE', 'ld_schema_strict_mode' );

/**
 * Slug of the top-level settings page.
 */
define( 'LD_SCHEMA_SETTINGS_PAGE', 'ld-schema' );

require_once LD_SCHEMA_PATH . 'includes/helpers.php';
require_once LD_SCHEMA_PATH . 'includes/class-loader.php';

/**
 * Activation routine: seeds default options on first activation.
 */
function ld_schema_activate() {
	if ( false === get_option( LD_SCHEMA_OPTION_POST_TYPES ) ) {
		add_option( LD_SCHEMA_OPTION_POST_TYPES, array( 'post', 'page' ) );
	}

	if ( false === get_option( LD_SCHEMA_OPTION_STRICT_MODE ) ) {
		add_option( LD_SCHEMA_OPTION_STRICT_MODE, 0 );
	}

	update_option( 'ld_schema_version', LD_SCHEMA_VERSION );
}

register_activation_hook( __FILE__, 'ld_schema_activate' );

/**
 * Boot the plugin after all plugins have loaded so that every SEO plugin
 * and custom post type is already registered.
 *
 * @return LD_Schema_Loader
 */
function ld_schema_loader() {
	return LD_Schema_Loader::instance();
}

add_action( 'plugins_loaded', 'ld_schema_loader' );
