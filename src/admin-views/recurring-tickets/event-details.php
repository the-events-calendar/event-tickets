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
	<ul id="tec-tickets-recurring-event-dates-list">
		<?php foreach ( $shown as $date ) : ?>
			<li><?php echo esc_html( $date ); ?></li>
		<?php endforeach; ?>
		<?php foreach ( $folded as $date ) : ?>
			<li class="tec-tickets__recurring-event-dates-folded" hidden><?php echo esc_html( $date ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $folded ) : ?>
		<?php
		// translators: %d is how many more dates the event has.
		$show_more = sprintf( _n( '+ %d more', '+ %d more', count( $folded ), 'event-tickets' ), count( $folded ) );
		$show_less = __( 'Show fewer', 'event-tickets' );
		?>
		<button
			type="button"
			class="button-link"
			aria-controls="tec-tickets-recurring-event-dates-list"
			aria-expanded="false"
			data-more="<?php echo esc_attr( $show_more ); ?>"
			data-less="<?php echo esc_attr( $show_less ); ?>"
			onclick="var open = 'true' !== this.getAttribute( 'aria-expanded' ); document.querySelectorAll( '#tec-tickets-recurring-event-dates-list .tec-tickets__recurring-event-dates-folded' ).forEach( function ( date ) { date.hidden = ! open; } ); this.setAttribute( 'aria-expanded', String( open ) ); this.textContent = open ? this.dataset.less : this.dataset.more;"
		><?php echo esc_html( $show_more ); ?></button>
	<?php endif; ?>
</li>
<?php if ( $venue ) : ?>
	<li class="venue-name">
		<strong><?php echo esc_html( tribe_get_venue_label_singular() ); ?>:</strong>
		<a href="<?php echo esc_url( $venue['edit_link'] ?? '' ); ?>"><?php echo esc_html( $venue['name'] ); ?></a>
	</li>
<?php endif; ?>
