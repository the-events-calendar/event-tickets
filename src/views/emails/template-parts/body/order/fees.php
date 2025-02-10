<?php
/**
 * Tickets Commerce: Email Fees Section
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/fees.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
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

$fees = $order->fees;

if ( empty( $fees ) ) {
	return;
}
?>


<tr>
	<td class="tec-tickets__email-table-content-order-total-container" align="right">
		<table class="tec-tickets__email-table-content-fees-total-table">
			<tr>
				<td class="tec-tickets__email-table-content-fees-total-left-cell">
					<strong><?php esc_html_e( 'Booking Fees', 'event-tickets' ); ?></strong>
				</td>
				<td class="tec-tickets__email-table-content-fees-total-right-cell">
					<?php echo esc_html( tribe_format_currency( 'Total Fees' ) ); ?>
				</td>
			</tr>
		</table>
	</td>
</tr>
