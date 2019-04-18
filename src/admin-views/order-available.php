<?php
/**
 * @var array $available
 */
?>

<div>
	<?php echo esc_html__( 'Current availablity is using', 'event-tickets' ) . ', ' . esc_html( array_search( min( $available ), $available ) . ' - ' . min( $available ) ); ?>
</div>
<p>
	<?php echo esc_html__( 'Ticket availability is based on the lowest number of inventory, stock, and capacity.', 'event-tickets' ); ?>
</p>
<ul class="tooltip-list">
	<li><?php echo esc_html__( 'Inventory is the capacity minus generated attendees for that ticket.', 'event-tickets' ); ?></li>
	<li><?php echo esc_html__( 'Stock is the lowest number of ticket stock or if active the shared stock.', 'event-tickets' ); ?></li>
	<li><?php echo esc_html__( 'Capacity is based on the chosen shared, shared capped, or individual capacity for this ticket.', 'event-tickets' ); ?></li>
</ul>