<?php
/**
 * This template renders a Single Ticket Title
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__content__title"
>
	<?php echo $ticket->name; ?>
</div>