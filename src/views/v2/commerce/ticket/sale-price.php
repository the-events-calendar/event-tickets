<?php
/**
 * Tickets Commerce: Ticket Sale Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/ticket/sale-price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.9.0
 *
 * @var Value $price The Value instance of the ticket price.
 * @var Value $regular_price The Value instance of the ticket regular price.
 * @var bool  $on_sale Whether the ticket is on sale.
 */

use TEC\Tickets\Commerce\Utils\Value;

if ( empty( $on_sale ) ) {
	return;
}

?>
<ins>
	<span class="tec-tickets-price__sale-price amount">
		<bdi>
			<?php echo esc_html( $price->get_currency_display() ); ?>
		</bdi>
	</span>
</ins>
<del aria-hidden="true">
	<span class="tec-tickets-price__regular-price amount">
		<bdi>
			<?php echo esc_html( $regular_price->get_currency() ); ?>
		</bdi>
	</span>
</del>