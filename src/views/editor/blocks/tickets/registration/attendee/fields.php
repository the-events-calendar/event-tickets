<?php
/**
 * This template renders a the fields for a ticket
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );

$meta   = Tribe__Tickets_Plus__Main::instance()->meta();
$fields = $meta->get_meta_fields_by_ticket( $ticket->ID );

?>
<?php foreach ( $fields as $field ) : ?>
	<?php $this->template( 'editor/blocks/tickets/registration/attendee/fields/' . $field->type  , array( 'ticket' => $ticket, 'field' => $field ) ); ?>
<?php endforeach; ?>
