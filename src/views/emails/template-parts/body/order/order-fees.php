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
 * @version TBD
 *
 * @since   TBD
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

if ( empty( $order ) || empty( $order->fees ) ) {
	return;
}

$fees = $order->fees;
?>


<tr>
	<td class="tec-tickets__email-table-content-order-fees-container" align="right">
		<table class="tec-tickets__email-table-content-order-fees-table">
			<?php foreach ( $fees as $fee ) : ?>
				<tr>
					<td class="tec-tickets__email-table-content-order-fees-name">
						<?php
						echo esc_html( $fee['display_name'] );
						if ( isset( $fee['quantity'] ) && $fee['quantity'] > 1 ) {
							printf(
								/* translators: %s: Quantity of a fee */
								' ' . esc_html_x( '(%sx)', 'Quantity of a fee with "x" after it, eg. "2x"', 'event-tickets' ),
								esc_html( $fee['quantity'] )
							);
						}
						?>
					</td>
					<td class="tec-tickets__email-table-content-order-fees-right-cell">
						<?php echo esc_html( tribe_format_currency( $fee['sub_total'] ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</td>
</tr>
