<?php
/**
 * Stores the ticket's name on each Tickets Commerce attendee when the attendee is created.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Attendees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
final class Attendees {
	/**
	 * The attendee meta key holding the ticket's name as it was when the attendee was created.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TICKET_NAME_META_KEY = '_tec_tickets_commerce_ticket_name';

	/**
	 * Adds the ticket's name to the attendee create arguments.
	 *
	 * The attendees repository stores any argument it has no alias for as post meta under the argument's key.
	 *
	 * @since TBD
	 *
	 * @param array|mixed $create_args The arguments used to create the attendee.
	 * @param mixed       $order       The order that generated the attendee.
	 * @param mixed       $ticket      The ticket that generated the attendee.
	 *
	 * @return array|mixed The create arguments, with the ticket's name when there is one.
	 */
	public function add_ticket_name( $create_args, $order, $ticket ) {
		// Other callbacks on this filter can hand back anything.
		if ( ! is_array( $create_args ) || ! $ticket instanceof Ticket_Object || '' === (string) $ticket->name ) {
			return $create_args;
		}

		$create_args[ self::TICKET_NAME_META_KEY ] = $ticket->name;

		return $create_args;
	}
}
