<?php
/**
 * Event Tickets Emails: Order Ticket Totals
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/ticket-totals.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.26.3
 *
 * @since 5.5.11
 * @since 5.26.3 Added the fees, coupons, and total rows.
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

use TEC\Tickets\Emails\Email_Abstract;

if ( empty( $order->items ) ) {
	return;
}

?>
<tr>
	<td>
		<table style="border-collapse:collapse;margin-top:10px">
			<?php $this->template( 'template-parts/body/order/ticket-totals/header-row' ); ?>
			<?php foreach ( $order->items as $cart_item ) : ?>
				<?php $this->template( 'template-parts/body/order/ticket-totals/ticket-row', [ 'cart_item' => $cart_item ] ); ?>
			<?php endforeach; ?>
			<?php $this->template( 'template-parts/body/order/ticket-totals/fees-row' ); ?>
			<?php $this->template( 'template-parts/body/order/ticket-totals/coupons-row' ); ?>
			<?php $this->template( 'template-parts/body/order/ticket-totals/total-row' ); ?>
		</table>
	</td>
</tr>
