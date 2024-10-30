<?php
/**
 * @var Tribe__Tickets__Ticket_Object $ticket                The ticket object.
 * @var bool                          $show_duplicate_button Whether to show the duplicate button.
 */

$edit_title      = sprintf(
	_x( 'Edit %s ID: %d', 'ticket ID title attribute', 'event-tickets' ),
	tribe_get_ticket_label_singular( 'ticket_id_title_attribute' ),
	$ticket->ID
);
$duplicate_title = sprintf(
// Translators: %s: dynamic "ticket" text, %d: ticket ID #.
	_x( 'Duplicate %s ID: %d', 'ticket ID title attribute', 'event-tickets' ),
	tribe_get_ticket_label_singular( 'ticket_id_title_attribute' ),
	$ticket->ID
);
$delete_title    = sprintf(
	_x( 'Delete %s ID: %d', 'ticket ID title attribute', 'event-tickets' ),
	tribe_get_ticket_label_singular( 'ticket_id_title_attribute' ),
	$ticket->ID
);
?>
<button
	data-provider='<?php echo esc_attr( $ticket->provider_class ); ?>'
	data-ticket-id='<?php echo esc_attr( $ticket->ID ); ?>'
	title='<?php echo esc_attr( $edit_title ); ?>'
	class='ticket_edit_button'>
	<span class='ticket_edit_text'><?php echo esc_html( $ticket->name ); ?></span>
</button>

<?php if ( $show_duplicate_button ) : ?>
	<button
		data-provider='<?php echo esc_attr( $ticket->provider_class ); ?>'
		data-ticket-id='<?php echo esc_attr( $ticket->ID ); ?>'
		title='<?php echo esc_attr( $duplicate_title ); ?>'
		class='ticket_duplicate'>
	<span class='ticket_duplicate_text'><?php echo esc_html( $ticket->name ); ?></span>
	</button>
<?php endif; ?>

<button
	attr-provider='<?php echo esc_attr( $ticket->provider_class ); ?>'
	attr-ticket-id='<?php echo esc_attr( $ticket->ID ); ?>'
	title='<?php echo esc_attr( $delete_title ); ?>'
	class='ticket_delete'>
	<span class='ticket_delete_text'><?php echo esc_html( $ticket->name ); ?></span>
</button>
