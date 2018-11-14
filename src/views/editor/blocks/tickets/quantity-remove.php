<?php
/**
 * This template renders a Single Ticket Quantity Minus Button
 *
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );
?>
<button
	class="tribe-block__tickets__item__quantity__remove"
>
	<?php esc_html_e( '-', 'events-gutenberg' ); ?>
</button>