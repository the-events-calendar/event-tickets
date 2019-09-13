<?php
/**
 * This template renders a the fields for a ticket
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/fields.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */
$meta        = Tribe__Tickets_Plus__Main::instance()->meta();
$required    = $meta->ticket_has_required_meta( $ticket->ID );
?>
<div
	class="tribe-common-h7 tribe-common-h6--min-medium tribe-common-h--alt tribe-ticket <?php echo $required ? 'tribe-ticket--has-required-meta' : ''; ?>"
	data-ticket-id="<?php echo esc_attr($ticket->ID); ?>">
	<h4 class="tribe-common-b1"><?php esc_html_e( 'Attendee', 'event-tickets' ); ?> {{data.attendee_id}}</h4>
	<?php foreach ( $fields as $field ) : ?>
		<?php
			$value = null;

			$args = array(
				'event_id'   => $event_id,
				'ticket'     => $ticket,
				'field'      => $field,
				'value'      => $value,
				'saved_meta' => $saved_meta,
			);

			$this->template( 'registration-js/attendees/fields/' . $field->type, $args );
		?>
	<?php endforeach; ?>
</div>
