<?php
/**
 * New RSVP form toggle button.
 *
 * @since 5.8.0
 *
 * @var int  $post_id  The ID of the post the form is being rendered for.
 * @var bool $disabled Whether the RSVP form toggle should be disabled.
 */

$disabled = ! empty( $disabled );
?>
<button
	id="rsvp_form_toggle"
	class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
	aria-label="<?php echo esc_attr(sprintf(_x('Add a new %s', 'RSVP form toggle button label', 'event-tickets'), tribe_get_rsvp_label_singular('rsvp_form_toggle_button_label'))); ?>"
	<?php if ( $disabled ) : ?>
		disabled
		title="<?php echo esc_attr_x( 'RSVP is temporarily disabled while migration is in progress.', 'Tooltip for disabled RSVP button during migration.', 'event-tickets' ); ?>"
	<?php endif; ?>
>
	<?php
	echo esc_html(
		sprintf(
			_x('New %s', 'RSVP form toggle button text', 'event-tickets'),
			tribe_get_rsvp_label_singular('rsvp_form_toggle_button_text')
		)
	); ?>
</button>
