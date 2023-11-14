<?php
/**
 * My Tickets Page
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/tickets/my-tickets.php
 *
 * @since 5.6.7
 *
 * @version 5.6.7
 *
 * @var  array  $orders              The orders for the current user.
 * @var  int    $post_id             The ID of the post the tickets are for.
 * @var  string $title               The title of the ticket section.
 *
 */

?>
<div class="tribe-tickets">
	<?php
		$this->template( 'tickets/my-tickets/orders-list' );
	?>
</div>