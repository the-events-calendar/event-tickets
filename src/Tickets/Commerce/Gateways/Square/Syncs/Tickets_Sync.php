<?php
/**
 * Syncs tickets with Square controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Tickets_Sync
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Tickets_Sync extends Controller_Contract {
	/**
	 * The action that syncs tickets with Square.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION = 'tec_tickets_commerce_square_sync_tickets';

	/**
	 * The group that the sync action belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const SYNC_ACTION_GROUP = 'tec_tickets_commerce_square_syncs';

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'init', [ $this, 'schedule_tickets_sync' ] );
		add_action( self::SYNC_ACTION, [ $this, 'sync_tickets' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {}

	/**
	 * Schedule the tickets sync.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function schedule_tickets_sync(): void {
		if ( as_has_scheduled_action( self::SYNC_ACTION, [], self::SYNC_ACTION_GROUP ) ) {
			return;
		}

		as_schedule_single_action( time(), self::SYNC_ACTION, [], self::SYNC_ACTION_GROUP );
	}

	/**
	 * Sync the tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function sync_tickets(): void {
		$providers = Tickets::modules();

		foreach ( array_keys( $providers ) as $provider_class ) {
			as_schedule_single_action( time(), self::SYNC_ACTION, [ $provider_class ], self::SYNC_ACTION_GROUP );
		}
	}
}
