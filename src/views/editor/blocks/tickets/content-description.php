<?php
/**
 * This template renders a Single Ticket description
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );

if ( ! $ticket->show_description() ) {
	return false;
}
?>
<div
	class="tribe-block__tickets__item__content__description"
>
	<?php echo $ticket->description; ?>
</div>