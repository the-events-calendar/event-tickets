<?php
/**
 * Recurring event tickets in the block editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */

namespace TEC\Tickets\Recurring_Tickets\Editor;

use TEC\Tickets\Event;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_REST_Request;

/**
 * Lists, in a date's Tickets block, the event's recurring event tickets rather than the date's copies of them, and
 * sends the Tickets by Date section's data with the event the editor loads and saves.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Editor
 */
final class Block_Editor {
	/**
	 * The REST field holding the Tickets by Date section's data.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const FIELD = 'tec_tickets_by_date';

	/**
	 * The Tickets by Date section's data.
	 *
	 * @since TBD
	 *
	 * @var Tickets_By_Date
	 */
	private Tickets_By_Date $tickets_by_date;

	/**
	 * Block_Editor constructor.
	 *
	 * @since TBD
	 *
	 * @param Tickets_By_Date $tickets_by_date The Tickets by Date section's data.
	 */
	public function __construct( Tickets_By_Date $tickets_by_date ) {
		$this->tickets_by_date = $tickets_by_date;
	}

	/**
	 * Adds the Tickets by Date section's data to an event, or one of its dates, in the REST API's edit context: the
	 * block editor loads the event from it, and gets it back from every save.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_tickets_by_date_field(): void {
		register_rest_field(
			TEC::POSTTYPE,
			self::FIELD,
			[
				'get_callback' => fn( $post, $field, $request ) => $request instanceof WP_REST_Request && 'edit' === $request['context']
					? $this->tickets_by_date->for_event( (int) Event::filter_event_id( (int) ( $post['id'] ?? 0 ), 'recurring-tickets-by-date' ) )
					: null,
				'schema'       => [
					'description' => __( 'The Tickets by Date section of a recurring event, as of its last save.', 'event-tickets' ),
					'type'        => [ 'object', 'null' ],
					'context'     => [ 'edit' ],
					'readonly'    => true,
				],
			]
		);
	}
	/**
	 * Removes a date's copies of the recurring event tickets from the tickets the Tickets block loads.
	 *
	 * A date shares its event's content, so its Tickets block already holds the event's tickets; a copy would be
	 * added to it as a ticket of its own.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object[]|mixed $tickets The tickets the block loads.
	 *
	 * @return Ticket_Object[]|mixed The tickets, without a date's copies.
	 */
	public function remove_date_copies( $tickets ) {
		if ( ! is_array( $tickets ) ) {
			return $tickets;
		}

		return array_values(
			array_filter(
				$tickets,
				static fn( $ticket ) => ! ( $ticket instanceof Ticket_Object && Ticket_ID::is_table_ticket( $ticket->ID ) )
			)
		);
	}
}
