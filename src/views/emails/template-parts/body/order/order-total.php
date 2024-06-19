<?php
/**
 * Event Tickets Emails: Order Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/order-total.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
 * @since 5.10.0 Allow for zero value total.
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

if ( empty( $order ) || empty( $order->total_value ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-order-total-container" align="right">
		<table class="tec-tickets__email-table-content-order-total-table">
			<tr>
				<td class="tec-tickets__email-table-content-order-total-left-cell">
					<?php echo esc_html__( 'Order Total', 'event-tickets' ); ?>
				</td>
				<td class="tec-tickets__email-table-content-order-total-right-cell">
					<?php echo esc_html( $order->total_value->get_currency() ); ?>
				</td>
			</tr>
		</table>
	</td>
</tr>