<?php
/**
 * Aggregate attendee queries across translations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Tickets
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Tickets;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;

/**
 * Class Attendee_Aggregator
 *
 * Aggregates attendee queries across all language translations of tickets and events.
 *
 * @since TBD
 */
class Attendee_Aggregator {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter $wpml WPML adapter instance.
	 */
	public function __construct( Wpml_Adapter $wpml ) {
		$this->wpml = $wpml;
	}

	/**
	 * Register hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'tribe_repository_tc_attendees_pre_get_posts', [ $this, 'handle_tc_attendees_query' ] );
		add_action( 'tribe_repository_attendees_pre_get_posts', [ $this, 'handle_rsvp_attendees_query' ] );

		add_filter( 'tec_tickets_attendees_filter_by_event', [ $this, 'expand_event_ids' ] );
		add_filter( 'tribe_tickets_rsvp_get_ticket', [ $this, 'aggregate_rsvp_qty_sold' ], 10, 3 );
	}

	/**
	 * Expand TC attendees meta query to include all translated ticket IDs.
	 *
	 * @since TBD
	 *
	 * @param object $query Repository query.
	 *
	 * @return void
	 */
	public function handle_tc_attendees_query( $query ): void {
		$query_vars = $this->get_query_vars_reference( $query );
		if ( null === $query_vars ) {
			return;
		}

		if ( ! isset( $query_vars['meta_query']['_tec_tickets_commerce_ticket_equals']['value'] ) ) {
			return;
		}

		$ticket_id = (int) $query_vars['meta_query']['_tec_tickets_commerce_ticket_equals']['value'];
		if ( $ticket_id <= 0 ) {
			return;
		}

		$ids = $this->wpml->get_translation_ids( $ticket_id );
		if ( empty( $ids ) ) {
			return;
		}

		$query_vars['meta_query']['_tec_tickets_commerce_ticket_equals']['compare'] = 'IN';
		$query_vars['meta_query']['_tec_tickets_commerce_ticket_equals']['value']   = $ids;
	}

	/**
	 * Expand RSVP attendees meta query to include all translated ticket IDs.
	 *
	 * @since TBD
	 *
	 * @param object $query Repository query.
	 *
	 * @return void
	 */
	public function handle_rsvp_attendees_query( $query ): void {
		$query_vars = $this->get_query_vars_reference( $query );
		if ( null === $query_vars ) {
			return;
		}

		if ( ! isset( $query_vars['meta_query']['_tribe_rsvp_product_in']['value'] ) ) {
			return;
		}

		$ticket_id = (int) $query_vars['meta_query']['_tribe_rsvp_product_in']['value'];
		if ( $ticket_id <= 0 ) {
			return;
		}

		$ids = $this->wpml->get_translation_ids( $ticket_id );
		if ( empty( $ids ) ) {
			return;
		}

		$query_vars['meta_query']['_tribe_rsvp_product_in']['value'] = $ids;
	}

	/**
	 * Expand event IDs to include all translations.
	 *
	 * @since TBD
	 *
	 * @param array<int> $ids Event IDs.
	 *
	 * @return array<int>
	 */
	public function expand_event_ids( $ids ): array {
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return (array) $ids;
		}

		$result = [];

		foreach ( $ids as $id ) {
			$id = (int) $id;

			if ( $id <= 0 ) {
				continue;
			}

			$result = array_merge( $result, $this->wpml->get_translation_ids( $id ) );
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $result ) ) ) );
	}

	/**
	 * Aggregate RSVP qty sold across translations.
	 *
	 * @since TBD
	 *
	 * @param object $ticket Ticket object.
	 * @param int    $event_id Event ID.
	 * @param int    $ticket_id Ticket ID.
	 *
	 * @return object Modified ticket object with aggregated qty_sold.
	 */
	public function aggregate_rsvp_qty_sold( $ticket, $event_id, $ticket_id ) {
		$ticket_id = (int) $ticket_id;

		if ( $ticket_id <= 0 || ! is_object( $ticket ) ) {
			return $ticket;
		}

		$ids = $this->wpml->get_translation_ids( $ticket_id );
		if ( empty( $ids ) ) {
			return $ticket;
		}

		$sold = 0;

		foreach ( $ids as $id ) {
			$id_sold = get_post_meta( (int) $id, 'total_sales', true );
			$sold   += is_numeric( $id_sold ) ? (int) $id_sold : 0;
		}

		$ticket->qty_sold = $sold;

		return $ticket;
	}

	/**
	 * Get a reference to the query vars array, if available.
	 *
	 * @since TBD
	 *
	 * @param object $query Query object.
	 *
	 * @return array|null Reference to query_vars array, or null if not available.
	 */
	private function &get_query_vars_reference( $query ) {
		$null = null;

		if ( ! is_object( $query ) ) {
			return $null;
		}

		if ( ! isset( $query->query_vars ) || ! is_array( $query->query_vars ) ) {
			return $null;
		}

		// Return reference to allow modification.
		return $query->query_vars;
	}
}


