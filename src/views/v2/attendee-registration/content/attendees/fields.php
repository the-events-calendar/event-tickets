<?php
/**
 * This template renders a the fields for a ticket
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/attendees/fields.php
 *
 * @since TBD
 *
 * @version TBD
 */

$meta     = Tribe__Tickets_Plus__Main::instance()->meta();
$required = $meta->ticket_has_required_meta( $ticket->ID );
$classes  = [
	'tribe-ticket',
	'tribe-tickets__form',
	'tribe-ticket--has-required-meta' => $required,
];

?>
<div
	<?php tribe_classes( $classes ); ?>
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
>

	<h4 class="tribe-common-b1 tribe-common-b1--bold tribe-tickets__attendee__title">
		<?php /* Translators: 1 the attendee number. */ ?>
		<?php echo sprintf( esc_html_x( 'Attendee %1$s', 'Tickets modal attendee fields', 'event-tickets' ), '{{data.attendee_id}}' ); ?>
	</h4>

	<?php
		/**
		 * Allows injection of meta fields in the tickets registration.
		 *
		 * @since TBD
		 *
		 * @see  Tribe__Template\do_entry_point()
		 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
		 */
		$this->do_entry_point( 'tickets_attendee_fields' );
	?>

</div>
