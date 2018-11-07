<?php
/**
 * This template renders a the fields for a ticket
 *
 * @version TBD
 *
 */
?>
<h4><?php esc_html_e( 'Attendee', 'tribe_tickets' ); ?> <?php echo esc_html( $key + 1 ); ?></h4>
<?php foreach ( $fields as $field ) : ?>
	<?php $value = ! empty( $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] ) ? $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] : null; ?>
	<?php $this->template( 'attendees/fields/' . $field->type, array( 'ticket' => $ticket, 'field' => $field, 'value' => $value, 'key' => $key ) ); ?>
<?php endforeach;
