<?php
/**
 * @var WP_Posts_List_Table $events_table Events list table.
 * @var string              $header Header string.
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $header ); ?></h1>
	<form method="post">
		<?php
		$events_table->search_box( 'search', 'search_id' );
		$events_table->display();
		?>
	</form>
</div>
