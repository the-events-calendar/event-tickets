<?php
/**
 * @var array $available
 */
?>

<div>
	<?php echo esc_html__( 'Availability for this ticket type is counted using', 'event-tickets' ) . ', ' . esc_html( array_search( min( $available ), $available ) . ' - ' . min( $available ) ); ?>
</div>
<p>
	<a href="m.tri.be/1aek"><?php esc_html_e( 'Learn more about how Availability is calculated.', 'event-tickets' ); ?></a>
</p>
<ul class="tooltip-list">
	<li><?php esc_html_e( 'Inventory is the capacity minus generated attendees for that ticket.', 'event-tickets' ); ?></li>
	<li><?php esc_html_e( 'Stock is the lowest number of ticket stock or if active the shared stock.', 'event-tickets' ); ?></li>
	<li><?php esc_html_e( 'Capacity is based on the chosen shared, shared capped, or individual capacity for this ticket.', 'event-tickets' ); ?></li>
</ul>
