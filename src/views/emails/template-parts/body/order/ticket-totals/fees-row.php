<?php
/**
 * Event Tickets Emails: Order Ticket Totals - Fees Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/ticket-totals/fees-row.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.26.3
 *
 * @since 5.26.3
 *
 * @var Tribe__Template $this               Current template object.
 * @var Email_Abstract  $email              The email object.
 * @var string          $heading            The email heading.
 * @var string          $title              The email title.
 * @var bool            $preview            Whether the email is in preview mode or not.
 * @var string          $additional_content The email additional content.
 * @var bool            $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var \WP_Post        $order              The order object.
 */

use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Emails\Email_Abstract;

// If there are no fees, we don't need to display anything.
if ( empty( $order ) || empty( $order->fees ) ) {
	return;
}

$fees = array_map(
	fn( float $value ) => Currency_Value::create_from_float( $value ),
	wp_list_pluck( $order->fees, 'sub_total' )
);

$total_fees = Currency_Value::sum( ...$fees );

?>
<tr class="tec-tickets__email-table-content-order-ticket-totals-fees-row">
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		&nbsp;
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
		<?php esc_html_e( 'Fees:', 'event-tickets' ); ?>
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
		<?php echo esc_html( $total_fees->get() ); ?>
	</td>
</tr>
