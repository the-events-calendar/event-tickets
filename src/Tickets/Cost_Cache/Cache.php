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
	 * Cache expiration time in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = 300; // 5 minutes.

	/**
	 * Cache key prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const CACHE_PREFIX = 'tec_event_cost_';

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

		$cache_key = $this->get_cache_key( $event_id, $with_currency_symbol );
		$cache     = tribe_cache();

		return $cache->get_transient( $cache_key );
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

		$cache_key = $this->get_cache_key( $event_id, $with_currency_symbol );
		$cache     = tribe_cache();

		return $cache->set_transient( $cache_key, $cost, self::CACHE_EXPIRATION );
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

		$cache = tribe_cache();

		// Clear both versions (with and without currency symbol).
		$cache->delete_transient( $this->get_cache_key( $event_id, true ) );
		$cache->delete_transient( $this->get_cache_key( $event_id, false ) );
	}

	/**
	 * Clear all cost caches.
	 *
	 * @since TBD
	 */
	public function clear_all() {
		$cache = tribe_cache();
		
		// This would need to be implemented in Tribe__Cache if we want to clear by prefix.
		// For now, we'll rely on individual cache clearing.
		do_action( 'tec_tickets_cost_cache_cleared_all' );
	}

	/**
	 * Get the cache key for an event cost.
	 *
	 * @since TBD
	 *
	 * @param int  $event_id              The event ID.
	 * @param bool $with_currency_symbol Whether currency symbol is included.
	 *
	 * @return string The cache key.
	 */
	private function get_cache_key( $event_id, $with_currency_symbol ) {
		$suffix = $with_currency_symbol ? '_with_symbol' : '_no_symbol';
		
		return self::CACHE_PREFIX . $event_id . $suffix;
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
