<?php
/**
 * Event Tickets Emails: New Order Template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/new-order.php
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

 // @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.

$this->template( 'template-parts/header' );
$this->template( 'admin-new-order/body' );
$this->template( 'template-parts/footer' );
