<?php
/**
 * The template for the ticket types column in the events list table.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var boolean $has_rsvp Whether the event has RSVP enabled.
 * @var boolean $has_ticket Whether the event has Tickets enabled.
 * @var boolean $has_series_pass Whether the event has Series Pass enabled.
 * @var Tribe__Tickets__Admin__Views $admin_views The admin views instance for flexible tickets.
 */
?>
<div class="tec-tickets__series_attached_ticket_types">
	<?php
		$admin_views->template( 'ticket-types-column/rsvp', [ 'has_rsvp' => $has_rsvp ] );
		$admin_views->template( 'ticket-types-column/ticket', [ 'has_ticket' => $has_ticket ] );
		$admin_views->template( 'ticket-types-column/series-pass', [ 'has_series_pass' => $has_series_pass ] );
	?>
</div>