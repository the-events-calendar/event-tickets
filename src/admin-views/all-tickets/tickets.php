<?php
/**
 * All Tickets screen.
 *
 * @since  TBD
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var WP_List_Table     $tickets_table  The list table for the All Tickets screen.
 */

$tickets_table->prepare_items();
?>
<h1>
	<?php esc_html_e( 'All Tickets', 'event-tickets' ); ?>
</h1>
<div>
	<?php $tickets_table->display(); ?>
</div>
