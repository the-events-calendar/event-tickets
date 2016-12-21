<?php
/**
 * Renders the My Attendance list
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets/shortcodes/my-attendance-list.php
 *
 * @version 4.3.5
 *
 * @var array $event_ids
 */
?>

<ul class="tribe-tickets my-attendance-list">
	<?php foreach ( $event_ids as $id ): ?>
		<?php $start_date = tribe_get_start_date( $id ); ?>
		<li class="event-<?php echo esc_attr( $id ) ?>">
			<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank">
				<?php echo get_the_title( $id ); ?>
				<?php if ( $start_date ): ?>
					<span class="datetime">(<?php echo $start_date; ?>)</span>
				<?php endif; ?>
			</a>
		</li>

	<?php endforeach; ?>

	<?php if ( empty( $event_ids ) ): ?>

		<li class="event-none">
			<?php esc_html_e( 'You have not indicated your attendance for any upcoming events.', 'event-tickets' ); ?>
		</li>

	<?php endif; ?>
</ul>
