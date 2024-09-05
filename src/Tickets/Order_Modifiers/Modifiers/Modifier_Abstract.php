<?php

namespace TEC\Tickets\Order_Modifiers\Modifiers;

/**
 * Abstract class for Order Modifiers (Coupons and Booking Fees).
 *
 * @since TBD
 */
abstract class Modifier_Abstract implements Modifier_Interface {

	/**
	 * The modifier type (e.g., 'coupon', 'fee').
	 *
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * Allowed sub_types (e.g., 'percentage', 'flat').
	 * Can be extended by concrete classes.
	 *
	 * @var array
	 */
	protected array $allowed_sub_types = [ 'percentage', 'flat' ];

	/**
	 * @inheritDoc
	 */
	abstract public function insert_modifier( array $data ): mixed;

	/**
	 * @inheritDoc
	 */
	abstract public function update_modifier( array $data ): mixed;

	/**
	 * Inserts or updates an Order Modifier to the system.
	 *
	 * @param array $data The data to save the modifier.
	 *
	 * @return mixed The result of the insert or update operation, or an empty array if no changes were made.
	 */
	public function save_modifier( array $data ): mixed {
		if ( empty( $data ) ) {
			return [];
		}

		$data['modifier_type'] = $this->modifier_type;

		// Perform base validation for required fields.
		if ( ! $this->validate_data( $data ) ) {
			return [];
		}

		// Determine if this is an update or an insert operation.
		if ( $this->is_update( $data ) ) {
			// Perform the update.
			$updated_data = $this->update_modifier( $data );
			return $updated_data ? : [];
		}

		// Otherwise, insert a new modifier.
		$inserted_data = $this->insert_modifier( $data );
		return $inserted_data ? : [];
	}

	/**
	 * Checks if the data contains an ID and is a valid update operation.
	 *
	 * @param array $data The data to check.
	 *
	 * @return bool True if the data contains a valid ID, indicating an update operation.
	 */
	protected function is_update( array $data ): bool {
		return isset( $data['id'] ) && is_int( $data['id'] ) && $data['id'] > 0;
	}

	/**
	 * Validates the basic data required for an Order Modifier.
	 * This method checks for essential fields and validates the sub_type.
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	protected function validate_data( array $data ): bool {
		$required_fields = [ 'post_id', 'sub_type', 'slug', 'display_name', 'status' ];

		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
				return false;
			}
		}

		// Validate the sub_type.
		return $this->validate_sub_type( $data['sub_type'] );
	}

	/**
	 * Validates the sub_type against the allowed sub_types.
	 * Can be customized by child classes.
	 *
	 * @param string $sub_type The sub_type to validate.
	 *
	 * @return bool True if the sub_type is valid, false otherwise.
	 */
	protected function validate_sub_type( string $sub_type ): bool {
		return in_array( $sub_type, $this->allowed_sub_types, true );
	}
}
