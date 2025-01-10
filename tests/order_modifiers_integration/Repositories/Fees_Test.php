<?php

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Repositories;

use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use Tribe\Tickets\Test\Testcases\Order_Modifiers_TestCase;

/**
 * Class Fees
 *
 * @since TBD
 */
class Fees_Test extends Order_Modifiers_TestCase {

	protected string $modifier_type = 'fee';

	/**
	 * Helper method to create a set of modifiers for testing.
	 *
	 * @since TBD
	 * @return void
	 */
	protected function create_modifiers() {
		$samples = [
			[
				'amount'   => '1000',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '10',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '5',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '1',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '20',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '15',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '25',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '30',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '50',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '75',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '100',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '200',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '500',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '750',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '1000',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '1500',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '2000',
				'sub_type' => 'flat',
			],
			[
				'amount'   => '2500',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '3000',
				'sub_type' => 'percent',
			],
			[
				'amount'   => '5000',
				'sub_type' => 'flat',
			],
		];

		foreach ( $samples as $index => $sample ) {
			$data = [
				'order_modifier_amount'       => $sample['amount'],
				'order_modifier_sub_type'     => $sample['sub_type'],
				'order_modifier_slug'         => "test_fee_{$index}_1",
				'order_modifier_display_name' => "Test Fee {$index} – 1",
				'modifier'                    => $this->modifier_type,
			];

			$this->upsert_order_modifier_for_test( $data );

			$data['order_modifier_slug']         = "test_fee_{$index}_2";
			$data['order_modifier_display_name'] = "Test Fee {$index} – 2";

			$this->upsert_order_modifier_for_test( $data );
		}
	}

	/**
	 * @test
	 * @return void
	 */
	public function should_find_all_fees() {
		$this->create_modifiers();

		$repo = Factory::get_repository_for_type( $this->modifier_type );

		// Test that we get the correct count of all fees.
		$count = $repo->get_search_count();
		$this->assertEquals( 40, $count );

		// Test that we get the correct number of fees with a limit.
		$results = $repo->search_modifiers( [ 'limit' => 5 ] );
		$this->assertCount( 5, $results );
	}
}
