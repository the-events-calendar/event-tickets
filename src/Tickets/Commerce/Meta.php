<?php
/**
 * Handles the meta for the Tickets Commerce.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Settings as Commerce_Settings;
use WP_Query;

/**
 * Meta class for the Tickets Commerce.
 *
 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @param int    $id              The ID of the object.
	 * @param string $meta_key        The meta key.
	 * @param array  $args            Additional arguments.
	 * @param string $type            The type of object.
	 * @param bool   $single          Whether to return a single value.
	 * @param bool   $use_environment Whether to use the environment.
	 *
	 * @return mixed The environmental meta value.
	 */
	public static function get( int $id, string $meta_key, array $args = [], string $type = 'post', bool $single = true, bool $use_environment = true ) {
		$meta_key = $use_environment ? Commerce_Settings::get_key( $meta_key, $args ) : sprintf( $meta_key, ...$args );

		return get_metadata( $type, $id, $meta_key, $single );
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
	 * @since 5.24.0
	 *
	 * @param int    $id              The ID of the object.
	 * @param string $meta_key        The meta key.
	 * @param mixed  $value           The value to add.
	 * @param array  $args            Additional arguments.
	 * @param string $type            The type of object.
	 * @param bool   $use_environment Whether to use the environment.
	 *
	 * @return bool|int Whether the meta was added.
	 */
	public static function add( int $id, string $meta_key, $value, array $args = [], string $type = 'post', bool $use_environment = true ) {
		// Make sure meta is added to the post, not a revision.
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		$meta_key = $use_environment ? Commerce_Settings::get_key( $meta_key, $args ) : sprintf( $meta_key, ...$args );

		return add_metadata( $type, $id, $meta_key, $value );
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
	 * @since 5.24.0
	 *
	 * @param int    $id              The ID of the object.
	 * @param string $meta_key        The meta key.
	 * @param mixed  $value           The value to update.
	 * @param array  $args            Additional arguments.
	 * @param string $type            The type of object.
	 * @param bool   $use_environment Whether to use the environment.
	 *
	 * @return bool|int Whether the meta was updated.
	 */
	public static function set( int $id, string $meta_key, $value, array $args = [], string $type = 'post', bool $use_environment = true ) {
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		$meta_key = $use_environment ? Commerce_Settings::get_key( $meta_key, $args ) : sprintf( $meta_key, ...$args );

		return update_metadata( $type, $id, $meta_key, $value );
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
	 * @since 5.24.0
	 *
	 * @param int    $id              The ID of the object.
	 * @param string $meta_key        The meta key.
	 * @param array  $args            Additional arguments.
	 * @param string $type            The type of object.
	 * @param mixed  $meta_value      The value to delete.
	 * @param bool   $use_environment Whether to use the environment.
	 *
	 * @return bool Whether the meta was deleted.
	 */
	public static function delete( int $id, string $meta_key, array $args = [], string $type = 'post', $meta_value = '', bool $use_environment = true ): bool {
		$the_post = 'post' === $type ? wp_is_post_revision( $id ) : false;
		if ( $the_post ) {
			$id = $the_post;
		}

		$meta_key = $use_environment ? Commerce_Settings::get_key( $meta_key, $args ) : sprintf( $meta_key, ...$args );

		return delete_metadata( $type, $id, $meta_key, $meta_value );
	}

	/**
	 * Get the object ID for a given meta key and value.
	 *
	 * @since 5.24.0
	 *
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 *
	 * @return int The object ID.
	 */
	public static function get_object_id( string $meta_key, $meta_value ): int {
		$meta_key  = Commerce_Settings::get_key( $meta_key );
		$cache     = tribe_cache();
		$cache_key = 'tec_tickets_commerce_meta_get_object_id_' . md5( $meta_key . '_' . wp_json_encode( $meta_value ) );
		$object_id = $cache[ $cache_key ] ?? false;

		if ( is_int( $object_id ) && $object_id >= 0 ) {
			return $object_id;
		}

		$args = [
			'post_type'              => array_merge( (array) tribe_get_option( 'ticket-enabled-post-types', [] ), tribe_tickets()->ticket_types() ),
			'post_status'            => 'any',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'posts_per_page'         => 1,
			'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => $meta_key,
					'value' => $meta_value,
				],
			],
			'fields'                 => 'ids',
		];

		$results = new WP_Query( $args );

		if ( empty( $results->posts ) ) {
			$cache[ $cache_key ] = 0;
			return $cache[ $cache_key ];
		}

		$cache[ $cache_key ] = (int) $results->posts[0];

		return $cache[ $cache_key ];
	}
}
