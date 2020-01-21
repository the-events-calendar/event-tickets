<?php
/**
 * Block: Tickets
 * Extra column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-price.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   TBD Updated code comments and array formatting.
 *
 * @version TBD
 */

/** @var Tribe__Tickets__Ticket_Object $ticket */
$ticket = $this->get( 'ticket' );

/** @var Tribe__Tickets__Tickets $provider */
$provider = $this->get( 'provider' );

/** @var Tribe__Tickets__Commerce__Currency $tribe_commerce_currency */
$tribe_commerce_currency = tribe( 'tickets.commerce.currency' );
?>
<div
	class="tribe-common-b2 tribe-common-b1--min-medium tribe-tickets__item__extra__price"
>
	<?php echo $tribe_commerce_currency->get_formatted_currency_with_symbol( $ticket->price, $post_id, $provider->class_name ) ?>
</div>