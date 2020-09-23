<?php
/**
 * This template renders the Attendee Registration submit button.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/button/submit.php
 *
 * @link    http://m.tri.be/1amp See more documentation about our views templating system.
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
