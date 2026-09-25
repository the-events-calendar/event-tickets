<?php
/**
 * Loads the items of orders stored in the Order Items table from the table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
use Throwable;

/**
 * Class Reader.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
class Reader extends Controller_Contract {
	/**
	 * The mapper that turns rows back into order items.
	 *
	 * @since TBD
	 *
	 * @var Mapper
	 */
	private Mapper $mapper;

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
	 * @param Container   $container  The container.
	 * @param Mapper      $mapper     The mapper that turns rows back into order items.
	 * @param Order_Items $repository The Order Items repository.
	 */
	public function __construct( Container $container, Mapper $mapper, Order_Items $repository ) {
		parent::__construct( $container );
		$this->mapper     = $mapper;
		$this->repository = $repository;
	}

	/**
	 * Unhooks the reader.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_order_model_items', [ $this, 'read_items' ] );
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
				[ $key, $item ] = $this->mapper->to_item( $model->toArray() );

				$list[ $key ] = $item;
			}
		} catch ( Throwable $e ) {
			$this->debug(
				'The order items could not be read from the table; the order reads them from its meta.',
				[
					'order_id' => $post_id,
					'error'    => $e->getMessage(),
				]
			);

			return $items;
		}

		return $list ?: $items;
	}

	/**
	 * Hooks the reader.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_commerce_order_model_items', [ $this, 'read_items' ], 10, 3 );
	}
}
