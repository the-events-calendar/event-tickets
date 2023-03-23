<?php
/**
 * Event Tickets Emails: New Order Template Body
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/new-order/body.php
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

 // @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.

$this->template( 'template-parts/body/title' );
$this->template( 'template-parts/body/order/purchaser-details' );
$this->template( 'template-parts/body/order/event-title' );
$this->template( 'template-parts/body/order/ticket-totals' );
$this->template( 'template-parts/body/order/order-total' );
$this->template( 'template-parts/body/order/payment-info' );
$this->template( 'template-parts/body/order/attendee-info' );
