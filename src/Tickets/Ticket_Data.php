<?php
/**
 * Ticket Data methods.
 *
 * @since 5.24.0
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__RSVP as RSVP;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Generator;

/**
 * Class Ticket_Data.
 *
 * @since 5.24.0
 * @package TEC\Tickets
 */
class Ticket_Data {
	/**
	 * The excluded ticket types.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	protected array $excluded_ticket_types = [ 'rsvp', 'edd', Series_Passes::TICKET_TYPE ];

	/**
	 * Set the excluded ticket types.
	 *
	 * @since 5.24.0
	 *
	 * @param array $excluded_ticket_types The excluded ticket types.
	 */
	public function set_excluded_ticket_types( array $excluded_ticket_types ): void {
		$this->excluded_ticket_types = $excluded_ticket_types;
	}

	/**
	 * Get the ticket types.
	 *
	 * @since 5.24.0
	 *
	 * @return array The ticket types.
	 */
	protected function get_ticket_types(): array {
		return array_values(
			array_filter(
				tribe_tickets()->ticket_types(),
				fn( $key ) => ! in_array( $key, $this->excluded_ticket_types, true ),
				ARRAY_FILTER_USE_KEY
			)
		);
	}

	/**
	 * Load the ticket object.
	 *
	 * @since 5.24.0
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return Ticket_Object|null The ticket object.
	 */
	public function load_ticket_object( int $ticket_id ): ?Ticket_Object {
		return Tickets::load_ticket_object( $ticket_id );
	}

	/**
	 * Get the tickets for a post.
	 *
	 * @since 5.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Generator<Ticket_Object> The ticket.
	 */
	public function get_posts_tickets( int $post_id ): Generator {
		$ticket_types = $this->get_ticket_types();

		foreach (
			tribe_tickets()
				->where( 'event', $post_id )
				->where( 'post_type', $ticket_types )
				->get_ids( true ) as $ticket_id
			) {

			if ( ! $ticket_id ) {
				continue;
			}

			$ticket = $this->load_ticket_object( $ticket_id );

			if ( ! $ticket instanceof Ticket_Object ) {
				continue;
			}

			if ( $ticket->get_event_id() !== $post_id ) {
				// This is a series ticket which is coming from the series!
				continue;
			}

			yield $ticket;
		}
	}

	/**
	 * Get the RSVP for a post.
	 *
	 * @since 5.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Ticket_Object|null The ticket object or null if not found.
	 */
	public function get_posts_rsvp( int $post_id ): ?Ticket_Object {
		$rsvp = tribe_tickets( 'rsvp' )->where( 'event', $post_id )->first();

		if ( ! $rsvp ) {
			return null;
		}

		$rsvp = $this->load_ticket_object( $rsvp->ID );

		if ( ! $rsvp instanceof Ticket_Object ) {
			return null;
		}

		return $rsvp;
	}

	/**
	 * Get the ticket data for a post.
	 *
	 * @since 5.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The ticket data.
	 */
	public function get_posts_tickets_data( int $post_id ): array {
		$ticket_count                   = 0;
		$availability                   = [];
		$tickets_on_sale                = [];
		$tickets_have_not_started_sales = [];
		$tickets_have_ended_sales       = [];
		$tickets_about_to_go_to_sale    = [];

		foreach ( $this->get_posts_tickets( $post_id ) as $ticket ) {
			++$ticket_count;

			$this->count_ticket_stats( $ticket, $availability, $tickets_on_sale, $tickets_have_not_started_sales, $tickets_have_ended_sales, $tickets_about_to_go_to_sale );
		}

		return [
			'ticket_count'                   => $ticket_count,
			'availability'                   => $availability,
			'tickets_on_sale'                => $tickets_on_sale,
			'tickets_have_not_started_sales' => $tickets_have_not_started_sales,
			'tickets_have_ended_sales'       => $tickets_have_ended_sales,
			'tickets_about_to_go_to_sale'    => $tickets_about_to_go_to_sale,
		];
	}

	/**
	 * Get the RSVP data for a post.
	 *
	 * @since 5.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The RSVP data.
	 */
	public function get_posts_rsvps_data( int $post_id ): array {
		$rsvp = $this->get_posts_rsvp( $post_id );

		$availability                   = [];
		$tickets_on_sale                = [];
		$tickets_have_not_started_sales = [];
		$tickets_have_ended_sales       = [];
		$tickets_about_to_go_to_sale    = [];

		if ( ! $rsvp ) {
			return [
				'ticket_count'                   => 0,
				'availability'                   => $availability,
				'tickets_on_sale'                => $tickets_on_sale,
				'tickets_have_not_started_sales' => $tickets_have_not_started_sales,
				'tickets_have_ended_sales'       => $tickets_have_ended_sales,
				'tickets_about_to_go_to_sale'    => $tickets_about_to_go_to_sale,
			];
		}

		$this->count_ticket_stats( $rsvp, $availability, $tickets_on_sale, $tickets_have_not_started_sales, $tickets_have_ended_sales, $tickets_about_to_go_to_sale );

		return [
			'ticket_count'                   => 1,
			'availability'                   => $availability,
			'tickets_on_sale'                => $tickets_on_sale,
			'tickets_have_not_started_sales' => $tickets_have_not_started_sales,
			'tickets_have_ended_sales'       => $tickets_have_ended_sales,
			'tickets_about_to_go_to_sale'    => $tickets_about_to_go_to_sale,
		];
	}

	/**
	 * Count the ticket stats.
	 *
	 * @since 5.24.0
	 *
	 * @param Ticket_Object $ticket                         The ticket object.
	 * @param array         $availability                   The availability array.
	 * @param array         $tickets_on_sale                The tickets on sale array.
	 * @param array         $tickets_have_not_started_sales The tickets have not started sales array.
	 * @param array         $tickets_have_ended_sales       The tickets have ended sales array.
	 * @param array         $tickets_about_to_go_to_sale    The tickets about to go to sale array.
	 *
	 * @return void
	 */
	protected function count_ticket_stats( Ticket_Object $ticket, array &$availability, array &$tickets_on_sale, array &$tickets_have_not_started_sales, array &$tickets_have_ended_sales, array &$tickets_about_to_go_to_sale ): void {
		$available     = $ticket->available();
		$start_sale_ts = $ticket->start_date( true );
		$end_sale_ts   = $ticket->end_date( true );

		if ( $available > 0 || -1 === $available ) {
			$sold = RSVP::class !== $ticket->provider_class ?
				$ticket->qty_sold() + $ticket->qty_pending() :
				count( $ticket->get_provider()->get_attendees_by_id( $ticket->ID ) );

			$availability[ $ticket->ID ] = [
				'available' => $available,
				'sold'      => $sold,
			];

			if ( $start_sale_ts < time() && $end_sale_ts > time() ) {
				$tickets_on_sale[] = $ticket->ID;
			}

			if ( $start_sale_ts - self::get_ticket_about_to_go_to_sale_seconds( $ticket->ID ) <= time() && $start_sale_ts > time() && $end_sale_ts > time() ) {
				$tickets_about_to_go_to_sale[] = $ticket->ID;
			}

			if ( $start_sale_ts > time() ) {
				$tickets_have_not_started_sales[] = $ticket->ID;
			}
		}

		if ( $end_sale_ts < time() ) {
			$tickets_have_ended_sales[] = $ticket->ID;
		}
	}

	/**
	 * Get the ticket about to go to sale seconds.
	 *
	 * @since 5.24.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The ticket about to go to sale seconds.
	 */
	public static function get_ticket_about_to_go_to_sale_seconds( int $ticket_id ): int {
		/**
		 * Filter the seconds before a ticket goes on sale that we consider it about to go on sale.
		 *
		 * @since 5.24.0
		 *
		 * @param int $seconds The seconds before a ticket goes on sale that we consider it about to go on sale.
		 * @return int The seconds before a ticket goes on sale that we consider it about to go on sale.
		 */
		return (int) apply_filters( 'tec_tickets_ticket_about_to_go_to_sale_seconds', 20 * MINUTE_IN_SECONDS, $ticket_id );
	}
}
