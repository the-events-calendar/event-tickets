<?php
/**
 * Fee item class for Cart.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

/**
 * Class Fee_Item
 *
 * Represents a fee item in the cart.
 */
class Fee_Item extends Abstract_Item {
	/**
	 * @var string The type of the item, which is 'fee'.
	 */
	protected $type = 'fee';

	/**
	 * Fee_Item constructor.
	 *
	 * @param string|int  $id       The ID of the fee item.
	 * @param int         $quantity The quantity of the fee item.
	 * @param int         $value    The value of the fee item in cents.
	 * @param string|null $sub_type Optional. The subtype of the fee ('flat' or 'percent'). Default 'flat'.
	 */
	public function __construct( $id, int $quantity, int $value, string $sub_type = 'flat' ) {
		parent::__construct( $id, $quantity, $value, $sub_type );
	}

	/**
	 * Calculates the amount for the fee item, potentially based on a subtotal.
	 *
	 * @param int|null $subtotal Optional. The subtotal value to base the calculation on. Default null.
	 *
	 * @return int The calculated fee amount in cents.
	 */
	public function get_amount( ?int $subtotal = null ): int {
		// @todo switch to case sttement, for percent, flat, default - throw an exception - Maybe create trait for the subtype
		if ( $this->sub_type === 'percent' && $subtotal !== null ) {
			// Calculate percentage fee based on the subtotal.
			return (int) ( $subtotal * ( $this->value / 100 ) );
		}

		// Flat fee in cents.
		return $this->value;
	}

	/**
	 * Checks if the fee item should be counted in the subtotal.
	 *
	 * @return bool False, as fees should not be included in the subtotal calculation.
	 */
	public function is_counted_in_subtotal(): bool {
		// Fees should not be counted in the subtotal, but should affect the total.
		return false;
	}

	/**
	 * Checks if the fee item is in stock.
	 *
	 * @return bool True if the fee is in stock, false otherwise.
	 */
	public function is_in_stock(): bool {
		return true;
	}
}
