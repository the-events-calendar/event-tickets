<?php
/**
 * Template for RSVP responses information display in admin panel.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var int    $post_id          The post ID of the event.
 * @var int    $rsvp_id          The RSVP ticket ID.
 * @var int    $total_responses  The total number of RSVP responses.
 * @var bool   $cant_go_enabled  Whether "Can't go" responses are enabled.
 * @var string $attendees_url    The URL to the attendees admin page.
 */

?>

<div class="tec-tickets-rsvp-responses-info" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
	<div style="display: flex; align-items: center; gap: 15px;">
		<div style="display: flex; align-items: center; gap: 5px;">
			<strong><?php echo esc_html_x( 'Responses:', 'Label for RSVP response count in admin panel.', 'event-tickets' ); ?></strong>
			<?php if ( $cant_go_enabled ) : ?>
				<span class="dashicons dashicons-info" 
					  title="<?php echo esc_attr_x( 'Responses count includes "not going"', 'Tooltip explaining RSVP count includes negative responses.', 'event-tickets' ); ?>" 
					  style="font-size: 16px; color: #666; cursor: help;">
				</span>
			<?php endif; ?>
		</div>
		<span class="tec-tickets-rsvp-total-count" style="font-weight: bold; color: #0073aa;">
			<?php echo esc_html( $total_responses ); ?>
		</span>
		<a href="<?php echo esc_url( $attendees_url ); ?>" class="button button-secondary" style="margin-left: auto;">
			<?php echo esc_html_x( 'View Attendees', 'Link text to view attendees admin page from RSVP panel.', 'event-tickets' ); ?>
		</a>
	</div>
</div>