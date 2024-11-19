<?php

namespace Tribe\Tickets\Test\Traits;

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as Order_Modifiers_Repository;

trait Order_Modifiers {

	/**
	 * Helper method to insert an Order Modifier for tests with assertions.
	 *
	 * @param array $data The data for creating the modifier.
	 *
	 * @return Model The created or updated modifier.
	 */
	protected function upsert_order_modifier_for_test( array $data ): Model {
		// Set default data for the modifier.
		$default_data = [
			'order_modifier_id'           => 0,
			'order_modifier_amount'       => '0', // Default fee of 10.00 USD in cents.
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_status'       => 'active',
			'order_modifier_slug'         => 'test_modifier',
			'order_modifier_display_name' => 'Test Modifier',
		];

		// Merge provided data with default data.
		$modifier_data = array_merge( $default_data, $data );

		if ( 'coupon' === $modifier_data['modifier'] ) {
			$modifier_data['order_modifier_coupon_name'] = $modifier_data['order_modifier_display_name'];
		}

		// Get the modifier type (e.g., coupon, fee, etc.).
		$modifier_type = sanitize_key( $modifier_data['modifier'] );

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// Use the Modifier Manager to save the data.
		$manager     = new Modifier_Manager( $modifier_strategy );
		$mapped_data = $modifier_strategy->map_form_data_to_model( $modifier_data );

		// Save the modifier and assert it's not null after saving.
		return $manager->save_modifier( $mapped_data );
	}

	/**
	 * Helper method to retrieve an Order Modifier for tests with assertions.
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return Order_Modifier The retrieved order modifier.
	 */
	protected function get_order_modifier_for_test( int $modifier_id, $type = 'coupon' ) {
		$order_modifier_repository = new Order_Modifiers_Repository( $type );

		// Retrieve the modifier by ID.
		return $order_modifier_repository->find_by_id( $modifier_id );
	}

	protected function clear_all_modifiers( $type = 'coupon' ) {
		$order_modifier_repository = new Order_Modifiers_Repository( $type );

		$all_modifiers = $order_modifier_repository->get_all();

		foreach ( $all_modifiers as $modifier ) {
			$order_modifier_repository->delete( $modifier );
		}
	}
}
