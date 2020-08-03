<?php
/**
 * This template renders the RSVP ARI sidebar guest list item JS template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/sidebar/guest-list/item-template.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<script
	class="tribe-tickets__rsvp-ar-guest-list-item-template"
	id="tmpl-tribe-tickets__rsvp-ar-guest-list-item-template-<?php echo esc_attr( $rsvp->ID ); ?>"
	type="text/template"
>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button
			class="tribe-tickets__rsvp-ar-guest-list-item-button tribe-tickets__rsvp-ar-guest-list-item-button--inactive"
			type="button"
			data-guest-number="{{data.attendee_id + 1}}"
			role="tab"
			aria-selected="false"
			aria-controls="tribe-tickets-rsvp-<?php echo esc_attr( $rsvp->ID ); ?>-guest-{{data.attendee_id + 1}}-tab"
			id="tribe-tickets-rsvp-<?php echo esc_attr( $rsvp->ID ); ?>-guest-{{data.attendee_id + 1}}"
			tabindex="-1"
		>
			<?php $this->template( 'v2/components/icons/guest', [ 'classes' => [ 'tribe-tickets__rsvp-ar-guest-icon' ] ] ); ?>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php
					echo esc_html( tribe_get_guest_label_singular( 'RSVP attendee registration sidebar guest button' ) );
				?>
			</span>
		</button>
	</li>
</script>
