<?php
/**
 * This template renders the RSVP ARI sidebar guest list.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/sidebar/guest-list.php
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<ul class="tribe-tickets__rsvp-ar-guest-list tribe-common-h6">
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button>
			<?php $this->template( 'v2/components/icons/guest', [ 'classes' => [ 'tribe-tickets__rsvp-ar-guest-icon' ] ] ); ?>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php
				echo wp_kses_post(
					sprintf(
						/* Translators: %s Guest label for RSVP attendee registration sidebar. */
						__( 'Main %s', 'event-tickets' ),
						tribe_get_guest_label_singular( 'RSVP attendee registration sidebar guest button' )
					)
				);
				?>
			</span>
		</button>
	</li>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button>
			<?php $this->template( 'v2/components/icons/guest', [ 'classes' => [ 'tribe-tickets__rsvp-ar-guest-icon--disabled' ] ] ); ?>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php
				echo wp_kses_post(
					sprintf(
						/* Translators: %s Guest label for RSVP attendee registration sidebar. */
						__( '%s 2', 'event-tickets' ),
						tribe_get_guest_label_singular( 'RSVP attendee registration sidebar guest button' )
					)
				);
				?>
			</span>
		</button>
	</li>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button>
			<?php $this->template( 'v2/components/icons/guest', [ 'classes' => [ 'tribe-tickets__rsvp-ar-guest-icon--disabled' ] ] ); ?>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php
				echo wp_kses_post(
					sprintf(
						/* Translators: %s Guest label for RSVP attendee registration sidebar. */
						__( '%s 3', 'event-tickets' ),
						tribe_get_guest_label_singular( 'RSVP attendee registration sidebar guest button' )
					)
				);
				?>
			</span>
		</button>
	</li>
</ul>
