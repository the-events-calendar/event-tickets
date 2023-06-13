<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets..hooks' ), 'some_method' ] );
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use tad_DI52_ServiceProvider;
use TEC\Tickets\Commerce\Payments_Tab;
use Tribe__Tickets__Ticket_Object as Ticket;

/**
 * Class Hooks.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets
 */
class Hooks extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets component.
	 *
	 * @since 5.1.6
	 */
	protected function add_actions() {
		add_action( 'tribe_settings_do_tabs', [ tribe( Payments_Tab::class ), 'register_tab' ], 15 );
		add_action( 'tribe_settings_after_save_' . Payments_Tab::$slug, [ $this, 'generate_payments_pages' ] );
		add_action( 'event_tickets_after_save_ticket', [ $this, 'clean_ticket_cache_on_save' ], 10, 2 );
		add_action( 'clean_post_cache', [ $this, 'clean_ticket_cache' ], 10, 2 );
	}

	/**
	 * Generate TicketsCommerce Pages.
	 *
	 * @since 5.2.1
	 */
	public function generate_payments_pages() {
		$this->container->make( Payments_Tab::class )->maybe_generate_pages();
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ tribe( Payments_Tab::class ), 'settings_add_tab_id' ] );
	}

	/**
	 * Clean the ticket cache when a ticket is saved.
	 *
	 * The ticket cache will be rehydrated on the next request for the Ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The ID of the Post the ticket is attached to, unused by this method.
	 * @param Ticket $ticket  The Ticket object that was saved.
	 *
	 * @return void
	 */
	public function clean_ticket_cache_on_save( $post_id, $ticket ): void {
		if ( ! $ticket instanceof Ticket ) {
			return;
		}

		wp_cache_delete( (int) $ticket->ID, 'tec_tickets' );
	}

	/**
	 * Clean the ticket cache when the post cache is cleaned.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the Ticket post.
	 *
	 * @return void
	 */
	public function clean_ticket_cache( $post_id ): void {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		// Checking the post type would require more time (due to filtering) than trying to delete a non-existing key.
		wp_cache_delete( (int) $post_id, 'tec_tickets' );
	}
}