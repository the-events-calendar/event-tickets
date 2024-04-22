<?php
/**
 * My Tickets Page
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets.php
 *
 * @since 5.6.7
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var  array  $orders              The orders for the current user.
 * @var  int    $post_id             The ID of the post the tickets are for.
 * @var  array  $titles              List of ticket type titles.
 */

?>
<div class="tribe-tickets">
	<?php
		$this->template( 'tickets/my-tickets/orders-list' );
	?>
</div>