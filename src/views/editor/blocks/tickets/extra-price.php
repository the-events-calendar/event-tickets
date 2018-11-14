<?php
/**
 * This template renders a Single Ticket Price
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__extra__price"
>
	<?php echo $ticket->get_provider()->get_price_html( $ticket->ID ); ?>
</div>