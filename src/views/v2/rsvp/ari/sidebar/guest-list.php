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
			<em class="tribe-common-svgicon tribe-common-svgicon--guest"></em>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php esc_html_e( 'Main Guest', 'event-tickets' ); ?>
			</span>
		</button>
	</li>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button>
			<em class="tribe-common-svgicon tribe-common-svgicon--guest-disabled"></em>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php esc_html_e( 'Guest 2', 'event-tickets' ); ?>
			</span>
		</button>
	</li>
	<li class="tribe-tickets__rsvp-ar-guest-list-item">
		<button>
			<em class="tribe-common-svgicon tribe-common-svgicon--guest-disabled"></em>
			<span class="tribe-tickets__rsvp-ar-guest-list-item-title tribe-common-a11y-visual-hide">
				<?php esc_html_e( 'Guest 3', 'event-tickets' ); ?>
				</span>
		</button>
	</li>
</ul>
