<?php
/**
 * This template renders a Single Ticket Quantity Minus Button
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );
?>
<button
	class="tribe-block__tickets__item__quantity__remove"
>
	<?php esc_html_e( '-', 'events-gutenberg' ); ?>
</button>