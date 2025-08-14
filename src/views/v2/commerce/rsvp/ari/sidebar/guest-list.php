<?php
/**
 * This template renders the RSVP ARI sidebar guest list.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/ari/sidebar/guest-list.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<ul
	class="tribe-tickets__rsvp-ar-guest-list tribe-common-h6"
	role="tablist"
	aria-label="<?php echo esc_attr( tribe_get_guest_label_plural( 'RSVP attendee registration sidebar aria label' ) ); ?>"
>

	<?php $this->template( 'v2/commerce/rsvp/ari/sidebar/guest-list/guest' ); ?>
	<?php $this->template( 'v2/commerce/rsvp/ari/sidebar/guest-list/guest-template' ); ?>

</ul>
