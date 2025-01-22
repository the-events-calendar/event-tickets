<?php

namespace Tribe\Tickets\Test\Traits;

use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Meta_Keys;

trait Order_Modifiers {

	use Meta_Keys;

	/**
	 * Helper method to insert an Order Modifier for tests with assertions.
	 *
	 * @param array  $data       The data for creating the modifier.
	 * @param string $applied_to The post type or product ID the modifier is applied to.
	 *
	 * @return Model The created or updated modifier.
	 */
	protected function upsert_order_modifier_for_test( array $data, string $applied_to = '' ): Model {
		// Set default data for the modifier.
		$default_data = [
			'order_modifier_id'           => 0,
			'order_modifier_amount'       => '0',
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
		$modifier = $manager->save_modifier( $mapped_data );
		$this->assertNotNull( $modifier );

		// Add applied_to meta if provided.
		if ( ! empty( $applied_to ) ) {
			$this->add_applied_to_meta( $modifier->id, $applied_to, $modifier_type );
		}

		return $modifier;
	}

	/**
	 * Helper method to add applied_to meta to an Order Modifier for tests.
	 *
	 * @since 5.18.1
	 *
	 * @param int    $modifier_id The ID of the modifier to add the meta to.
	 * @param string $applied_to  The applied_to value (e.g. 'per', 'all').
	 * @param string $type        The type of the modifier (e.g. 'coupon', 'fee').
	 *
	 * @return void
	 */
	protected function add_applied_to_meta( int $modifier_id, string $applied_to, string $type ) {
		$meta = new Order_Modifier_Meta(
			[
				'order_modifier_id' => $modifier_id,
				'meta_key'          => $this->get_applied_to_key( $type ),
				'meta_value'        => $applied_to,
				'priority'          => 0,
			]
		);

		( new Order_Modifiers_Meta() )->upsert_meta( $meta );
	}

	/**
	 * Helper method to retrieve an Order Modifier for tests with assertions.
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return Order_Modifier The retrieved order modifier.
	 */
	protected function get_order_modifier_for_test( int $modifier_id, $type = 'coupon' ) {
		$order_modifier_repository = Factory::get_repository_for_type( $type );

		// Retrieve the modifier by ID.
		return $order_modifier_repository->find_by_id( $modifier_id );
	}

	protected function clear_all_modifiers( $type = 'coupon' ) {
		$order_modifier_repository = Factory::get_repository_for_type( $type );

		$all_modifiers = $order_modifier_repository->get_all();

		foreach ( $all_modifiers as $modifier ) {
			$order_modifier_repository->delete( $modifier );
		}
	}
}
