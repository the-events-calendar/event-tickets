<?php
/**
 * This template renders the RSVP AR form title.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/template/title.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<header>
	<h3 class="tribe-tickets__rsvp-ar-form-title tribe-common-h5" data-guest-number="{{data.attendee_id + 1}}">
		<?php echo esc_html( tribe_get_guest_label_singular( 'RSVP attendee registration form title' ) ); ?>
	</h3>
</header>
