<?php
/**
 * The Ticket-Able Post Actions controller.
 *
 * @since TBD
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket_Data;
use WP_Post;
use TEC\Tickets\Commerce\Module as Commerce_Ticket_Provider;

/**
 * Class Ticket_Able_Post_Actions.
 *
 * @since TBD
 * @package TEC\Tickets
 */
class Ticket_Able_Post_Actions extends Controller_Contract {
	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'save_post', [ $this, 'fire_ticket_able_post_change_action' ], 10, 3 );
		add_action( 'wp_trash_post', [ $this, 'fire_ticket_or_ticket_able_post_deleted' ] );
		add_action( 'before_delete_post', [ $this, 'fire_ticket_or_ticket_able_post_deleted' ] );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'save_post', [ $this, 'fire_ticket_able_post_change_action' ] );
		remove_action( 'wp_trash_post', [ $this, 'fire_ticket_or_ticket_able_post_deleted' ] );
		remove_action( 'before_delete_post', [ $this, 'fire_ticket_or_ticket_able_post_deleted' ] );
	}

	/**
	 * Fire the ticket-able post deleted action.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function fire_ticket_or_ticket_able_post_deleted( int $post_id ): void {
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

		$is_full_deletion = doing_action( 'before_delete_post' );

		if ( ! in_array( $post->post_type, $ticket_types, true ) ) {
			/**
			 * Fires when a ticket-able post is deleted.
			 *
			 * @since TBD
			 *
			 * @param int  $post_id         The post ID.
			 * @param bool $is_full_deletion Whether the post is being fully deleted.
			 */
			do_action( 'tec_tickets_ticket_able_post_deleted', $post_id, $is_full_deletion );
			return;
		}

		$ticket = Tickets::load_ticket_object( $post_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			$this->fire_ticket_deleted_action( $post_id, $is_full_deletion, null, null );
			return;
		}

		$parent_id = $ticket->get_event_id();

		if ( ! $parent_id ) {
			$this->fire_ticket_deleted_action( $post_id, $is_full_deletion, null, null );
			return;
		}

		$syncable_tickets = tribe( Ticket_Data::class )->get_sync_able_tickets_of_event( $parent_id );

		$this->fire_ticket_deleted_action( $post_id, $is_full_deletion, $parent_id, count( $syncable_tickets ) > 1 );
	}

	/**
	 * Fire the ticket-able post change action.
	 *
	 * @since TBD
	 *
	 * @param int     $post_id   The post ID.
	 * @param WP_Post $post      The post object.
	 * @param bool    $is_update Whether the post is being updated.
	 *
	 * @return void
	 */
	public function fire_ticket_able_post_change_action( int $post_id, WP_Post $post, bool $is_update ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		$ticket_data = $this->get_ticket_data_by_provider( $post_id );

		$has_syncable_tickets = ! empty( $ticket_data->get_sync_able_tickets_of_event( $post_id ) );

		/**
		 * Fires when a ticket-able post is upserted.
		 *
		 * @since TBD
		 *
		 * @param int     $post_id              The post ID.
		 * @param WP_Post $post                 The post object.
		 * @param bool    $is_update            Whether the post is being updated.
		 * @param bool    $has_syncable_tickets Whether the post has sync-able tickets.
		 */
		do_action( 'tec_tickets_ticket_able_post_upserted', $post_id, $post, $is_update, $has_syncable_tickets );

		if ( $is_update ) {
			/**
			 * Fires when a ticket-able post is updated.
			 *
			 * @since TBD
			 *
			 * @param int     $post_id              The post ID.
			 * @param WP_Post $post                 The post object.
			 * @param bool    $has_syncable_tickets Whether the post has sync-able tickets.
			 */
			do_action( 'tec_tickets_ticket_able_post_updated', $post_id, $post, $has_syncable_tickets );
			return;
		}

		/**
		 * Fires when a ticket-able post is created.
		 *
		 * @since TBD
		 *
		 * @param int     $post_id              The post ID.
		 * @param WP_Post $post                 The post object.
		 * @param bool    $has_syncable_tickets Whether the post has sync-able tickets.
		 */
		do_action( 'tec_tickets_ticket_able_post_created', $post_id, $post, $has_syncable_tickets );
	}

	/**
	 * Get the ticket data by provider.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Ticket_Data The ticket data.
	 */
	private function get_ticket_data_by_provider( int $post_id ): Ticket_Data {
		$provider = Tickets::get_event_ticket_provider_object( $post_id );

		if ( $provider instanceof Commerce_Ticket_Provider ) {
			return tribe( Commerce_Ticket_Data::class );
		}

		return tribe( Ticket_Data::class );
	}

	/**
	 * Fire the ticket deleted action.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id                   The post ID.
	 * @param bool  $is_full_deletion          Whether the post is being fully deleted.
	 * @param ?int  $parent_id                 The parent ID.
	 * @param ?bool $has_more_syncable_tickets Whether the post has more sync-able tickets.
	 */
	private function fire_ticket_deleted_action( int $post_id, bool $is_full_deletion, ?int $parent_id, ?bool $has_more_syncable_tickets ): void {
		/**
		 * Fires when a ticket is deleted.
		 *
		 * @since TBD
		 *
		 * @param int  $post_id                    The post ID.
		 * @param bool $is_full_deletion           Whether the post is being fully deleted.
		 * @param ?int $parent_id                  The parent ID.
		 * @param ?bool $has_more_syncable_tickets Whether the post has more sync-able tickets.
		 */
		do_action( 'tec_tickets_ticket_deleted', $post_id, $is_full_deletion, $parent_id, $has_more_syncable_tickets );
	}
}
