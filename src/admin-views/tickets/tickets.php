<?php
/**
 * All Tickets screen.
 *
 * @since  TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var WP_List_Table     $tickets_table  The list table for the All Tickets screen.
 * @var string            $page_slug      The slug of the current page.
 */

$tickets_table->prepare_items();
?>
<h1>
	<?php esc_html_e( 'All Tickets', 'event-tickets' ); ?>
</h1>
<form id="tec-tickets-all-tickets-form" method="get">
	<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>" />
	<?php $tickets_table->display(); ?>
</form>
