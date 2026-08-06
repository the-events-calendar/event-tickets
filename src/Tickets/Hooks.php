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
 * @since 5.1.6
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Payments_Tab;
use WP_Query;
use WP_Post;

/**
 * Class Hooks.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets
 */
class Hooks extends Service_Provider {

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
	 * @since 5.29.2 Added the `tec_tickets_commerce_attendee_after_archive` action for uncheck-in on archive.
	 */
	protected function add_actions() {
		$this->container->register( Ticket_Cache_Controller::class );
		add_action( 'tec_tickets_commerce_attendee_after_archive', [ $this, 'uncheckin_attendee_on_archive' ] );
	}

	/**
	 * Revokes an Attendee's check-in status when a held gateway webhook is resolved and their
	 * order is archived (e.g. a deferred Stripe refund that applies after the checkout hold window).
	 *
	 * During the hold window an Attendee can legitimately check in before a deferred webhook that
	 * would move the order away from a completed status is applied. Once that webhook does apply
	 * (via the async processor) and the order's Attendees are archived, any check-in that happened
	 * in that window is stale and must be revoked.
	 *
	 * Only unchecks when the archive was triggered by a held-webhook resolution (detected via a
	 * post meta flag set by the gateway's async processor). Post-event refunds that archive
	 * attendees outside the hold window are left alone so legitimate attendance history.
	 *
	 * @since 5.29.2
	 *
	 * @param int $attendee_id The Attendee post ID that was just archived.
	 */
	public function uncheckin_attendee_on_archive( int $attendee_id ): void {
		$module = tribe( Module::class );

		if ( ! get_post_meta( $attendee_id, $module->checkin_key, true ) ) {
			return;
		}

		// Tickets Commerce stores the order relationship as post_parent on the attendee.
		$order_id = wp_get_post_parent_id( $attendee_id );

		// Only act when the archive is part of resolving a held gateway webhook.
		if ( ! $order_id || ! get_post_meta( $order_id, '_tec_tickets_commerce_webhook_resolving_archive', true ) ) {
			return;
		}

		$module->uncheckin( $attendee_id );
	}

	/**
	 * Provides the results for the events dropdown in the Orders table.
	 *
	 * @since 5.20.0
	 *
	 * @param array<string,mixed>  $results The results.
	 * @param array<string,string> $search The search.
	 *
	 * @return array<string,mixed>
	 */
	public function provide_events_results_to_ajax( $results, $search ) {
		if ( empty( $search['term'] ) ) {
			return $results;
		}

		$term = $search['term'];

		$args = [
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'post_type'              => (array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			'post_status'            => 'any',
			'posts_per_page'         => 10,
			's'                      => $term,
			// Default to show most recent first.
			'orderby'                => 'ID',
			'order'                  => 'DESC',
		];

		$query = new WP_Query( $args );

		if ( empty( $query->posts ) ) {
			return $results;
		}

		$results = array_map(
			function ( WP_Post $result ) {
				return [
					'id'   => $result->ID,
					'text' => get_the_title( $result->ID ),
				];
			},
			$query->posts
		);

		return [ 'results' => $results ];
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
	 * @since 5.29.2 Added the `tec_tickets_attendee_checkin` filter.
	 */
	protected function add_filters() {
		add_filter( 'tribe_dropdown_tec_tickets_list_ticketables_ajax', [ $this, 'provide_events_results_to_ajax' ], 10, 2 );
		add_filter( 'tec_tickets_attendee_checkin', [ $this, 'prevent_checkin_for_invalid_order_status' ], 10, 2 );
	}

	/**
	 * Prevents an Attendee from being checked in when the order backing it is not in a status
	 * that counts as "completed" for its provider (e.g. an order that has been refunded).
	 *
	 * This is a defense-in-depth guard: it runs regardless of the entry point (admin AJAX, QR
	 * redirect, REST API, etc.) since it hooks directly into `Tribe__Tickets__Tickets::checkin()`.
	 *
	 * @since 5.29.2
	 *
	 * @param bool|null $checkin     The current filtered value; a non-null value here means another
	 *                               callback already decided the outcome, so we defer to it.
	 * @param int       $attendee_id The post ID of the Attendee being checked in.
	 *
	 * @return bool|null
	 */
	public function prevent_checkin_for_invalid_order_status( $checkin, int $attendee_id ): ?bool {
		if ( null !== $checkin ) {
			return $checkin;
		}

		/*
		 * A non-completed order (e.g. refunded, not-completed) archives its Attendees by trashing them.
		 * Trashed Attendees are excluded from the provider's default Attendee queries below, which would
		 * otherwise make this guard fail open (allow check-in) for exactly the case it needs to block.
		 */
		if ( 'trash' === get_post_status( $attendee_id ) ) {
			return false;
		}

		$ticket_provider = tribe( 'tickets.data_api' )->get_ticket_provider( $attendee_id );

		if ( ! $ticket_provider ) {
			return $checkin;
		}

		$attendee = $ticket_provider->get_attendees_by_id( $attendee_id );
		$attendee = reset( $attendee );

		if ( ! is_array( $attendee ) || ! isset( $attendee['order_status'] ) ) {
			return $checkin;
		}

		$completed_statuses = (array) tribe( 'tickets.status' )->get_completed_status_by_provider_name( $ticket_provider );

		if ( empty( $completed_statuses ) ) {
			return $checkin;
		}

		if ( ! in_array( $attendee['order_status'], $completed_statuses, true ) ) {
			return false;
		}

		/**
		 * Filters whether the order has a pending non-completed transition (e.g. a deferred refund webhook).
		 *
		 * @since 5.29.2
		 *
		 * @param bool $has_pending Whether the order has a pending non-completed transition.
		 * @param int  $order_id    The order ID.
		 */
		$has_pending = (bool) apply_filters( 'tec_tickets_commerce_order_has_pending_non_completed_transition', false, (int) $attendee['order_id'] );

		if ( ! empty( $attendee['order_id'] ) && $has_pending ) {
			return false;
		}

		return $checkin;
	}
}
