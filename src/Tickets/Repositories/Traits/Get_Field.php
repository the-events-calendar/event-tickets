<?php
/**
 * Trait for getting single field values from repositories.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Repositories\Traits
 */

namespace TEC\Tickets\Repositories\Traits;

/**
 * Trait Get_Field
 *
 * Provides a method to get a single field value without loading the full object.
 * Useful for quick lookups when you only need one field value.
 *
 * Requirements:
 * - Using class must have a get_update_fields_aliases() method that
 *   returns an array mapping field aliases to meta keys.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Repositories\Traits
 */
trait Get_Field {

	/**
	 * Get a single field value without loading full object.
	 *
	 * Useful for quick lookups when you only need one field value.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id Post ID (ticket or attendee).
	 * @param string $field   Field name (alias-aware, e.g., 'price', 'event_id', 'email').
	 *
	 * @return mixed Field value or null if not found.
	 */
	public function get_field( int $post_id, string $field ) {
		// Map field aliases to actual meta keys using the repository's alias map.
		$aliases = $this->get_update_fields_aliases();
		$meta_key = isset( $aliases[ $field ] ) ? $aliases[ $field ] : $field;

		// Check if this is a post field (not meta)
		$post_fields = [ 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_type', 'menu_order' ];

		if ( in_array( $meta_key, $post_fields, true ) ) {
			$post = get_post( $post_id );

			return $post ? $post->$meta_key : null;
		}

		// Use metadata_exists to distinguish "not set" from "set to empty"
		if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
			return null;
		}

		return get_post_meta( $post_id, $meta_key, true );
	}
}
