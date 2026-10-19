<?php
/**
 * A recurring event's dates and venue, atop its Attendees and Orders pages.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/event-details.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string                                               $dates_label The label of the dates: upcoming ones, or all.
 * @var string[]                                             $shown       The dates shown.
 * @var string[]                                             $folded      The dates folded away until asked for.
 * @var array{name: string, edit_link: string|null}|null     $venue       The event's venue, if any.
 */

?>
<li class="tec-tickets__recurring-event-dates">
	<strong><?php echo esc_html( $dates_label ); ?></strong>
	<ul>
		<?php foreach ( $shown as $date ) : ?>
			<li><?php echo esc_html( $date ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $folded ) : ?>
		<details>
			<summary>
				<?php
				// translators: %d is how many more dates the event has.
				echo esc_html( sprintf( _n( '+ %d more', '+ %d more', count( $folded ), 'event-tickets' ), count( $folded ) ) );
				?>
			</summary>
			<ul>
				<?php foreach ( $folded as $date ) : ?>
					<li><?php echo esc_html( $date ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
</li>
<?php if ( $venue ) : ?>
	<li class="venue-name">
		<strong><?php echo esc_html( tribe_get_venue_label_singular() ); ?>:</strong>
		<a href="<?php echo esc_url( $venue['edit_link'] ?? '' ); ?>"><?php echo esc_html( $venue['name'] ); ?></a>
	</li>
<?php endif; ?>
