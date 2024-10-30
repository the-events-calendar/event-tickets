<?php
/**
 * Abstract Cart
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Cart
 */

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Abstract_Cart
 *
 * @since 5.10.0
 */
abstract class Abstract_Cart implements Cart_Interface {
	/**
	 * Cart total
	 *
	 * @since 5.10.0
	 *
	 * @var null|float
	 */
	public $cart_total = null;

	/**
	 * Get the tickets currently in the cart for a given provider.
	 *
	 * @since 5.10.0
	 *
	 * @param bool $full_item_params Determines all the item params, including event_id, sub_total, and obj.
	 *
	 * @return array<string, mixed> List of items.
	 */
	public function get_items_in_cart( $full_item_params = false ): array {
		$items = $this->get_items();

		// When Items is empty in any capacity return an empty array.
		if ( empty( $items ) ) {
			return [];
		}

		if ( $full_item_params ) {
			$items = array_map(
				static function ( $item ) {
					$item['obj'] = Tickets::load_ticket_object( $item['ticket_id'] );
					// If it's an invalid ticket we just remove it.
					if ( ! $item['obj'] instanceof Ticket_Object ) {
						return null;
					}

					$sub_total_value = Value::create();
					$sub_total_value->set_value( $item['obj']->price );

					$item['event_id']  = $item['obj']->get_event_id();
					$item['sub_total'] = $sub_total_value->sub_total( $item['quantity'] );
					$item['type']      = 'ticket';

					return $item;
				},
				$items
			);
		}

		return array_filter( $items );
	}

	/**
	 * Get the total value of the cart, including additional values such as fees or discounts.
	 *
	 * This method calculates the total by first computing the subtotal from all items in the cart,
	 * and then applying any additional values (e.g., fees or discounts) provided via the `tec_tickets_commerce_get_cart_total_value` filter.
	 *
	 * @since TBD Refactored logic, to include a new filter.
	 * @since 5.10.0
	 *
	 * @return float|null The total value of the cart, or null if there are no items.
	 */
	public function get_cart_total(): ?float {
		$subtotal = $this->get_cart_subtotal();
		if ( null === $subtotal ) {
			return null;
		}

		$items = $this->get_items_in_cart( true );

		// Extract subtotals from the cart items.
		$sub_totals = array_filter( wp_list_pluck( $items, 'sub_total' ) );

		/**
		 * Filters the total value in the cart to add additional fees or discounts.
		 *
		 * Additional values must be instances of the `Value` class to ensure consistent behavior.
		 *
		 * @since TBD
		 *
		 * @param Value[] $values     An array of `Value` instances representing additional fees or discounts.
		 * @param array   $items      The items currently in the cart.
		 * @param Value   $sub_totals The total of the subtotals from the items.
		 */
		$additional_values = apply_filters(
			'tec_tickets_commerce_get_cart_total_value',
			[],
			$items,
			Value::create()->total( $sub_totals )
		);

		// Combine the subtotals and additional values.
		$total_value = Value::create()->total( array_merge( $sub_totals, $additional_values ) );

		$this->cart_total = $total_value->get_decimal();

		return $total_value->get_decimal();
	}

	/**
	 * Get the subtotal of the cart items.
	 *
	 * The subtotal is the sum of all item subtotals without additional values like fees or discounts.
	 *
	 * @since TBD Refactored to avoid cumulative calculations.
	 *
	 * @return float|null The subtotal of the cart.
	 */
	public function get_cart_subtotal(): ?float {
		// Reset cart_total to ensure it's not cumulative across calls.
		$this->cart_total = 0.0;

		$items = $this->get_items_in_cart( true );

		// If no items in the cart, return null.
		if ( empty( $items ) ) {
			return null;
		}

		// Calculate the total from the subtotals of each item.
		foreach ( $items as $item ) {
			$this->cart_total += $item['sub_total']->get_decimal();
		}

		return $this->cart_total;
	}
}
