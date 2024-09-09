<?php

namespace TEC\Tickets\Order_Modifiers\Modifiers;

use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;

/**
 * Concrete Strategy for Coupon Modifiers.
 *
 * @since TBD
 */
class Coupon implements Modifier_Strategy_Interface {

	/**
	 * The modifier type for coupons.
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';

	/**
	 * Gets the modifier type for coupons.
	 *
	 * @since TBD
	 *
	 * @return string The modifier type ('coupon').
	 */
	public function get_modifier_type(): string {
		return $this->modifier_type;
	}

	/**
	 * Inserts a new Coupon Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to insert.
	 *
	 * @return mixed The newly inserted modifier or an empty array if no changes were made.
	 */
	public function insert_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to 'coupon'.
		$data['modifier_type'] = $this->modifier_type;

		// Use the repository to insert the data into the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->insert( new Order_Modifier( $data ) );
	}

	/**
	 * Updates an existing Coupon Modifier.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to update.
	 *
	 * @return mixed The updated modifier or an empty array if no changes were made.
	 */
	public function update_modifier( array $data ): mixed {
		// Ensure the modifier_type is set to 'coupon'.
		$data['modifier_type'] = $this->modifier_type;

		// Use the repository to update the data in the `order_modifiers` table.
		$repository = new Order_Modifiers_Repository();
		return $repository->update( new Order_Modifier( $data ) );
	}

	/**
	 * Validates the required fields for Coupons.
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( array $data ): bool {
		$required_fields = [
			'post_id',
			'modifier_type',
			'sub_type',
			'fee_amount_cents',
			'slug',
			'display_name',
			'status',
		];

		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Renders the coupon table.
	 *
	 * @since TBD
	 *
	 * @return void The rendered coupon table.
	 */
	public function render_table($context) {
		// Your logic for rendering the coupon table.
		echo 'Rendered Coupons Table';
	}

	/**
	 * Renders the coupon edit screen.
	 *
	 * @since TBD
	 *
	 * @return mixed The rendered coupon edit screen.
	 */
	public function render_edit($context) {
		// Your logic for rendering the coupon edit screen.
		echo 'Rendered Coupon Edit Screen';
	}
}
