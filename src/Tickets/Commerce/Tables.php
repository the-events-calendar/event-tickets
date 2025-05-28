<?php
/**
 * Commerce Tables controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Commerce\Tables\Webhooks as Webhooks_Table;

/**
 * Tables class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Tables extends Controller_Contract {
	/**
	 * Register the controller's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'init', [ $this, 'schedule_webhook_storage_clean_up' ] );
		Register::table( Webhooks_Table::class );
	}

	/**
	 * Unregister the controller's hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'schedule_webhook_storage_clean_up' ] );
	}
}