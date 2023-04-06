<?php
/**
 * Event Tickets Emails: Purchase Receipt Body template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/purchase-receipt/body.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template   $this                  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

 // @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.

$this->template( 'template-parts/body/title' );
$this->template( 'purchase-receipt/greeting' );
$this->template( 'template-parts/body/order/purchaser-details' );
$this->template( 'template-parts/body/order/event-title' );
$this->template( 'template-parts/body/order/ticket-totals' );
$this->template( 'template-parts/body/order/order-total' );
$this->template( 'template-parts/body/order/payment-info' );
$this->template( 'template-parts/body/order/attendees-table' );
$this->template( 'template-parts/body/add-content' );
