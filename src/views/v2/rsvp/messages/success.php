<?php
/**
 * Block: RSVP
 * Messages Sucess
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/messages/success.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

$step = sanitize_text_field( tribe_get_request_var( 'step', '' ) );

if ( 'success' !== $step ) {
	return;
}
?>
<div class="tribe-tickets__rsvp-message tribe-tickets__rsvp-message--success tribe-common-b3">
	<em class="tribe-common-svgicon tribe-tickets__rsvp-message--success-icon"></em>

	<span class="tribe-tickets__rsvp-message-text">
		<strong>
			<?php
			echo esc_html(
				sprintf(
					/* Translators: 1: RSVP label. */
					_x( 'Your %1$s has been received! ', 'blocks rsvp messages success', 'event-tickets' ),
					tribe_get_rsvp_label_singular( 'blocks_rsvp_messages_success' )
				)
			);
			?>
		</strong>

		<?php
		echo esc_html(
			sprintf(
				/* Translators: 1: RSVP label. */
				_x( 'Check your email for %1$s confirmation.', 'blocks rsvp messages success', 'event-tickets' ),
				tribe_get_rsvp_label_singular( 'blocks_rsvp_messages_success' )
			)
		);
		?>
	</span>
</div>
