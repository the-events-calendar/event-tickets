<?php
/**
 * This template renders the summary ticket price
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item__price">
	<?php echo $ticket['provider']->get_price_html( $ticket['id'] ); ?>
</div>