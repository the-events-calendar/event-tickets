<?php
/**
 * Handles the meta for the Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Settings as Commerce_Settings;

/**
 * Meta class for the Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Meta {
	/**
	 * Wrapper for get_metadata that allows for environmental meta.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since TBD
	 *
	 * @param int    $id       The ID of the object.
	 * @param string $meta_key The meta key.
	 * @param array  $args     Additional arguments.
	 * @param string $type     The type of object.
	 * @param bool   $single   Whether to return a single value.
	 *
	 * @return mixed The environmental meta value.
	 */
	public static function get( int $id, string $meta_key, array $args = [], string $type = 'post', bool $single = true ) {
		return get_metadata( $type, $id, Commerce_Settings::get_environmental_key( $meta_key, $args ), $single );
	}

	/**
	 * Wrapper for add_metadata that allows for environmental meta.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since TBD
	 *
	 * @param int    $id         The ID of the object.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $value      The value to add.
	 * @param array  $args       Additional arguments.
	 * @param string $type       The type of object.
	 *
	 * @return bool Whether the meta was added.
	 */
	public static function add( int $id, string $meta_key, $value, array $args = [], string $type = 'post' ): bool {
		// Make sure meta is added to the post, not a revision.
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		return add_metadata( $type, $id, Commerce_Settings::get_environmental_key( $meta_key, $args ), $value );
	}

	/**
	 * Wrapper for update_metadata that allows for environmental meta.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since TBD
	 *
	 * @param int    $id         The ID of the object.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $value      The value to update.
	 * @param array  $args       Additional arguments.
	 * @param string $type       The type of object.
	 *
	 * @return bool Whether the meta was updated.
	 */
	public static function set( int $id, string $meta_key, $value, array $args = [], string $type = 'post' ): bool {
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		return (bool) update_metadata( $type, $id, Commerce_Settings::get_environmental_key( $meta_key, $args ), $value );
	}

	/**
	 * Wrapper for delete_metadata that allows for environmental meta.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since TBD
	 *
	 * @param int    $id         The ID of the object.
	 * @param string $meta_key   The meta key.
	 * @param array  $args       Additional arguments.
	 * @param string $type       The type of object.
	 * @param mixed  $meta_value The value to delete.
	 *
	 * @return bool Whether the meta was deleted.
	 */
	public static function delete( int $id, string $meta_key, array $args = [], string $type = 'post', $meta_value = '' ): bool {
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		return delete_metadata( $type, $id, Commerce_Settings::get_environmental_key( $meta_key, $args ), $meta_value );
	}
}
