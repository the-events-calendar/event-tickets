<?php
/**
 * Event Tickets Emails: RSVP "Not Going" Email Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/rsvp-not-going.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since 5.5.10
 *
 * @var Tribe_Template  $this  Current template object.
 */

$this->template( 'template-parts/header' );

$this->template( 'rsvp-not-going/body' );

$this->template( 'template-parts/footer' );
