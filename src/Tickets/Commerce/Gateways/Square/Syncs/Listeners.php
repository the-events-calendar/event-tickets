<?php
/**
 * Listens to events that trigger the scheduling of the syncs.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Tickets\Commerce\Gateways\Square\Syncs\Controller as Sync_Controller;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Ticket_Data;
use WP_Post;

/**
 * Class Listeners
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs
 */
class Listeners extends Controller_Contract {

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_ticket_sync' ], 10, 2 );
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_ticket_sync_on_date_start' ], 10, 4 );
		add_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_ticket_sync_on_date_end' ], 10, 4 );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_ticket_sync' ] );
		remove_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_ticket_sync_on_date_start' ] );
		remove_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_ticket_sync_on_date_end' ] );
	}

	/**
	 * Schedule the ticket sync.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $parent_id The parent ID.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync( int $ticket_id, int $parent_id ): void {
		as_schedule_single_action( time() + MINUTE_IN_SECONDS / 3, Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date start.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param bool $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_date_start( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $parent ): void {
		$should_sync = $its_happening || time() >= $timestamp - Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id );

		if ( ! $should_sync ) {
			return;
		}

		as_schedule_single_action( time(), Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date end.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param bool $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int $timestamp The timestamp.
	 *
	 * @return void
	 */
	public function schedule_ticket_sync_on_date_end( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $parent ): void {
		if ( ! $its_happening ) {
			// Remove the synced tickets going out of sale at the very last moment.
			as_unschedule_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP );
			return;
		}

		as_schedule_single_action( time(), Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}
}
