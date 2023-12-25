<?php
/**
 * My Tickets: Title
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/tickets/my-tickets/title.php
 *
 * @since 5.6.7
 *
 * @since TBD Added the ticket type parameter.
 *
 * @version TBD
 *
 * @var string  $title       The title.
 * @var string  $ticket_type The ticket type.
 *
 */
?>
<div class="tec-tickets__my-tickets-list-title-container type-<?php echo esc_attr( $ticket_type ); ?>">
	<div class="tec-tickets__my-tickets-list-title">
		<?php echo esc_html( $title ); ?>
	</div>
</div>