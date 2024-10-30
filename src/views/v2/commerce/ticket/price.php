<?php
/**
 * Tickets Commerce: Ticket Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/ticket/price.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.2.3
 * @since 5.9.0 Add support to display the regular price when the ticket is on sale.
 *
 * @version 5.9.0
 *
 * @var Value $price The Value instance of the ticket price.
 * @var Value $regular_price The Value instance of the ticket regular price.
 * @var bool  $on_sale Whether the ticket is on sale.
 */

use TEC\Tickets\Commerce\Utils\Value;

if ( empty( $on_sale ) ) {
	$on_sale = ! empty( $item ) && ! empty( $item['obj']->on_sale );
}

if ( ! isset( $regular_price ) && isset( $item['obj']->regular_price ) ) {
	$regular_price = Value::create( $item['obj']->regular_price );
}

if ( isset( $price ) && ! $price instanceof Value ) {
	return;
}

if ( isset( $regular_price ) && ! $regular_price instanceof Value ) {
	return;
}
?>

<span class="tec-tickets-price amount">
	<?php
		$this->template(
			'ticket/regular-price',
			[
				'price'   => $price,
				'on_sale' => $on_sale,
			]
		);

		if ( $on_sale ) {
			$this->template(
				'ticket/sale-price',
				[
					'price'         => $price,
					'regular_price' => $regular_price,
					'on_sale'       => $on_sale,
				]
			);
		}
		?>
</span>
