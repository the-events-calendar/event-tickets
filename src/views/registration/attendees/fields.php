<?php
/**
 * This template renders a the fields for a ticket
 *
 * @version TBD
 *
 */
?>
<?php foreach ( $fields as $field ) : ?>
	<?php $this->template( 'fields/' . $field->type, array( 'ticket' => $ticket, 'field' => $field ) ); ?>
<?php endforeach; ?>
