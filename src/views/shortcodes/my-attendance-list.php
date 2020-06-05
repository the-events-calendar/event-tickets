<?php
/**
 * Renders the My Attendance list
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/shortcodes/my-attendance-list.php
 *
 * @since   4.8.2
 * @since   TBD Removed target="_blank" from links, added direct link to each post's "My Tickets" view.
 *
 * @version TBD
 *
 * @var array $event_ids
 */

$view = Tribe__Tickets__Tickets_View::instance();
?>

<ul class="tribe-tickets my-attendance-list">
	<?php
	foreach ( $event_ids as $id ):
		$is_event = function_exists( 'tribe_is_event' ) ? tribe_is_event( $id ) : false;
		$direct_link_my_tickets = $view->get_tickets_page_url( $id, $is_event );
		?>
		<?php $start_date = tribe_get_start_date( $id ); ?>
		<li class="event-<?php echo esc_attr( $id ) ?>">
			<a class="event-post-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
				<?php echo get_the_title( $id ); ?>
				<?php if ( $start_date ): ?>
					<span class="datetime">(<?php echo $start_date; ?>)</span>
				<?php endif; ?>
			</a>
			<?php
			if ( ! empty( $direct_link_my_tickets ) ) :
				?>
				<span class="event-post-tickets-separator">&mdash;</span>
				<a class="event-post-direct-tickets-link" href="<?php echo esc_url( $direct_link_my_tickets ); ?>">
					<?php esc_html_e( 'View Tickets', 'event-tickets' ); ?>
				</a>
			<?php
			endif;
			?>
		</li>

	<?php endforeach; ?>

	<?php if ( empty( $event_ids ) ): ?>

		<li class="event-none">
			<?php esc_html_e( 'You have not indicated your attendance for any upcoming events.', 'event-tickets' ); ?>
		</li>

	<?php endif; ?>
</ul>
