<?php
/**
 * Block: Tickets
 * Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 *
 * @version 4.11
 *
 */
$provider   = $this->get( 'provider' );
$must_login = ! is_user_logged_in() && $provider->login_required();
$event_id   = $this->get( 'event_id' );
$event      = get_post( $event_id );

/** @var \Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
$attendee_registration = tribe( 'tickets.attendee_registration' );

if ( $must_login ) {
	$this->template( 'blocks/tickets/submit-login' );
} elseif ( $attendee_registration->is_modal_enabled() ) {
	$this->template( 'blocks/tickets/submit-button-modal' );
} else {
	$this->template( 'blocks/tickets/submit-button' );
}
