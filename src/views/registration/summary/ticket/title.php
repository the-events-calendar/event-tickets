<?php
/**
 * This template renders the summary ticket title
 *
 * @version TBD
 *
 */
$ticket_data = Tribe__Tickets__Tickets::load_ticket_object( $ticket['id'] );
?>
<div class="tribe-block__tickets__registration__tickets__item__title">
	<?php echo $ticket_data->name; ?>
</div>