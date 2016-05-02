<?php
/**
 * @var array $event_ids
 */
?>

<ul class="tribe-tickets my-attendance-list">
	<?php foreach ( $event_ids as $id ): ?>

		<li class="event-<?php echo esc_attr( $id ) ?>">
			<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank"><?php echo get_the_title( $id ); ?>
			<span class="datetime">(<?php echo tribe_get_start_date( $id ); ?>)</span></a>
		</li>

	<?php endforeach; ?>

	<?php if ( empty( $event_ids ) ): ?>

		<li class="event-none">
			<?php _e( 'You have not indicated your attendance for any upcoming events.', 'event-tickets' ); ?>
		</li>

	<?php endif; ?>
</ul>