<?php
/**
 * This template renders a Single Ticket Plus Button
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );
?>
<button
	class="tribe-block__tickets__item__quantity__add"
>
	<?php esc_html_e( '+', 'events-gutenberg' ); ?>
</button>