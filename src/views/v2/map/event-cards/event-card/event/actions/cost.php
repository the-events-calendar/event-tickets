<?php
/**
 * View: Map View - Single Event Actions - Cost
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/event-tickets/views/v2/map/event-cards/event-card/event/actions/cost.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.10.9
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 *
 */
if ( empty( $event->tickets->exist ) ) {
	return;
}
?>

<a
	href="<?php echo esc_url( $event->tickets->link->anchor ); ?>"
	class="tribe-events-c-small-cta__link tribe-common-cta tribe-common-cta--thin-alt"
>
	<?php echo esc_html( $event->tickets->link->label ); ?>
</a>
