<?php
/**
 * Seating seat selection timer template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/seat-selection-timer.php
 *
 * @since 5.16.0
 * @since 5.20.1 Updated the template wrapper.
 *
 * @version 5.20.1
 *
 * @var string $token        The token used to keep track of the seat selection timer.
 * @var string $redirect_url The URL to redirect the user to when the timer expires.
 * @var int    $post_id      The post ID of the post to purchase tickets for.
 * @var bool   $sync_on_load Whether to sync the timer with the backend on DOM ready or not.
 */

?>
<div class="tribe-common">
	<div class="tec-tickets-seating__timer tec-tickets-seating__timer--hidden"
		data-token="<?php echo esc_attr( $token ); ?>"
		data-redirect-url="<?php echo esc_url( $redirect_url ); ?>"
		data-post-id="<?php echo esc_attr( $post_id ); ?>"
		<?php echo $sync_on_load ? 'data-sync-on-load' : ''; ?>
	>
		<div class="dashicons dashicons-clock"></div>
		<div class="tec-tickets-seating__message">
			<span>
				<span class="tec-tickets-seating__message-text">
					<?php echo esc_html_x( 'Seats reserved for ', 'Seat selection timer text', 'event-tickets' ); ?>
				</span>
				<span class="tec-tickets-seating__message-time">
					<span class="tec-tickets-seating__time-minutes">
						<!-- This will be set by JS. -->
					</span>:<span class="tec-tickets-seating__time-seconds">
						<!-- This will be set by JS. -->
					</span>
				</span>
			</span>
		</div>
		<div class="tec-tickets-seating__dialog-append-target"></div>
	</div>
</div>
<?php
