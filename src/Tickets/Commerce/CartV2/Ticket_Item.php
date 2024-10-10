<?php
/**
 * Ticket item class for Cart.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

/**
 * Class Ticket_Item
 *
 * Represents a ticket item in the cart.
 */
class Ticket_Item extends Abstract_Item {
	/**
	 * @var string The type of the item, which is 'ticket'.
	 */
	protected $type = 'ticket';

	/**
	 * Calculates the amount for the ticket item, potentially based on a subtotal.
	 *
	 * @param int|null $subtotal Optional. The subtotal value to base the calculation on. Default null.
	 *
	 * @return int The calculated amount in cents.
	 */
	public function get_amount( ?int $subtotal = null ): int {
		// For tickets, the amount is simply the value times the quantity.
		return $this->value * $this->quantity;
	}

	/**
	 * Checks if the ticket item should be counted in the subtotal.
	 *
	 * @return bool True if the ticket is counted in the subtotal, false otherwise.
	 */
	public function is_counted_in_subtotal(): bool {
		// Tickets should always be counted in the subtotal.
		return true;
	}

	/**
	 * Checks if the ticket item is in stock.
	 *
	 * @return bool True if the ticket is in stock, false otherwise.
	 *
	 * @todo Implement the stock logic for tickets.
	 */
	public function is_in_stock(): bool {
		// @todo - Add stock logic for ticket.
		return true;
	}
}
