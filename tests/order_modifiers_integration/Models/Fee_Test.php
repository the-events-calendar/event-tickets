<?php

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use stdClass;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Exceptions\Not_Found_Exception;
use Codeception\TestCase\WPTestCase;

class Fee_Test extends WPTestCase {
	use Fee_Creator;
	use Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_find_by_id() {
		$post = self::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post );
		$fee = $this->create_fee_for_ticket( $ticket );

		$model = Fee::find( $fee );

		$this->assertInstanceOf( Order_Modifier::class, $model );
		$this->assertEquals( $fee, $model->id );
	}

	/**
	 * @test
	 */
	public function it_should_delete() {
		$fee = Fee::create(
			[
				'sub_type'     => 'flat',
				'raw_amount'   => new Float_Value( 1.00 ),
				'slug'         => 'test-fee',
				'status'       => 'active',
				'display_name' => 'Test Fee',
			]
		);

		$fee->delete();

		$this->expectException( Not_Found_Exception::class );
		Fee::find( $fee->id );
	}

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

		$model = Fee::find( $fee->id );

		$this->assertInstanceOf( Fee::class, $model );
		$this->assertGreaterThan( 0, $model->id );
		$this->assertEquals( 'flat', $model->sub_type );
		$this->assertEquals( 1.00, $model->raw_amount );
		$this->assertEquals( 'fee', $model->modifier_type );
		$this->assertEquals( 'test-fee', $model->slug );
		$this->assertEquals( 'active', $model->status );
		$this->assertEquals( 'Test Fee', $model->display_name );
		$this->assertNull( $model->start_time );
		$this->assertNull( $model->end_time );
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
