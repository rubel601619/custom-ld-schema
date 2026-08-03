<?php
/**
 * Shared helper functions for the Custom LD Schema plugin.
 *
 * @package Custom_LD_Schema
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieve all public post types as objects, keyed by their slug.
 *
 * @return WP_Post_Type[]
 */
function ld_schema_get_public_post_types() {
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	// Remove internal WordPress post types that are never edited directly.
	unset( $post_types['attachment'] );

	return $post_types;
}

/**
 * Retrieve the list of post types the meta box is enabled for.
 *
 * "Posts" and "Pages" are enabled by default.
 *
 * @return string[]
 */
function ld_schema_enabled_post_types() {
	$defaults = array( 'post', 'page' );
	$option   = get_option( LD_SCHEMA_OPTION_POST_TYPES, $defaults );

	if ( ! is_array( $option ) ) {
		$option = $defaults;
	}

	return array_values( array_filter( array_map( 'sanitize_key', $option ) ) );
}

/**
 * Whether the meta box is enabled for a given post type.
 *
 * @param string $post_type Post type slug.
 * @return bool
 */
function ld_schema_is_enabled( $post_type ) {
	return in_array( $post_type, ld_schema_enabled_post_types(), true );
}

/**
 * Retrieve the saved custom schema for a post.
 *
 * @param int $post_id Post ID.
 * @return string Stored JSON-LD (without the script wrapper) or empty string.
 */
function ld_schema_get_custom( $post_id ) {
	$post_id = (int) $post_id;

	if ( ! $post_id ) {
		return '';
	}

	$value = get_post_meta( $post_id, LD_SCHEMA_META_KEY, true );

	return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Sanitize and validate schema input before it is stored.
 *
 * - Strips a surrounding <script> wrapper when present.
 * - Validates that the remaining content is valid JSON.
 * - Never re-encodes the JSON, preserving the author's formatting.
 *
 * @param string $content Raw content pasted by the administrator.
 * @return string|false The JSON content to store, an empty string to clear the
 *                      field, or false when the content is not valid JSON.
 */
function ld_schema_sanitize_json( $content ) {
	if ( ! is_string( $content ) ) {
		return '';
	}

	$content = trim( $content );

	if ( '' === $content ) {
		return '';
	}

	// Remove a surrounding <script type="application/ld+json">...</script> wrapper.
	if ( preg_match( '/<script\b[^>]*>(.*?)<\/script\s*>/is', $content, $matches ) ) {
		$content = trim( $matches[1] );
	}

	if ( '' === $content ) {
		return '';
	}

	// Validate the JSON without reformatting it.
	json_decode( $content );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return false;
	}

	return $content;
}

/**
 * Safely render stored JSON-LD inside a <script> block.
 *
 * Escapes "</" as "<\/" (still valid JSON) to prevent the stored value from
 * breaking out of the script tag, while leaving all other characters intact.
 *
 * @param string $content Stored JSON-LD.
 * @return string Escaped JSON-LD.
 */
function ld_schema_escape_json( $content ) {
	return str_replace( '</', '<\\/', $content );
}

/**
 * Resolve the post ID for the current singular request, if any.
 *
 * @return int Post ID or 0.
 */
function ld_schema_current_post_id() {
	$post = get_queried_object();

	if ( $post instanceof WP_Post && is_singular() ) {
		return (int) $post->ID;
	}

	return 0;
}
