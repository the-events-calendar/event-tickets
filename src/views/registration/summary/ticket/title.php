<?php
/**
 * This template renders the summary ticket title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/ticket/title.php
 *
 * @since 4.9
 * @since TBD Update template paths to add the "registration/" prefix
 * @version TBD
 *
 */
$ticket_data = Tribe__Tickets__Tickets::load_ticket_object( $ticket['id'] );
?>
<div class="tribe-block__tickets__registration__tickets__item__title">
	<?php echo $ticket_data->name; ?>
</div>
