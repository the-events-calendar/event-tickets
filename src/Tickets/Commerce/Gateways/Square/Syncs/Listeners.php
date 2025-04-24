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
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\Item;

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
		add_action( 'save_post', [ $this, 'schedule_sync_on_save' ], 10, 2 );
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_sync' ], 10, 2 );
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_sync_on_date_start' ], 10, 4 );
		add_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_sync_on_date_end' ], 10, 4 );
		add_action( 'wp_trash_post', [ $this, 'schedule_sync_on_delete' ] );
		add_action( 'before_delete_post', [ $this, 'schedule_sync_on_delete' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'save_post', [ $this, 'schedule_sync_on_save' ] );
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_sync' ] );
		remove_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'schedule_sync_on_date_start' ] );
		remove_action( 'tec_tickets_ticket_end_date_trigger', [ $this, 'schedule_sync_on_date_end' ] );
		remove_action( 'wp_trash_post', [ $this, 'schedule_sync_on_delete' ] );
		remove_action( 'before_delete_post', [ $this, 'schedule_sync_on_delete' ] );
	}

	/**
	 * Schedule the sync on save.
	 *
	 * @since TBD
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function schedule_sync_on_save( int $post_id, WP_Post $post ): void {
		if ( ! in_array( $post->post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		$this->schedule_sync( $post_id, $post_id );
	}

	/**
	 * Schedule the sync on delete.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function schedule_sync_on_delete( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$ticket_types = tribe_tickets()->ticket_types();

		$ticket_or_event_types = array_merge(
			(array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			$ticket_types
		);

		if ( ! in_array( $post->post_type, $ticket_or_event_types, true ) ) {
			return;
		}

		if ( ! $this->is_object_syncable( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, $ticket_types, true ) ) {
			$this->schedule_deletion( $post_id );
			return;
		}

		// This is a ticket - if this is the last "sync-able" ticket we need to schedule deletion for the parent event instead.
		$ticket = Tickets::load_ticket_object( $post_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			// It seems like we cant delete its parent...
			$this->schedule_deletion( $post_id );
			return;
		}

		$parent_id = $ticket->get_event_id();

		if ( ! $parent_id ) {
			// The parent is already deleted...
			$this->schedule_deletion( $post_id );
			return;
		}

		$syncable_tickets = Sync_Controller::get_sync_able_tickets_of_event( $parent_id );

		if ( count( $syncable_tickets ) > 1 ) {
			// There are more sync-able tickets for this event, we only need to delete the ticket from Square.
			$this->schedule_deletion( $post_id );
			return;
		}

		// This is the last sync-able ticket for this event, we need to schedule deletion for the parent event instead.
		$this->schedule_deletion( $parent_id );
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
	public function schedule_sync( int $ticket_id, int $parent_id ): void {
		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		if ( ! $this->is_object_syncable( $ticket_id ) ) {
			return;
		}

		as_schedule_single_action( time() + MINUTE_IN_SECONDS / 3, Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $parent_id ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date start.
	 *
	 * @since TBD
	 *
	 * @param int     $ticket_id     The ticket ID.
	 * @param bool    $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int     $timestamp     The timestamp.
	 * @param WP_Post $post_parent   The parent post.
	 *
	 * @return void
	 */
	public function schedule_sync_on_date_start( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $post_parent ): void {
		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		if ( ! $this->is_object_syncable( $ticket_id ) ) {
			return;
		}

		$should_sync = $its_happening || time() >= $timestamp - Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id );

		if ( ! $should_sync ) {
			return;
		}

		as_schedule_single_action( time(), Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the ticket sync on date end.
	 *
	 * @since TBD
	 *
	 * @param int     $ticket_id     The ticket ID.
	 * @param bool    $its_happening Whether the ticket is about to go to sale or is already on sale.
	 * @param int     $timestamp     The timestamp.
	 * @param WP_Post $post_parent   The parent post.
	 *
	 * @return void
	 */
	public function schedule_sync_on_date_end( int $ticket_id, bool $its_happening, int $timestamp, WP_Post $post_parent ): void {
		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		if ( ! $this->is_object_syncable( $ticket_id ) ) {
			return;
		}

		if ( ! $its_happening ) {
			// Remove the synced tickets going out of sale at the very last moment.
			return;
		}

		as_schedule_single_action( time(), Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_parent->ID ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Schedule the deletion of a ticket from Square.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	protected function schedule_deletion( int $post_id ): void {
		$remote_objects   = tribe( Remote_Objects::class );
		$remote_object_id = $remote_objects->delete_remote_object_data( $post_id );

		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			// Unschedule any possible scheduled syncs.
			as_unschedule_action( Items_Sync::HOOK_SYNC_EVENT_ACTION, [ $post_id ], Sync_Controller::AS_SYNC_ACTION_GROUP );
		}

		if ( as_has_scheduled_action( Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, $remote_object_id ], Sync_Controller::AS_SYNC_ACTION_GROUP ) ) {
			return;
		}

		as_schedule_single_action( time() + MINUTE_IN_SECONDS / 3, Items_Sync::HOOK_SYNC_DELETE_EVENT_ACTION, [ 0, $remote_object_id ], Sync_Controller::AS_SYNC_ACTION_GROUP );
	}

	/**
	 * Whether the object is syncable.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool
	 */
	protected function is_object_syncable( int $post_id ): bool {
		return (bool) Item::get_remote_object_id( $post_id );
	}
}
