<?php
/**
 * Commerce Tables controller.
 *
 * @since 5.24.0
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
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce
 */
class Tables extends Controller_Contract {
	/**
	 * The action to schedule the webhook storage clean up.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const WEBHOOK_STORAGE_CLEAN_UP_ACTION = 'tec_tickets_commerce_async_webhook_storage_clean_up';

	/**
	 * The action group for the webhook storage clean up.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const TICKETS_COMMERCE_ACTION_GROUP = 'tec-tickets-commerce-actions';

	/**
	 * Register the controller's hooks.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'init', [ $this, 'schedule_webhook_storage_clean_up' ] );
		add_action( self::WEBHOOK_STORAGE_CLEAN_UP_ACTION, [ $this, 'clean_up_webhook_storage' ] );
		Register::table( Webhooks_Table::class );
	}

	/**
	 * Unregister the controller's hooks.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'schedule_webhook_storage_clean_up' ] );
		remove_action( self::WEBHOOK_STORAGE_CLEAN_UP_ACTION, [ $this, 'clean_up_webhook_storage' ] );
	}

	/**
	 * Clean up the webhook storage.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function clean_up_webhook_storage(): void {
		Webhooks_Table::delete_old_stale_entries();
	}

	/**
	 * Schedule the webhook storage clean up.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function schedule_webhook_storage_clean_up(): void {
		if ( as_has_scheduled_action( self::WEBHOOK_STORAGE_CLEAN_UP_ACTION, [], self::TICKETS_COMMERCE_ACTION_GROUP ) ) {
			return;
		}

		as_schedule_single_action(
			time() + DAY_IN_SECONDS,
			self::WEBHOOK_STORAGE_CLEAN_UP_ACTION,
			[],
			self::TICKETS_COMMERCE_ACTION_GROUP,
			true
		);
	}
}
