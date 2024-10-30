<?php
/**
 * Event Tickets Emails: Order Post Title, displays the Post Title for the post containing the tickets.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/post-title.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.6.0
 *
 * @since 5.6.0
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

if ( empty( $order->events_in_order ) ) {
	return;
}

$event = get_post( $order->events_in_order[0] );

if ( empty( $event ) || empty( $event->post_title ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-order-post-title">
		<?php echo esc_html( $event->post_title ); ?>
	</td>
</tr>
