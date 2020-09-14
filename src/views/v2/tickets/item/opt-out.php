<?php
/**
 * Block: Tickets
 * Opt out
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/opt-out.php
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

 // Bail if it's in "mini cart" or "modal" context.
if (
	! empty( $is_modal )
	|| ! empty( $is_mini )
) {
	return;
}

/**
 * Use this filter to hide the Attendees List Opt-out
 *
 * @since 4.9
 *
 * @param bool $hide_attendee_list_optout Whether to hide attendees list opt-out.
 * @param int  $post_id                   The post ID this ticket belongs to.
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false, $post_id );

if ( $hide_attendee_list_optout ) {
	// Force opt-out.
	?>
	<input
		name="attendee[optout]"
		value="1"
		type="hidden"
	/>
	<?php
	return;
}

/* var Tribe__Tickets__Privacy $privacy  */
$privacy = tribe( 'tickets.privacy' );

$field_id = [
	'tribe-tickets-attendees-list-optout',
	$ticket->ID,
];

$field_id = implode( '-', $field_id );
?>
<div class="tribe-common-form-control-checkbox tribe-tickets-attendees-list-optout--wrapper">
	<label
		class="tribe-common-form-control-checkbox__label"
		for="<?php echo esc_attr( $field_id ); ?>"
	>
		<input
			class="tribe-common-form-control-checkbox__input tribe-tickets__item__optout"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="attendee[optout]"
			type="checkbox"
			<?php checked( true ); ?>
		/>
		<?php echo $privacy->get_opt_out_text(); ?>
	</label>
</div>
