<?php
/**
 * Block: RSVP
 * Form Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit-button.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since TBD Use function for submit button text.
 *
 * @version TBD
 */

?>
<button
	type="submit"
	name="tickets_process"
	value="1"
	class="tribe-block__rsvp__submit-button"
>
	<?php echo esc_html( sprintf( _x( 'Submit %s', 'blocks rsvp form submit button', 'event-tickets' ), tribe_get_rsvp_label_singular( 'blocks_rsvp_form_submit_button' ) ) ); ?>
</button>