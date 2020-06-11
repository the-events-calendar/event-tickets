<?php
/**
 * Block: RSVP
 * Form Name
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/form/fields/name.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 */

/**
 * Set the default Full Name for the RSVP form
 *
 * @param string
 * @param Tribe__Events_Gutenberg__Template $this
 *
 * @since 4.9
 */
$name = apply_filters( 'tribe_tickets_rsvp_form_full_name', '', $this );
?>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-name"
	>
		<?php esc_html_e( 'Name', 'event-tickets' ); ?><span class="screen-reader-text"><?php esc_html_e( 'required', 'event-tickets' ); ?></span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="text"
		id="tribe-tickets-rsvp-name"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input"
		name="attendee[full_name]"
		value="<?php echo esc_attr( $name ); ?>"
		required
		aria-required="true"
		placeholder="<?php esc_attr_e( 'John Doe', 'event-tickets' ); ?>"
	>
</div>
