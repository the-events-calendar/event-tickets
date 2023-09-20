<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee name.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/ticket/holder-name.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 * If you are looking for Event related templates, see in The Events Calendar plugin.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe__Template                    $this          Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email         The email object.
 * @var bool                               $preview       Whether the email is in preview mode or not.
 * @var bool                               $is_tec_active Whether `The Events Calendar` is active or not.
 * @var array                              $tickets       The list of tickets.
 * @var WP_Post                            $ticket        The ticket object.
 * @var WP_Post|null                       $event         The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $ticket['holder_name'] ) ) {
	return;
}

?>
<td class="tec-tickets__email-table-content-ticket-holder-name-container">
	<h2 class="tec-tickets__email-table-content-ticket-holder-name">
		<?php echo esc_html( $ticket['holder_name'] ); ?>
	</h2>
