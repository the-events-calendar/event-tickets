<?php
/**
 * Template for RSVP responses information display in admin panel.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var int    $post_id         The post ID of the event.
 * @var int    $rsvp_id         The RSVP ticket ID.
 * @var int    $total_responses The total number of RSVP responses.
 * @var bool   $cant_go_enabled Whether "Can't go" responses are enabled.
 * @var string $attendees_url   The URL to the attendees admin page.
 */

defined( 'ABSPATH' ) || die();
?>

<div class="tec-tickets-rsvp-responses-info__wrap">
	<div class="tec-tickets-rsvp-responses-info__label">
		<strong><?php echo esc_html_x( 'Responses:', 'Label for RSVP response count in admin panel.', 'event-tickets' ); ?></strong>
		<?php if ( $cant_go_enabled ) : ?>
			<span class="dashicons dashicons-info"
				title="<?php echo esc_attr_x( 'Responses count includes "not going"', 'Tooltip explaining RSVP count includes negative responses.', 'event-tickets' ); ?>"
			>
				</span>
		<?php endif; ?>
	</div>
	<div class="tec-tickets-rsvp-responses-info__count-link">
		<span class="tec-tickets-rsvp-total-count">
			<?php echo esc_html( $total_responses ); ?>
		</span>
		<a href="<?php echo esc_url( $attendees_url ); ?>" class="tec-tickets-rsvp-responses-info__link" target="_blank">
			<?php echo esc_html_x( 'View Attendees', 'Link text to view attendees admin page from RSVP panel.', 'event-tickets' ); ?>
		</a>
	</div>
</div>
