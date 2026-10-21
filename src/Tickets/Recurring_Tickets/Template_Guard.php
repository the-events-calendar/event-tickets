<?php
/**
 * Keeps the templates of recurring event tickets from being shown to customers or sold.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Template_Guard.
 *
 * A template is the ticket post an admin creates on a recurring event; each date sells a row made from it. Selling
 * the template itself would oversell every date and create attendees that belong to no date.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Template_Guard {
	/**
	 * The ticket type of templates and of their rows.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TICKET_TYPE = 'recurring';

	/**
	 * Whether a ticket is a template: a ticket post, not a row, of the recurring type.
	 *
	 * Reads the post's type only, so it holds with ECP off.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool Whether the ticket is a template.
	 */
	public function is_template( int $ticket_id ): bool {
		return ! Ticket_ID::is_table_ticket( $ticket_id ) && self::TICKET_TYPE === get_post_meta( $ticket_id, '_type', true );
	}

	/**
	 * Drops templates from a ticket list built for customers.
	 *
	 * Only someone who can edit the post, asking from the admin or over REST as the editors do, keeps them. Front-end
	 * AJAX runs in the admin and the calendar views navigate over REST, so neither alone means an editor is asking.
	 * Selling a template is refused by the cart either way.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object[] $tickets The tickets.
	 * @param int|string      $post_id The post the tickets were asked for.
	 *
	 * @return Ticket_Object[] The tickets, without templates unless an editor asks.
	 */
	public function drop_from_front_end( array $tickets, $post_id = 0 ): array {
		if ( ( is_admin() || wp_is_serving_rest_request() ) && current_user_can( 'edit_post', (int) $post_id ) ) {
			return $tickets;
		}

		return array_values(
			array_filter(
				$tickets,
				fn( $ticket ) => ! ( $ticket instanceof Ticket_Object && $this->is_template( (int) $ticket->ID ) )
			)
		);
	}
}
