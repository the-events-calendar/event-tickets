<?php
/**
 * Event Tickets Emails: New Order Template Body
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/new-order/body.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
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

$this->template( 'template-parts/body/title' );

$this->template( 'template-parts/body/order/customer-purchaser-details' );

$this->template( 'template-parts/body/order/post-title' );

$this->template( 'template-parts/body/order/ticket-totals' );

$this->template( 'template-parts/body/order/order-total' );

$this->template( 'template-parts/body/order/order-gateway-data' );

$this->template( 'template-parts/body/order/payment-info' );

$this->template( 'template-parts/body/order/attendees-table' );

$this->template( 'template-parts/body/additional-content' );
