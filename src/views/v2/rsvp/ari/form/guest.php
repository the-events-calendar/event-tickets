<?php
/**
 * This template renders the RSVP AR form guest.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/guest.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>

<div
	class="tribe-tickets__rsvp-ar-form-guest"
	data-guest-number="1"
>
	<?php $this->template( 'v2/rsvp/ari/form/title', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/ari/form/fields', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/ari/form/buttons', [ 'rsvp' => $rsvp ] ); ?>

</div>
