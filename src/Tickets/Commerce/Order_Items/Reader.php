<?php
/**
 * Loads the items of orders stored in the Order Items table from the table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Tickets\Commerce\Order_Items\Line_Items\Line_Item_Types;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
use Throwable;
use Tribe__Log as Log;

/**
 * Class Reader.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
final class Reader {
	/**
	 * The line item types that turn rows back into order items.
	 *
	 * @since TBD
	 *
	 * @var Line_Item_Types
	 */
	private Line_Item_Types $types;

	/**
	 * The Order Items repository.
	 *
	 * @since TBD
	 *
	 * @var Order_Items
	 */
	private Order_Items $repository;

	/**
	 * Reader constructor.
	 *
	 * @since TBD
	 *
	 * @param Line_Item_Types $types      The line item types that turn rows back into order items.
	 * @param Order_Items     $repository The Order Items repository.
	 */
	public function __construct( Line_Item_Types $types, Order_Items $repository ) {
		$this->types      = $types;
		$this->repository = $repository;
	}

	/**
	 * Replaces the items read from the order meta with the ones stored in the table, for orders stored there.
	 *
	 * Other orders run no query. Any failure is logged, never thrown, and the order keeps the items from its meta,
	 * which every order stored in the table also carries.
	 *
	 * @since TBD
	 *
	 * @param mixed                  $items     The order items, as read from the order meta.
	 * @param int                    $post_id   The order post ID.
	 * @param array<string,string[]> $post_meta The order post meta, as returned by `get_post_meta()`.
	 *
	 * @return mixed The order items.
	 */
	public function read_items( $items, int $post_id, array $post_meta ) {
		if ( Writer::VERSION !== absint( $post_meta[ Writer::VERSION_META_KEY ][0] ?? 0 ) ) {
			return $items;
		}

		$list = [];

		try {
			foreach ( $this->repository->get_by_order( $post_id ) as $model ) {
				$row            = $model->toArray();
				[ $key, $item ] = $this->types->get( $row['type'] )->from_row( $row );

				$list[ $key ] = $item;
			}
		} catch ( Throwable $e ) {
			do_action(
				'tribe_log',
				Log::DEBUG,
				'The order items could not be read from the table; the order reads them from its meta.',
				[
					'controller' => Controller::class,
					'order_id'   => $post_id,
					'error'      => $e->getMessage(),
				]
			);

			return $items;
		}

		return $list ?: $items;
	}
}
