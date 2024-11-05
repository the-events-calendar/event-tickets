<?php

namespace TEC\Tickets\Tests\Integration\Order_Modifiers\Models;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;

class Fee_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function can_create_fee_with_value_object() {
		$fee = Fee::create(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => new Float_Value( 1.00 ),
				'slug'         => 'test-fee',
				'status'       => 'active',
				'display_name' => 'Test Fee',
			]
		);

		$this->assertInstanceOf( Fee::class, $fee );
		$this->assertGreaterThan( 0, $fee->id );
		$this->assertEquals( 'flat', $fee->sub_type );
		$this->assertEquals( 1.00, $fee->raw_amount );
		$this->assertEquals( 'fee', $fee->modifier_type );
		$this->assertEquals( 'test-fee', $fee->slug );
		$this->assertEquals( 'active', $fee->status );
		$this->assertEquals( 'Test Fee', $fee->display_name );
		$this->assertNull( $fee->start_time );
		$this->assertNull( $fee->end_time );
	}

	/**
	 * @test
	 */
	public function can_create_fee_with_float() {
		$fee = Fee::create(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => 1.00,
				'slug'         => 'test-fee',
				'status'       => 'active',
				'display_name' => 'Test Fee',
			]
		);

		$this->assertInstanceOf( Fee::class, $fee );
		$this->assertGreaterThan( 0, $fee->id );
		$this->assertEquals( 'flat', $fee->sub_type );
		$this->assertEquals( 1.00, $fee->raw_amount );
		$this->assertEquals( 'fee', $fee->modifier_type );
		$this->assertEquals( 'test-fee', $fee->slug );
		$this->assertEquals( 'active', $fee->status );
		$this->assertEquals( 'Test Fee', $fee->display_name );
		$this->assertNull( $fee->start_time );
		$this->assertNull( $fee->end_time );
	}

	/**
	 * @test
	 */
	public function create_fee_from_query_builder() {
		$object                = new stdClass();
		$object->id            = 1;
		$object->modifier_type = 'fee';
		$object->sub_type      = 'flat';
		$object->raw_amount    = 1.00;
		$object->slug          = 'test-fee';
		$object->display_name  = 'Test Fee';
		$object->status        = 'active';
		$object->created_at    = '2024-10-25 00:00:00';
		$object->start_time    = null;
		$object->end_time      = null;

		$fee = Fee::fromQueryBuilderObject( $object );

		$this->assertInstanceOf( Fee::class, $fee );
		$this->assertEquals( 'fee', $fee->modifier_type );
		$this->assertEquals( 'flat', $fee->sub_type );
		$this->assertEquals( 1.00, $fee->raw_amount );
		$this->assertEquals( 'test-fee', $fee->slug );
		$this->assertEquals( 'Test Fee', $fee->display_name );
		$this->assertEquals( 'active', $fee->status );
		$this->assertEquals( '2024-10-25 00:00:00', $fee->created_at );
		$this->assertNull( $fee->start_time );
		$this->assertNull( $fee->end_time );
	}
}
