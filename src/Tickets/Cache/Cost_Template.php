<?php
/**
 * Simple template caching for cost templates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cache
 */

namespace TEC\Tickets\Cache;

/**
 * Class Cost_Template
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cache
 */
class Cost_Template {

	/**
	 * Meta key prefix for cached templates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const META_KEY_PREFIX = '_tec_cached_template_';

	/**
	 * Get cached template HTML.
	 *
	 * @since TBD
	 *
	 * @param int    $event_id The event ID.
	 * @param string $hook_name The template hook name.
	 *
	 * @return string|false The cached HTML or false if not cached.
	 */
	public function get( $event_id, $hook_name ) {
		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$meta_key = self::META_KEY_PREFIX . md5( $hook_name );

		$meta_exists = metadata_exists( 'post', $event_id, $meta_key );
		if ( ! $meta_exists ) {
			return false;
		}

		return get_post_meta( $event_id, $meta_key, true );
	}

	/**
	 * Set cached template HTML.
	 *
	 * @since TBD
	 *
	 * @param int    $event_id The event ID.
	 * @param string $hook_name The template hook name.
	 * @param string $html The HTML to cache.
	 *
	 * @return bool Whether the cache was set.
	 */
	public function set( $event_id, $hook_name, $html ) {
		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$meta_key = self::META_KEY_PREFIX . md5( $hook_name );

		return (bool) update_post_meta( $event_id, $meta_key, $html );
	}

	/**
	 * Clear cached templates for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 */
	public function clear( $event_id ) {
		if ( ! is_numeric( $event_id ) ) {
			return;
		}

		// Get all meta keys for this event that match our prefix.
		$meta_keys = get_post_meta( $event_id );

		if ( ! empty( $meta_keys ) ) {
			foreach ( $meta_keys as $key => $value ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				if ( 0 === strpos( $key, self::META_KEY_PREFIX ) ) {
					delete_post_meta( $event_id, $key );
				}
			}
		}
	}

	/**
	 * Clear all template caches.
	 *
	 * @since TBD
	 */
	public function clear_all() {
		// Get all events that might have cached templates.
		$events = tribe_events()
			->where( 'meta_like', self::META_KEY_PREFIX )
			->fields( 'ids' )
			->per_page( -1 )
			->found();

		// Clear cache for each event.
		foreach ( $events as $event_id ) {
			$this->clear( $event_id );
		}
	}
}
