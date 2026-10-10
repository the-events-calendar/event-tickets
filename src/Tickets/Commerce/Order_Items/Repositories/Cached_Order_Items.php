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
 * Decorates an Order Items repository with the object cache, which is persistent where the site has a persistent
 * object cache. Every write goes to the decorated repository, then invalidates the cache, so a cached read is never
 * older than the last write.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */
final class Cached_Order_Items implements Order_Items_Repository {
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
	 * The decorated Order Items repository.
	 *
	 * @since TBD
	 *
	 * @var Order_Items_Repository
	 */
	private Order_Items_Repository $repository;

	/**
	 * Cached_Order_Items constructor.
	 *
	 * @since TBD
	 *
	 * @param Order_Items_Repository $repository The Order Items repository to decorate.
	 */
	public function __construct( Order_Items_Repository $repository ) {
		$this->repository = $repository;
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
			$this->flush();
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

	/**
	 * Inserts rows, then invalidates the cache.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to insert.
	 *
	 * @return int The number of rows inserted.
	 */
	public function insert_many( array $rows ): int {
		try {
			return $this->repository->insert_many( $rows );
		} finally {
			$this->flush();
		}
	}

	/**
	 * Updates rows, then invalidates the cache.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to update.
	 *
	 * @return int The number of rows changed.
	 */
	public function update_rows( array $rows ): int {
		try {
			return $this->repository->update_rows( $rows );
		} finally {
			$this->flush();
		}
	}

	/**
	 * Deletes an order's rows, then invalidates the cache.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_order( int $order_id ): int {
		try {
			return $this->repository->delete_by_order( $order_id );
		} finally {
			$this->flush();
		}
	}

	/**
	 * Deletes the given rows, then invalidates the cache.
	 *
	 * @since TBD
	 *
	 * @param int[] $ids The row IDs.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_many( array $ids ): int {
		try {
			return $this->repository->delete_many( $ids );
		} finally {
			$this->flush();
		}
	}

	/**
	 * Invalidates every cached read.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function flush(): void {
		wp_cache_set( self::TOKEN_KEY, uniqid( '', true ), self::GROUP );
	}
}
