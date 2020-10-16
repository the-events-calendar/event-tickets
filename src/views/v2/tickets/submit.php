<?php
/**
 * Block: Tickets
 * Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/submit.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $is_mini  True if it's in mini cart context.
 * @var bool $is_modal True if it's in modal context.
 */

if (
	! empty( $is_modal )
	|| ! empty( $is_mini )
) {
	return;
}

$this->template( 'v2/tickets/submit/must-login' );

$this->template( 'v2/tickets/submit/button' );
