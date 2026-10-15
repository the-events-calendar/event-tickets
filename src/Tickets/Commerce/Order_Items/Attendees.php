<?php
/**
 * Stores the ticket's name on each Tickets Commerce attendee when the attendee is created.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Tickets\Commerce\Attendee;
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
		// Other callbacks on this filter can hand back anything, and the repository unslashes the meta it saves.
		if ( ! is_array( $create_args ) || ! $ticket instanceof Ticket_Object || ! is_string( $ticket->name ) || '' === $ticket->name ) {
			return $create_args;
		}

		$create_args[ self::TICKET_NAME_META_KEY ] = wp_slash( $ticket->name );

		return $create_args;
	}

	/**
	 * Keeps the stored ticket name in step with the ticket an attendee was moved to.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id        The moved attendee's ID.
	 * @param int $src_ticket_type_id The ticket the attendee came from.
	 * @param int $tgt_ticket_type_id The ticket the attendee now belongs to.
	 *
	 * @return void
	 */
	public function update_ticket_name_on_move( $attendee_id, $src_ticket_type_id, $tgt_ticket_type_id ): void {
		if ( Attendee::POSTTYPE !== get_post_type( $attendee_id ) ) {
			return;
		}

		$name = get_post_field( 'post_title', $tgt_ticket_type_id );

		if ( '' === $name ) {
			delete_post_meta( $attendee_id, self::TICKET_NAME_META_KEY );

			return;
		}

		update_post_meta( $attendee_id, self::TICKET_NAME_META_KEY, wp_slash( $name ) );
	}
}
