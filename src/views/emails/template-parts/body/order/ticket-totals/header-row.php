<?php
/**
 * Event Tickets Emails: Order Ticket Totals - Header Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/ticket-totals/header-row.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.26.3
 *
 * @since 5.5.11
 * @since 5.26.3 Tweaked the spacing of the columns to allow more space for fees and coupons.
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

?>
<tr class="tec-tickets__email-table-content-order-ticket-totals-header-row">
	<th style="width: 60%" class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		<?php echo esc_html__( 'Ticket', 'event-tickets' ); ?>
	</th>
	<th style="width: 25%" class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
		<?php echo esc_html__( 'Qty', 'event-tickets' ); ?>
	</th>
	<th style="width: 15%" class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
		<?php echo esc_html__( 'Price', 'event-tickets' ); ?>
	</th>
</tr>
