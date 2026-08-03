<?php
/**
 * Uninstall routine for Custom LD Schema.
 *
 * Cleans up all options and post meta created by the plugin.
 *
 * @package Custom_LD_Schema
 */

// If uninstall is not called from WordPress, exit.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ld_schema_enabled_post_types' );
delete_option( 'ld_schema_strict_mode' );
delete_option( 'ld_schema_version' );

delete_post_meta_by_key( '_ld_custom_schema' );
