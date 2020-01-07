<?php
/**
 * View: Week View - Event Tooltip Cost
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/event-tickets/views/v2/week/grid-body/events-day/event/tooltip/cost.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @since   4.10.9
 * @version TBD
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 *
 */
if ( empty( $event->cost ) ) {
	return;
}
?>
<div class="tribe-events-c-small-cta tribe-common-b3 tribe-events-pro-week-grid__event-tooltip-cost">
	<?php if ( $event->tickets->exist() && tribe_tickets_is_current_time_in_date_window( $event->ID )  ) : ?>
		<a
			href="<?php echo esc_url( $event->tickets->link->anchor ); ?>"
			class="tribe-events-c-small-cta__link tribe-common-cta tribe-common-cta--thin-alt"
		>
			<?php echo esc_html( $event->tickets->link->label ); ?>
		</a>
	<?php endif; ?>
	<span class="tribe-events-c-small-cta__price">
		<?php echo esc_html( $event->cost ) ?>
	</span>
</div>

