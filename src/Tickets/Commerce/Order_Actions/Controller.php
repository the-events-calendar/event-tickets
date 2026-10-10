<?php
/**
 * Hooks the Order Actions service that fires the order updated and deleted actions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Actions
 */

namespace TEC\Tickets\Commerce\Order_Actions;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Order_Actions;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Actions
 */
class Controller extends Controller_Contract {
	/**
	 * Unhooks the service.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'update_post_metadata', $this->container->callback( Order_Actions::class, 'remember_previous_items' ), 10 );
		remove_action( 'added_post_meta', $this->container->callback( Order_Actions::class, 'fire_order_updated' ), 10 );
		remove_action( 'updated_post_meta', $this->container->callback( Order_Actions::class, 'fire_order_updated' ), 10 );
		remove_action( 'deleted_post', $this->container->callback( Order_Actions::class, 'fire_order_deleted' ), 10 );
	}

	/**
	 * Hooks the service.
	 *
	 * It is bound as a singleton so the container returns the same callbacks to `unregister()`, and so the items
	 * remembered before a write are the ones read after it.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Order_Actions::class );

		add_filter( 'update_post_metadata', $this->container->callback( Order_Actions::class, 'remember_previous_items' ), 10, 3 );
		add_action( 'added_post_meta', $this->container->callback( Order_Actions::class, 'fire_order_updated' ), 10, 4 );
		add_action( 'updated_post_meta', $this->container->callback( Order_Actions::class, 'fire_order_updated' ), 10, 4 );
		add_action( 'deleted_post', $this->container->callback( Order_Actions::class, 'fire_order_deleted' ), 10, 2 );
	}
}
