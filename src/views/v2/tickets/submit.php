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
 * @link http://m.tri.be/1amp
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

// @todo Convert this into an action.
// $this->template( 'v2/tickets/submit/button-modal' );

$this->template( 'v2/tickets/submit/button' );
