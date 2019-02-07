<?php
/**
 * This template renders a the fields for a ticket
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/fields.php
 *
 * @since 4.9
 * @since TBD Update template paths to add the "registration/" prefix
 * @version TBD
 *
 */
?>
<div class="tribe-ticket">
	<h4><?php esc_html_e( 'Attendee', 'tribe-tickets' ); ?> <?php echo esc_html( $key + 1 ); ?></h4>
	<?php foreach ( $fields as $field ) : ?>
		<?php
			$value = ! empty( $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] ) ? $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] : null;

			$args = array(
				'event_id'   => $event_id,
				'ticket'     => $ticket,
				'field'      => $field,
				'value'      => $value,
				'key'        => $key,
				'saved_meta' => $saved_meta,
			);

			$this->template( 'registration/attendees/fields/' . $field->type, $args );
		?>
	<?php endforeach; ?>
</div>
