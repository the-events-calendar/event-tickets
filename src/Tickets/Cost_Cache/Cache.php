<?php
/**
 * Cost caching functionality for events.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */

namespace TEC\Tickets\Cost_Cache;

/**
 * Class Cache
 *
 * Handles caching of event cost calculations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */
class Cache {

	/**
	 * Meta key for cached cost without currency symbol.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const META_KEY_COST = '_tec_cached_cost';

	/**
	 * Meta key for cached cost with currency symbol.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const META_KEY_COST_WITH_SYMBOL = '_tec_cached_cost_with_symbol';

	/**
	 * Get cached cost for an event.
	 *
	 * @since TBD
	 *
	 * @param int  $event_id              The event ID.
	 * @param bool $with_currency_symbol Whether to include currency symbol.
	 *
	 * @return string|false The cached cost or false if not cached.
	 */
	public function get( $event_id, $with_currency_symbol = false ) {
		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$meta_key = $with_currency_symbol ? self::META_KEY_COST_WITH_SYMBOL : self::META_KEY_COST;
		
		// Check if the meta exists (even if empty string for free events).
		$meta_exists = metadata_exists( 'post', $event_id, $meta_key );
		
		if ( ! $meta_exists ) {
			return false;
		}

		return get_post_meta( $event_id, $meta_key, true );
	}

	/**
	 * Set cached cost for an event.
	 *
	 * @since TBD
	 *
	 * @param int    $event_id              The event ID.
	 * @param bool   $with_currency_symbol Whether currency symbol is included.
	 * @param string $cost                 The cost to cache.
	 *
	 * @return bool Whether the cache was set.
	 */
	public function set( $event_id, $with_currency_symbol, $cost ) {
		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$meta_key = $with_currency_symbol ? self::META_KEY_COST_WITH_SYMBOL : self::META_KEY_COST;

		return (bool) update_post_meta( $event_id, $meta_key, $cost );
	}

	/**
	 * Clear cached cost for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 */
	public function clear( $event_id ) {
		if ( ! is_numeric( $event_id ) ) {
			return;
		}

		// Clear both versions (with and without currency symbol).
		delete_post_meta( $event_id, self::META_KEY_COST );
		delete_post_meta( $event_id, self::META_KEY_COST_WITH_SYMBOL );
	}

	/**
	 * Clear all cost caches.
	 *
	 * @since TBD
	 */
	public function clear_all() {
		// Get all events that might have cached costs.
		$events = tribe_events()
			->where_or(
				[
					function ( $repository ) {
						$repository->where( 'meta_exists', self::META_KEY_COST );
					},
					function ( $repository ) {
						$repository->where( 'meta_exists', self::META_KEY_COST_WITH_SYMBOL );
					},
				] 
			)
			->fields( 'ids' )
			->per_page( -1 )
			->found();

		// Clear cache for each event.
		foreach ( $events as $event_id ) {
			$this->clear( $event_id );
		}

		do_action( 'tec_tickets_cost_cache_cleared_all' );
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool Whether caching is enabled.
	 */
	public function is_enabled() {
		/**
		 * Filter whether event cost caching is enabled.
		 *
		 * @since TBD
		 *
		 * @param bool $enabled Whether caching is enabled. Default true.
		 */
		return apply_filters( 'tec_tickets_enable_cost_cache', true );
	}
}
