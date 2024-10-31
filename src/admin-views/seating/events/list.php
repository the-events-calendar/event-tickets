<?php
/**
 * Associated events list view by layout.
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 *
 * @var WP_Posts_List_Table $events_table Events list table.
 * @var string              $header Header string.
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $header ); ?></h1>
	<form id="event-tickets__seating-events-form" method="post">
		<?php
		$events_table->views();
		$events_table->search_box( 'search', 'search_id' );
		$events_table->display();
		?>
	</form>
</div>
