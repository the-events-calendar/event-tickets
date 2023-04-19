<?php
/**
 * Event Tickets Emails: Main template > Body.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body.php
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

$this->template( 'template-parts/body/title' );

// @todo @codingmusician @juanfra: We need to move these to TEC and the `tickets` folder.
$this->template( 'template-parts/body/event/date' );

$this->template( 'template-parts/body/event/title' );

$this->template( 'template-parts/body/event/image' );

$this->template( 'template-parts/body/tickets' );

$this->template( 'template-parts/body/event/venue' );

$this->template( 'template-parts/body/event/links' );
