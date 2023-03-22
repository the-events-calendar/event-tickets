<?php
/**
 * Event Tickets Emails: New Order Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/admin-new-order.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

$this->template( 'template-parts/header' );

$this->template( 'template-parts/body/title' );
$this->template( 'template-parts/body/order/purchaser-details' );
$this->template( 'template-parts/body/order/event-title' );
$this->template( 'template-parts/body/order/ticket-totals' );
$this->template( 'template-parts/body/order/order-total' );
// @todo @codingmusician @juanfra Get status from $order object and remove hardcoded status.
$this->template( 'template-parts/body/order/payment-info', [ 'status' => 'success' ] );
$this->template( 'template-parts/body/order/attendee-info' );

$this->template( 'template-parts/footer' );
