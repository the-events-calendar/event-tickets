<?php
/**
 * Tickets Emails Email Template
 *
 * @since  TBD  Email template to be mailed out by the Event Tickets plugin.
 * 
 * @var Tribe_Template  $this  Current template object.
 */


if ( tribe_is_truthy( $preview ) ) {
	$this->template( 'email-template/preview' );
} else {
	$this->template( 'email-template/main' );
}

