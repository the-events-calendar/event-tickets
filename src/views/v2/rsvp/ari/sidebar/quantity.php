<?php
/**
 * This template renders the RSVP ticket form quantity input.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/quantity.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-ar-quantity">
	<span class="tribe-common-h7">
		<?php esc_html_e( 'Total Guests', 'event-tickets' ); ?>
	</span>

	<div class="tribe-tickets__rsvp-ar-quantity-input">
		<?php $this->template( 'v2/rsvp/ari/sidebar/quantity/minus' ); ?>

		<?php $this->template( 'v2/rsvp/ari/sidebar/quantity/input', [ 'rsvp' => $rsvp ] ); ?>

		<?php $this->template( 'v2/rsvp/ari/sidebar/quantity/plus' ); ?>
	</div>

</div>
