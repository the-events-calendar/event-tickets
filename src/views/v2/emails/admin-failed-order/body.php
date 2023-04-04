<?php
/**
 * Event Tickets Emails: Failed Order Template Body.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/failed-order/body.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template  $this  Current template object.
 */


$this->template( 'template-parts/body/title' );
$this->template( 'template-parts/body/order/error-message' );
$this->template( 'template-parts/body/order/purchaser-details' );
$this->template( 'template-parts/body/order/event-title' );
$this->template( 'template-parts/body/order/ticket-totals' );
$this->template( 'template-parts/body/order/order-total' );
$this->template( 'template-parts/body/order/payment-info' );
