<?php
/**
 * Tickets Commerce: Regular Ticket Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/ticket/regular-price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.9.0
 *
 * @version 5.9.0
 *
 * @var Value $price The Value instance of the ticket price.
 * @var bool  $on_sale Whether the ticket is on sale.
 */

use TEC\Tickets\Commerce\Utils\Value;

if ( $on_sale ) {
	return;
}

$regular_price_label = $price->get_currency();

// If the price is zero, we should display it as free.
if ( $price->get_decimal() == 0 ) {
	$regular_price_label = _x( 'Free', 'No cost', 'event-tickets' );
}

echo esc_html( $regular_price_label );
