<?php

namespace TEC\Tickets\Tests\Integration\Order_Modifiers;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Order_Modifiers\Modifier_Admin_Handler;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Abstract;
use TEC\Tickets\Order_Modifiers\Table_Views\Order_Modifier_Table;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\Order_Modifiers;

abstract class Create_Order_Modifiers_Abstract extends WPTestCase {
	use Order_Modifiers;
	use With_Uopz;
	use SnapshotAssertions;

	/**
	 * The type of order modifier being tested ($this->modifier_type).
	 *
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * @before
	 */
	public function set_up(): void {
		$this->clear_all_modifiers( $this->modifier_type );
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$this->set_class_fn_return(
			Modifier_Abstract::class,
			'generate_unique_slug',
			'fixed_slug'
		);
		$this->set_class_fn_return(
			Order_Modifier_Table::class,
			'render_actions',
			static function ( $item ) {
				if ( isset( $item['display_name'] ) ) {
					return (string) $item['display_name'];
				}

				return 'Modifier Display Name';
			},
			true
		);
	}

	/**
	 * @after
	 * @return void
	 */
	public function tear_down() {
		$this->clear_all_modifiers( $this->modifier_type );
	}

	/**
	 * Data provider for testing various order modifiers and edge cases using yield.
	 *
	 * @return \Generator
	 */
	public function order_modifier_provider(): \Generator {
		$modifier_type_uc = ucfirst( $this->modifier_type );

		// Valid Modifier - Flat Fee
		yield "Valid {$modifier_type_uc} - Flat {$modifier_type_uc}" => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "flat_{$this->modifier_type}",
				'order_modifier_display_name' => "Flat {$modifier_type_uc}",
			],
			'is_valid' => true, // Indicating that this case should pass validation.
		];

		// Valid Modifier - Percent Fee
		yield "Valid {$modifier_type_uc} - Percent {$modifier_type_uc}" => [
			[
				'order_modifier_amount'       => 15,
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => "percent_{$this->modifier_type}",
				'order_modifier_display_name' => "Percent {$modifier_type_uc}",
			],
			'is_valid' => true,
		];

		// Edge Case - Negative Fee Amount
		yield "Edge Case - Negative {$modifier_type_uc} Amount" => [
			[
				'order_modifier_amount'       => -10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "negative_{$this->modifier_type}",
				'order_modifier_display_name' => "Negative {$modifier_type_uc}",
			],
			'is_valid' => false,
		];

		// Edge Case - Excessively Large Fee Amount
		yield "Edge Case - Excessively Large {$modifier_type_uc}" => [
			[
				'order_modifier_amount'       => 100000.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "large_{$this->modifier_type}",
				'order_modifier_display_name' => "Large {$modifier_type_uc}",
			],
			'is_valid' => true,
		];

		// Edge Case - Invalid Sub-Type
		yield "Edge Case - Invalid Sub-Type" => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'invalid_sub_type',
				'order_modifier_slug'         => 'invalid_sub_type',
				'order_modifier_display_name' => 'Invalid Sub-Type',
			],
			'is_valid' => false,
		];

		// Edge Case - Invalid Characters in Display Name
		yield "Edge Case - Invalid Characters in Display Name" => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'invalid_characters',
				'order_modifier_display_name' => 'Invalid #$%@ Name!',
			],
			'is_valid' => false,
		];

		// Edge Case - Missing Required Fields (Amount)
		yield "Edge Case - Missing Required Fields (Amount)" => [
			[
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'missing_amount',
				'order_modifier_display_name' => 'Missing Amount',
			],
			'is_valid' => false,
		];

		// Edge Case - Missing Required Fields (Slug)
		yield "Edge Case - Missing Required Fields (Slug)" => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_display_name' => 'Missing Slug',
			],
			'is_valid' => false,
		];

		// Edge Case - Duplicate Slug
		yield "Edge Case - Duplicate Slug" => [
			[
				'order_modifier_amount'       => 10.00,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'duplicate_slug',
				'order_modifier_display_name' => 'Duplicate Slug',
			],
			'is_valid' => true,
		];

		// Edge Case - Valid Amount with Decimal Precision
		yield "Valid {$modifier_type_uc} - Decimal Precision" => [
			[
				'order_modifier_amount'       => 10.05,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "decimal_{$this->modifier_type}",
				'order_modifier_display_name' => "Decimal {$modifier_type_uc}",
			],
			'is_valid' => true,
		];

		// Edge Case - Zero Amount
		yield "Edge Case - Zero Amount" => [
			[
				'order_modifier_amount'       => 0,
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "zero_amount_{$this->modifier_type}",
				'order_modifier_display_name' => "Zero Amount {$modifier_type_uc}",
			],
			'is_valid' => false,
		];
	}

	/**
	 * Test inserting and validating order modifiers using a data provider.
	 *
	 * @test
	 * @dataProvider order_modifier_provider
	 */
	public function insert_and_validate( array $modifier_data, bool $is_valid ) {
		$modifier_data['modifier'] = $this->modifier_type;

		if ( $is_valid ) {
			$inserted_order_modifier = $this->upsert_order_modifier_for_test( $modifier_data );
			$this->assertNotNull( $inserted_order_modifier );
		} else {
			try {
				$this->upsert_order_modifier_for_test( $modifier_data );
				$this->fail( "Expected validation failure, but {$this->modifier_type} was inserted." );
			} catch ( \Exception $e ) {
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
			'order_modifier_slug'         => "test_{$this->modifier_type}_update",
			'order_modifier_display_name' => "Test {$this->modifier_type} Insert",
		];
		$inserted_modifier = $this->upsert_order_modifier_for_test( $insert_data );

		// Step 2: Update the same modifier by providing the `order_modifier_id`.
		$update_data      = [
			'modifier'                    => $this->modifier_type,
			'order_modifier_id'           => $inserted_modifier->id,
			'order_modifier_amount'       => 10.00, // Update to $10.00 in cents.
			'order_modifier_display_name' => "Test {$this->modifier_type} Updated",
		];
		$updated_modifier = $this->upsert_order_modifier_for_test( $update_data );

		// Step 3: Retrieve the updated modifier and validate changes.
		$retrieved_modifier = $this->get_order_modifier_for_test( $updated_modifier->id, $this->modifier_type );

		// Validate that the updates are properly saved.
		$this->assertEquals( $updated_modifier->id, $retrieved_modifier->id );
		$this->assertEquals( 1000, $retrieved_modifier->raw_amount ); // Should now be $10.00.
		$this->assertEquals( $update_data['order_modifier_display_name'], $retrieved_modifier->display_name ); // Name should be updated.
	}

	public function does_edit_form_display_properly_with_no_data() {
		$modifier_admin_handler = new Modifier_Admin_Handler();
		$_POST                  = [
			'modifier'    => $this->modifier_type,
			'modifier_id' => 0,
			'edit'        => 1,
		];
		ob_start();
		$modifier_admin_handler->render_tec_order_modifiers_page();
		$test = ob_get_contents();
		ob_end_flush();
		return $test;
	}

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

		return $output;
	}

	/**
	 * Data provider for testing different scenarios of the modifier edit form.
	 *
	 * @return \Generator
	 */
	public function modifier_edit_form_data_provider(): \Generator {
		$modifier_type_uc = ucfirst( $this->modifier_type );

		yield "Flat {$modifier_type_uc}" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 5.00, // $5.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => "test_flat_{$this->modifier_type}",
				'order_modifier_display_name' => "Flat {$modifier_type_uc}",
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];

		yield "Percent {$modifier_type_uc}" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 10.00, // 10% as a percentage.
				'order_modifier_sub_type'     => 'percent',
				'order_modifier_slug'         => "test_percent_{$this->modifier_type}",
				'order_modifier_display_name' => "Percent {$modifier_type_uc}",
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];

		// Edge case: Long decimal value
		yield "{$modifier_type_uc} - Long Decimal Value" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 100.595, // Amount with long decimal (will be rounded).
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'long_decimal',
				'order_modifier_display_name' => 'Long Decimal',
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];

		// Edge case: Excessively large amount
		yield "{$modifier_type_uc} - Excessively Large Amount" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 123456790, // Large amount.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'large_amount',
				'order_modifier_display_name' => 'Large Amount',
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];

		// Edge case: Special characters in display name and slug
		yield "{$modifier_type_uc} - Special Characters" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 500, // $5.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'special_!@#$%^&*',
				'order_modifier_display_name' => 'Special !@#$%^&*',
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];

		// Edge case: Emojis in display name and slug
		yield "{$modifier_type_uc} - Emojis in Name and Slug" => [
			'insert_data' => [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => 1500, // $15.00 in cents.
				'order_modifier_sub_type'     => 'flat',
				'order_modifier_slug'         => 'emoji_😊🔥',
				'order_modifier_display_name' => "Emoji 😊🔥 {$modifier_type_uc}",
			],
			'post_data'   => [
				'modifier' => $this->modifier_type,
				'edit'     => 1,
			],
		];
	}


	protected function get_table_display() {
		for ( $i = 0; $i < 20; $i++ ) {
			// Step 1: Insert a new modifier.
			$insert_data = [
				'modifier'                    => $this->modifier_type,
				'order_modifier_amount'       => (float) $i,
				'order_modifier_sub_type'     => $i % 2 ? 'percent' : 'flat',
				'order_modifier_slug'         => sprintf( 'test_%1$s_%2$02d', $this->modifier_type, $i ),
				'order_modifier_display_name' => "Test {$this->modifier_type} Insert",
			];
			$this->upsert_order_modifier_for_test( $insert_data );
		}

		$modifier_admin_handler = new Modifier_Admin_Handler();
		$_POST = [
			'modifier' => $this->modifier_type,
		];
		ob_start();
		$modifier_admin_handler->render_tec_order_modifiers_page();
		$test = ob_get_contents();
		ob_end_flush();

		return $test;
	}
}
