<?php

namespace TEC\Tickets\Order_Modifiers;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Abstract;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\Order_Modifiers;

class Create_Fees_Modifiers_Test extends WPTestCase {
	use Order_Modifiers;
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * The type of order modifier being tested (coupon).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * @before
	 */
	public function set_up(): void {
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$this->set_class_fn_return(
			Modifier_Abstract::class,
			'generate_unique_slug',
			'fixed_slug'
		);
	}

	/**
	 * Data provider for testing various order modifiers and edge cases using yield.
	 *
	 * @return \Generator
	 */
	/**
	 * Data provider for testing various order modifiers and edge cases using yield.
	 *
	 * @return \Generator
	 */
	public function order_modifier_provider(): \Generator {
		// Valid Coupon - Flat Fee
		yield 'Valid Coupon - Flat Coupon' => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'flat_coupon',
				'order_modifier_display_name' => 'Flat Coupon',
			],
			'is_valid' => true, // Indicating that this case should pass validation.
		];

		// Valid Coupon - Percent Fee
		yield 'Valid Coupon - Percent Coupon' => [
			[
				'order_modifier_amount'       => 15, // 15% as cents (percentage is represented as an integer).
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => 'percent_coupon',
				'order_modifier_display_name' => 'Percent Coupon',
			],
			'is_valid' => true, // This should pass validation as well.
		];

		// Edge Case - Negative Fee Amount
		yield 'Edge Case - Negative Fee Amount' => [
			[
				'order_modifier_amount'       => -10.00, // Negative amount, which should be caught.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'negative_fee',
				'order_modifier_display_name' => 'Negative Fee',
			],
			'is_valid' => false, // This should fail due to the negative amount.
		];

		// Edge Case - Excessively Large Fee Amount
		yield 'Edge Case - Excessively Large Fee Amount' => [
			[
				'order_modifier_amount'       => 100000.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'large_fee',
				'order_modifier_display_name' => 'Large Fee',
			],
			'is_valid' => true, // This should pass, unless specific validation restricts large values.
		];

		// Edge Case - Invalid Sub-Type
		yield 'Edge Case - Invalid Sub-Type' => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'invalid_sub_type', // Invalid sub-type.
				'order_modifier_slug'         => 'invalid_sub_type',
				'order_modifier_display_name' => 'Invalid Sub-Type',
			],
			'is_valid' => false, // This should fail due to the invalid sub-type.
		];

		// Edge Case - Invalid Characters in Display Name
		yield 'Edge Case - Invalid Characters in Display Name' => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'invalid_characters',
				'order_modifier_display_name' => 'Invalid #$%@ Name!', // Invalid characters in display name.
			],
			'is_valid' => false, // This should fail due to invalid characters in the display name.
		];

		// Edge Case - Missing Required Fields (Amount)
		yield 'Edge Case - Missing Required Fields (Amount)' => [
			[
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'missing_amount',
				'order_modifier_display_name' => 'Missing Amount',
			],
			'is_valid' => false, // Should fail due to missing required fields (amount).
		];

		// Edge Case - Missing Required Fields (Slug)
		yield 'Edge Case - Missing Required Fields (Slug)' => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_display_name' => 'Missing Slug',
			],
			'is_valid' => false, // Should fail due to missing slug.
		];

		// Edge Case - Duplicate Slug
		yield 'Edge Case - Duplicate Slug' => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'duplicate_slug',
				'order_modifier_display_name' => 'Duplicate Slug',
			],
			'is_valid' => true, // Assuming no prior slug exists; if a duplicate exists, this should fail.
		];

		// Edge Case - Valid Amount with Decimal Precision
		yield 'Valid Coupon - Decimal Precision' => [
			[
				'order_modifier_amount'       => 10.05,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'decimal_coupon',
				'order_modifier_display_name' => 'Decimal Coupon',
			],
			'is_valid' => true, // This should pass with decimal precision.
		];

		// Edge Case - Zero Amount
		yield 'Edge Case - Zero Amount' => [
			[
				'order_modifier_amount'       => 0,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'zero_amount_coupon',
				'order_modifier_display_name' => 'Zero Amount Coupon',
			],
			'is_valid' => false, // Should fail because the amount is zero.
		];
	}

	/**
	 * Test inserting and validating coupons with various edge cases using a data provider.
	 *
	 * @test
	 *
	 * @dataProvider order_modifier_provider
	 *
	 * @param array $modifier_data The data for creating the coupon.
	 * @param bool  $is_valid      Whether this test case is expected to pass validation.
	 */
	public function insert_and_validate_coupon( array $modifier_data, bool $is_valid ) {
		$modifier_data['modifier'] = $this->modifier_type;
		if ( $is_valid ) {
			// Expecting the coupon to be inserted successfully.
			$inserted_coupon = $this->upsert_order_modifier_for_test( $modifier_data );
			$this->assertNotNull( $inserted_coupon );
		} else {
			// Expecting the coupon insertion to fail.
			try {
				$this->upsert_order_modifier_for_test( $modifier_data );
				$this->fail( 'Expected validation failure, but coupon was inserted.' );
			} catch ( \Exception $e ) {
				// Validation failed as expected.
				$this->assertTrue( true, 'Validation failed as expected.' );
			}
		}
	}

	/**
	 * Test updating an existing order modifier using the upsert method.
	 *
	 * @test
	 */
	public function upsert_update_existing_order_modifier() {
		// Step 1: Insert a new modifier.
		$insert_data       = [
			'modifier'                    => $this->modifier_type,
			'order_modifier_amount'       => 5.00, // $5.00 in cents.
			'order_modifier_sub_type'     => 'flat',
			'order_modifier_slug'         => 'test_coupon_update',
			'order_modifier_display_name' => 'Test Coupon Insert',
		];
		$inserted_modifier = $this->upsert_order_modifier_for_test( $insert_data );

		// Step 2: Update the same modifier by providing the `order_modifier_id`.
		$update_data      = [
			'modifier'                    => $this->modifier_type,
			'order_modifier_id'           => $inserted_modifier->id, // Use the inserted modifier's ID.
			'order_modifier_amount'       => 10.00, // Update to $10.00 in cents.
			'order_modifier_display_name' => 'Test Coupon Updated',
		];
		$updated_modifier = $this->upsert_order_modifier_for_test( $update_data );

		// Step 3: Retrieve the updated modifier and validate changes.
		$retrieved_modifier = $this->get_order_modifier_for_test( $updated_modifier->id, $this->modifier_type );

		// Validate that the updates are properly saved.
		$this->assertEquals( $updated_modifier->id, $retrieved_modifier->id );
		$this->assertEquals( 1000, $retrieved_modifier->raw_amount ); // Should now be $10.00.
		$this->assertEquals( $update_data['order_modifier_display_name'], $retrieved_modifier->display_name ); // Name should be updated.
	}

	/**
	 * @test
	 */
	public function does_edit_form_display_properly_with_no_data() {
		$modifier_admin_handler = new Modifier_Admin_Handler();
		$_POST                  = [
			'modifier'    => 'fee',
			'modifier_id' => 0,
			'edit'        => 1,
		];
		ob_start();
		$modifier_admin_handler->render_tec_order_modifiers_page();
		$test = ob_get_contents();
		ob_end_flush();
		$this->assertMatchesHtmlSnapshot( $test );
	}

	/**
	 * @test
	 * @dataProvider modifier_edit_form_data_provider
	 */
	public function does_edit_form_display_properly_with_data( array $insert_data, array $post_data ) {
		// Step 1: Insert a new modifier.
		$inserted_modifier = $this->upsert_order_modifier_for_test( $insert_data );

		// Update the modifier ID in the POST data with the inserted modifier's ID.
		$post_data['modifier_id'] = $inserted_modifier->id;

		// Initialize the Modifier Admin Handler.
		$modifier_admin_handler = new Modifier_Admin_Handler();

		// Set the $_POST data for the request.
		$_POST = $post_data;

		// Capture the output of the render method.
		ob_start();
		$modifier_admin_handler->render_tec_order_modifiers_page();
		$output = ob_get_contents();
		ob_end_clean();

		// Assert that the form renders correctly by comparing to the snapshot.
		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * Data provider for testing different scenarios of the modifier edit form.
	 *
	 * @return \Generator
	 */
	public function modifier_edit_form_data_provider(): \Generator {
		yield 'Flat Fee' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 5.00, // $5.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'test_flat_fee',
				'order_modifier_display_name' => 'Flat Fee',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		yield 'Percent Fee' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 10.00, // 10% as a percentage.
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => 'test_percent_fee',
				'order_modifier_display_name' => 'Percent Fee',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		yield 'Invalid Slug' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 7.00, // $7.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => '', // Invalid slug (empty).
				'order_modifier_display_name' => 'Invalid Slug Coupon',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];
		yield 'Flat Fee' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 1000, // $10.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'flat_fee',
				'order_modifier_display_name' => 'Flat Fee',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		yield 'Percent Fee' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 10.00, // 10% as a percentage.
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => 'percent_fee',
				'order_modifier_display_name' => 'Percent Fee',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		// Edge case: Long decimal value
		yield 'Fee - Long Decimal Value' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 100.595, // Amount with long decimal (will be rounded).
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'long_decimal',
				'order_modifier_display_name' => 'Long Decimal',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		// Edge case: Excessively large amount
		yield 'Fee - Excessively Large Amount' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 123456790, // Large amount.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'large_amount',
				'order_modifier_display_name' => 'Large Amount',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		// Edge case: Special characters in display name and slug
		yield 'Fee - Special Characters' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 500, // $5.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'special_!@#$%^&*',
				'order_modifier_display_name' => 'Special !@#$%^&*',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];

		// Edge case: Emojis in display name and slug
		yield 'Fee - Emojis in Name and Slug' => [
			'insert_data' => [
				'modifier'                    => 'fee',
				'order_modifier_amount'       => 1500, // $15.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'emoji_😊🔥',
				'order_modifier_display_name' => 'Emoji 😊🔥 Fee',
			],
			'post_data'   => [
				'modifier' => 'fee',
				'edit'     => 1,
			],
		];
	}
}
