<?php
/**
 * Caches the reads of the Order Items repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use TEC\Tickets\Commerce\Order_Items\Models\Order_Item;

/**
 * Class Cached_Order_Items.
 *
 * Decorates the Order Items repository with the object cache, which is persistent where the site has a persistent
 * object cache. Every write through the repository calls `flush()`, so a cached read is never older than the last write.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */
final class Cached_Order_Items {
	/**
	 * The object cache group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const GROUP = 'tec_tickets_order_items';

	/**
	 * The cache key holding the token every cached read is keyed with.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const TOKEN_KEY = 'last_changed';

	/**
	 * The Order Items repository.
	 *
	 * @since TBD
	 *
	 * @var Order_Items
	 */
	private Order_Items $repository;

	/**
	 * Cached_Order_Items constructor.
	 *
	 * @since TBD
	 *
	 * @param Order_Items $repository The Order Items repository.
	 */
	public function __construct( Order_Items $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Invalidates every cached read.
	 *
	 * @since TBD
	 *
	 * @internal Called by the repository after a write.
	 *
	 * @return void
	 */
	public static function flush(): void {
		wp_cache_set( self::TOKEN_KEY, uniqid( '', true ), self::GROUP );
	}

	/**
	 * Returns an order's rows in list order, from the cache when it holds them.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return Order_Item[] The rows, ordered by position, then ID, ascending.
	 */
	public function get_by_order( int $order_id ): array {
		$token = wp_cache_get( self::TOKEN_KEY, self::GROUP );

		if ( ! $token ) {
			self::flush();
			$token = wp_cache_get( self::TOKEN_KEY, self::GROUP );
		}

		$key    = "order_{$order_id}_{$token}";
		$cached = wp_cache_get( $key, self::GROUP, false, $found );

		if ( $found && is_array( $cached ) ) {
			return $cached;
		}

		$rows = $this->repository->get_by_order( $order_id );

		wp_cache_set( $key, $rows, self::GROUP );

		return $rows;
	}
}
