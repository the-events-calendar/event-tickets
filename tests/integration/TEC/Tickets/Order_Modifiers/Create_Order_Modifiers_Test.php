<?php

namespace TEC\Tickets\Order_Modifiers;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Order_Modifiers\Repositories\Coupons as Coupons_Repository;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Order_Modifiers\Repositories\Fees as Fees_Repository;
use Tribe\Tickets\Test\Traits\Order_Modifiers;

class Create_Order_Modifiers_Test extends WPTestCase {
	use Order_Modifiers;

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
			'order_modifier_id'           => 0, // For creating a new modifier.
			'order_modifier_amount'       => '0', // Default fee of 10.00 USD in cents.
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_status'       => 'active',
			'order_modifier_slug'         => 'test_modifier',
			'order_modifier_display_name' => 'Test Modifier',
		];

		// Merge provided data with default data.
		$modifier_data = array_merge( $default_data, $data );

		// Assert that the modifier type is valid ('coupon' or 'fee').
		$valid_types = [ 'coupon', 'fee' ];
		$this->assertTrue( in_array( $modifier_data['modifier'], $valid_types ), 'Invalid modifier type provided.' );

		if ( 'coupon' === $modifier_data['modifier'] ) {
			$modifier_data['order_modifier_coupon_name'] = $modifier_data['order_modifier_display_name'];
		} elseif ( 'fee' === $modifier_data['modifier'] ) {
			$modifier_data['order_modifier_fee_name'] = $modifier_data['order_modifier_display_name'];
		}

		// Get the modifier type (e.g., coupon, fee, etc.).
		$modifier_type = sanitize_key( $modifier_data['modifier'] );

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// Assert that the strategy exists.
		$this->assertNotNull( $modifier_strategy, 'Modifier strategy should not be null.' );

		// Use the Modifier Manager to save the data.
		$manager     = new Modifier_Manager( $modifier_strategy );
		$mapped_data = $modifier_strategy->map_form_data_to_model( $modifier_data );

		// Assert that mapped data is valid and not null.
		$this->assertNotNull( $mapped_data, 'Mapped modifier data should not be null.' );

		// Save the modifier and assert it's not null after saving.
		$result = $manager->save_modifier( $mapped_data );
		$this->assertNotNull( $result, 'Failed to save modifier.' );

		// Return the saved modifier.
		return $result;
	}

	/**
	 * Helper method to retrieve an Order Modifier for tests with assertions.
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return Order_Modifier The retrieved order modifier.
	 */
	protected function get_order_modifier_for_test( int $modifier_id, $type = 'coupon' ) {
		// Assert that a valid modifier ID is provided.
		$this->assertGreaterThan( 0, $modifier_id, 'Modifier ID should be greater than zero.' );

		if ( 'coupon' === $type ) {
			$order_modifier_repository = new Coupons_Repository();
		} elseif ( 'fee' === $type ) {
			$order_modifier_repository = new Fees_Repository();
		}

		// Retrieve the modifier by ID.
		$modifier = $order_modifier_repository->find_by_id( $modifier_id );

		// Assert that the modifier exists.
		$this->assertNotNull( $modifier, "Order modifier with ID $modifier_id should not be null." );

		// Return the retrieved modifier.
		return $modifier;
	}

	/**
	 * Data provider for testing various order modifiers and edge cases using yield.
	 *
	 * @return \Generator
	 */
	public function order_modifier_provider(): \Generator {
		// Coupon - Flat Fee
		yield 'Coupon - Flat Fee' => [
			[
				'modifier'                    => 'coupon',
				'order_modifier_amount'       => 1000, // $10.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'flat_coupon',
				'order_modifier_display_name' => 'Flat Coupon',
			],
		];

		// Coupon - Percent Fee
		yield 'Coupon - Percent Fee' => [
			[
				'modifier'                    => 'coupon',
				'order_modifier_amount'       => 15, // 15% as cents (percentage is represented as an integer).
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => 'percent_coupon',
				'order_modifier_display_name' => 'Percent Coupon',
			],
		];

		// Fee - Flat
		yield 'Fee - Flat' => [
			[
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 500, // $5.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'flat_fee',
				'order_modifier_display_name' => 'Flat Fee',
			],
		];

		// Fee - Percent
		yield 'Fee - Percent' => [
			[
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 20, // 20% as cents (percentage is represented as an integer).
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => 'percent_fee',
				'order_modifier_display_name' => 'Percent Fee',
			],
		];

		// Edge Case: Negative Fee Amount
		yield 'Negative Fee Amount' => [
			[
				'modifier'                    => 'coupon',
				'order_modifier_amount'       => -1000, // Negative amount.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'negative_fee',
				'order_modifier_display_name' => 'Negative Fee',
			],
		];

		// Edge Case: Excessively Large Fee Amount
		yield 'Excessively Large Fee Amount' => [
			[
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 100000000, // $1,000,000 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'large_fee',
				'order_modifier_display_name' => 'Large Fee',
			],
		];

		// Edge Case: Invalid Sub-Type
		yield 'Invalid Sub-Type' => [
			[
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 1000,
				'order_modifier_sub_type'     => 'invalid_sub_type', // Invalid sub-type
				'order_modifier_slug'         => 'invalid_sub_type',
				'order_modifier_display_name' => 'Invalid Sub-Type',
			],
		];

		// Edge Case: Invalid Characters in Display Name
		yield 'Invalid Characters in Display Name' => [
			[
				'modifier'                    => 'coupon',
				'order_modifier_amount'       => 1000,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'invalid_characters',
				'order_modifier_display_name' => 'Invalid #$%@ Name!', // Special characters in display name
			],
		];
	}

	/**
	 * Test inserting and retrieving order modifiers with different types using a data provider.
	 *
	 * @dataProvider order_modifier_provider
	 *
	 * @param array $modifier_data The data for creating the order modifier.
	 */
	public function test_insert_and_get_order_modifier_with_data_provider( array $modifier_data ) {
		// Step 1: Insert the order modifier.
		$inserted_modifier = $this->upsert_order_modifier_for_test( $modifier_data );

		// Step 2: Retrieve the same modifier.
		$retrieved_modifier = $this->get_order_modifier_for_test( $inserted_modifier->id, $modifier_data['modifier'] );

		// Step 3: Assert that the retrieved data matches the inserted data.
		$this->assertEquals( $inserted_modifier->id, $retrieved_modifier->id );
		$this->assertEquals( $inserted_modifier->slug, $retrieved_modifier->slug );
		$this->assertEquals( $inserted_modifier->raw_amount, $retrieved_modifier->raw_amount );
		$this->assertEquals( $inserted_modifier->sub_type, $retrieved_modifier->sub_type );
		$this->assertEquals( $inserted_modifier->display_name, $retrieved_modifier->display_name );
	}

	/**
	 * Test inserting a new order modifier and validate upsert behavior.
	 *
	 * @test
	 */
	public function upsert_insert_new_order_modifier() {
		// Step 1: Insert a new order modifier.
		$insert_data = [
			'modifier'                    => 'coupon',
			'order_modifier_amount'       => 1000, // $10.00 in cents.
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_slug'         => 'test_coupon_insert',
			'order_modifier_display_name' => 'Test Coupon Insert',
		];

		$inserted_modifier = $this->upsert_order_modifier_for_test( $insert_data );

		// Step 2: Retrieve the newly inserted modifier.
		$retrieved_modifier = $this->get_order_modifier_for_test( $inserted_modifier->id, 'coupon' );

		// Step 3: Validate that the modifier was inserted and data matches.
		$this->assertEquals( $inserted_modifier->id, $retrieved_modifier->id );
		$this->assertEquals( $inserted_modifier->slug, $retrieved_modifier->slug );
		$this->assertEquals( $inserted_modifier->raw_amount, $retrieved_modifier->raw_amount );
		$this->assertEquals( $inserted_modifier->display_name, $retrieved_modifier->display_name );
	}

	/**
	 * Test updating an existing order modifier using the upsert method.
	 *
	 * @test
	 */
	public function upsert_update_existing_order_modifier() {
		// Step 1: Insert a new modifier.
		$insert_data       = [
			'modifier'                    => 'fee',
			'order_modifier_amount'       => 500, // $5.00 in cents.
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_slug'         => 'test_fee_update',
			'order_modifier_display_name' => 'Test Fee Insert',
		];
		$inserted_modifier = $this->upsert_order_modifier_for_test( $insert_data );

		// Step 2: Update the same modifier by providing the `order_modifier_id`.
		$update_data      = [
			'modifier'                    => 'fee',
			'order_modifier_id'           => $inserted_modifier->id, // Use the inserted modifier's ID.
			'order_modifier_amount'       => 1000, // Update to $10.00 in cents.
			'order_modifier_display_name' => 'Test Fee Updated',
		];
		$updated_modifier = $this->upsert_order_modifier_for_test( $update_data );

		// Step 3: Retrieve the updated modifier and validate changes.
		$retrieved_modifier = $this->get_order_modifier_for_test( $updated_modifier->id, 'fee' );

		// Validate that the updates are properly saved.
		$this->assertEquals( $updated_modifier->id, $retrieved_modifier->id );
		$this->assertEquals( 1000, $retrieved_modifier->raw_amount ); // Should now be $10.00.
		$this->assertEquals( 'Test Fee Updated', $retrieved_modifier->display_name ); // Name should be updated.
	}
}
