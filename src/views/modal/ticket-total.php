<?php
/**
 * Modal: Ticket Total
 * Total column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/ticketptotal.php
 *
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__modal_total"
>
	<span class="tribe-block__tickets__item__modal_total_currency"><?php echo tribe_get_option( 'defaultCurrencySymbol', '$' ); ?></span>
	<span class="tribe-block__tickets__item__modal_total_amount">0.00</span>
</div>
