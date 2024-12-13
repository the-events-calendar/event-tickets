<?php
/**
 * The custom tables' controller.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Controller;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships;

/**
 * Class Tables.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Controller;
 */
class Tables extends Controller_Contract {

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		// During unregister we should NOT drop our tables. So nothing to do.
	}

	/**
	 * Registers the tables and the bindings required to use them.
	 *
	 * @since 5.18.0
	 *
	 * @return void The tables are registered.
	 */
	protected function do_register(): void {
		Register::table( Order_Modifiers::class );
		Register::table( Order_Modifiers_Meta::class );
		Register::table( Order_Modifier_Relationships::class );
	}
}
