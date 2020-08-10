<?php
/**
 * Block: RSVP
 * Actions - Success - Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/success/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.12.3
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-actions-success-going">
	<em class="tribe-tickets__rsvp-actions-success-going-check-icon"></em>
	<span class="tribe-tickets__rsvp-actions-success-going-text tribe-common-h4 tribe-common-h6--min-medium">
		<?php if ( ! empty( $is_going ) ) : ?>
			<?php esc_html_e( 'You are going', 'event-tickets' ); ?>
		<?php else : ?>
			<?php esc_html_e( "Can't go", 'event-tickets' ); ?>
		<?php endif; ?>
	</span>
</div>
