<?php
/**
 * The template for the ticket types column in the events list table.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var array $tickets_by_types Array of tickets by types.
 * @var Tribe__Tickets__Admin__Views $admin_views The admin views instance for flexible tickets.
 */
?>
<div class="tec-tickets__series_attached_ticket-types">
	<?php
	foreach ( $tickets_by_types as $type => $tickets ) :
		$admin_views->template( 'ticket-types-column/' . $type, [ 'type'=> $type, 'tickets' => $tickets ] );
	endforeach;
	?>
</div>