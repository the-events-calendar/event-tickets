<?php
/**
 * Event Tickets Emails: Ticket Email Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/ticket.php
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

$this->template( 'template-parts/ticket/tickets' );

$this->template( 'template-parts/footer' );
