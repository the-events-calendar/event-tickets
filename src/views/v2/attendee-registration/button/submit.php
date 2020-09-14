<?php
/**
 * Block: Tickets
 * Registration Attendee Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/button/submit.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 */

?>
<button
	class="tribe-common-c-btn tribe-common-c-btn--small tribe-tickets__item__registration__submit"
	type="submit"
>
	<?php echo esc_html_x( 'Save & Checkout', 'Save attendee meta and proceed to checkout.', 'event-tickets' ); ?>
</button>
