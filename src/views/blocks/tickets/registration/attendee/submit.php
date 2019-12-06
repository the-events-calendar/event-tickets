<?php
/**
 * Block: Tickets
 * Registration Attendee Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/attendee/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 *
 */

?>
<button
	class="tribe-common-c-btn tribe-common-c-btn--small tribe-tickets__item__registration__submit"
	type="submit"
>
	<?php echo esc_html_x( 'Save & Check Out', 'Save attendee meta and proceed to checkout.', 'event-tickets' ); ?>
</button>
