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
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return bool Whether the ticket is a template.
	 */
	public function is_template( Ticket_Object $ticket ): bool {
		return ! Ticket_ID::is_table_ticket( $ticket->ID ) && self::TICKET_TYPE === $ticket->type();
	}
}
