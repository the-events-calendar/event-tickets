<?php
/**
 * The Order Items controller: stores each line of a Tickets Commerce order in its own table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and environment variable, that disables the feature when truthy.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_COMMERCE_ORDER_ITEMS_DISABLED';

	/**
	 * Determines whether the feature is active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			return false;
		}

		/**
		 * Filters whether the Order Items feature is active.
		 *
		 * Only applies when neither the disabling constant nor the environment variable is set.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Default `true`.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_commerce_order_items_active', true ) );
	}

	/**
	 * Unregisters the controller. The table and its rows are never dropped: they are the record of what was sold.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Writer::class )->unregister();
	}

	/**
	 * Registers the table and the writer.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		try {
			Register::table( Order_Items::class );
		} catch ( DatabaseQueryException $e ) {
			$this->error(
				'The Order Items table could not be registered.',
				[
					'error' => $e->getMessage(),
					'query' => $e->getQuery(),
				]
			);

			return;
		}

		$this->container->register( Writer::class );
	}
}
