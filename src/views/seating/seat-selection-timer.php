<?php
/**
 * Seating seat selection timer template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/seat-selection-timer.php
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string $token        The token used to keep track of the seat selection timer.
 * @var string $redirect_url The URL to redirect the user to when the timer expires.
 * @var int    $post_id      The post ID of the post to purchase tickets for.
 */
?>

<div class="tec-tickets-seating__timer"
	 data-token="<?php echo esc_attr( $token ); ?>"
	 data-redirect-url="<?php echo esc_attr( $redirect_url ); ?>"
	 data-post-id="<?php echo esc_attr( $post_id ); ?>"
>
	<div class="dashicons dashicons-clock"></div>
	<div class="tec-tickets-seating__message">
			<span>
				<span class="tec-tickets-seating__message-text">
					<?php echo esc_html_x('Seat selections reserved for ', 'Seat selection timer text', 'event-tickets'); ?>
				</span>
				<span class="tec-tickets-seating__message-time">
					<!-- This will be set by the js component. -->
				</span>
			</span>
	</div>
</div>

