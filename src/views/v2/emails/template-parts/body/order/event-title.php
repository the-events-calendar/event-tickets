<?php
/**
 * Event Tickets Emails: Order Event Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/event-title.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var string                             $heading            The email heading.
 * @var string                             $title              The email title.
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var \WP_Post                           $order              The order object.
 */

// @todo @codingmusician: This needs to be moved to TEC if it's the event title.

if ( empty( $order->events_in_order ) ) {
	return;
}

$event = tribe_get_event( $order->events_in_order[0] );

if ( empty( $event ) || empty( $event->post_title ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-order-event-title">
		<?php echo esc_html( $event->post_title ); ?>
	</td>
</tr>
