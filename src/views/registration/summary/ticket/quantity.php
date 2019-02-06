<?php
/**
 * This template renders the summary ticket quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/ticket/quantity.php
 *
 * @since 4.9
 * @since TBD Update template paths to add the "registration/" prefix
 * @version TBD
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item__quantity">
	<?php echo esc_html( $ticket['qty'] ); ?>
</div>
