<?php
/**
 * This template renders a the fields for a ticket
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-ticket">
	<h4><?php esc_html_e( 'Attendee', 'tribe_tickets' ); ?> <?php echo esc_html( $key + 1 ); ?></h4>
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

			$this->template( 'attendees/fields/' . $field->type, $args );
		?>
	<?php endforeach; ?>
</div>
