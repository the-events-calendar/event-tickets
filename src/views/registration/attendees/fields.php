<?php
/**
 * This template renders a the fields for a ticket
 *
 * @version TBD
 *
 */
$storage = new Tribe__Tickets_Plus__Meta__Storage();
$saved_meta = $storage->get_meta_data_for( $ticket->ID );
?>
<?php foreach ( $fields as $field ) : ?>
	<?php $value = ! empty( $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] ) ? $saved_meta[ $ticket->ID ][ $key ][ $field->slug ] : null; ?>
	<?php $this->template( 'fields/' . $field->type, array( 'ticket' => $ticket, 'field' => $field, 'value' => $value, 'key' => $key ) ); ?>
<?php endforeach; ?>
