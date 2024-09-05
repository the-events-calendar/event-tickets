<?php

namespace TEC\Tickets\Order_Modifiers\Modifiers;

use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;

/**
 * Class for handling Coupon Modifiers.
 *
 * @since TBD
 */
class Coupon extends Modifier_Abstract {

	/**
	 * The modifier type for coupons.
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * Inserts a new Coupon Modifier.
	 *
	 * @param array $data The data to insert.
	 *
	 * @return mixed The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): mixed {
		// Use the repository to insert the data into the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->insert( new Order_Modifier( $data ) );
	}

	/**
	 * Updates an existing Coupon Modifier.
	 *
	 * @param array $data The data to update.
	 *
	 * @return mixed The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): mixed {
		// Use the repository to update the data in the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->update( new Order_Modifier( $data ) );
	}

	/**
	 * Overrides the base validation method to ensure required fields for Coupons are present.
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	protected function validate_data( array $data ): bool {
		// Call the base validation for common fields.
		if ( ! parent::validate_data( $data ) ) {
			return false;
		}

		// Additional coupon-specific validation (e.g., fee_amount_cents).
		if ( empty( $data['fee_amount_cents'] ) || ! is_int( $data['fee_amount_cents'] ) ) {
			return false;
		}

		return true;
	}
}
