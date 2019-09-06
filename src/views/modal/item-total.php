<?php
/**
 * Modal: Item Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/item-total.php
 *
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

$ticket = $this->get( 'ticket' );

/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency        = tribe( 'tickets.commerce.currency' );
$currency_symbol = $currency->get_currency_symbol( $ticket->ID, true );
?>
<div
	class="tribe-block__tickets__item__total__wrap"
>
	<span class="tribe-block__tickets__item__total__currency-symbol"><?php echo $currency_symbol; ?></span>
	<span class="tribe-block__tickets__item__total"><?php echo tribe_format_currency( 0 ); ?></span>
</div>
