<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Order_Modifiers_Integration\Checkout;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Coupons;
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_No_Object_Storage;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Coupons_Test extends Controller_Test_Case {

	use Attendee_Maker;
	use Coupon_Creator;
	use Order_Maker;
	use Reservations_Maker;
	use Series_Pass_Factory;
	use SnapshotAssertions;
	use Ticket_Maker;
	use Type;
	use With_No_Object_Storage;
	use With_Tickets_Commerce;
	use With_Uopz;

	protected string $controller_class = Coupons::class;

	/**
	 * @test
	 */
	public function it_should_not_store_objects() {
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$coupon    = $this->create_coupon();

		$order = $this->create_order(
			[
				$ticket_id  => 1,
				$coupon->id => [
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		$this->assert_no_object_stored( get_post_meta( $order->ID ) );
	}

	/**
	 * @test
	 */
	public function it_should_calculate_the_order_total_correctly() {
		// Register the controller.
		$this->make_controller()->register();

		// Set up our test objects.
		$post      = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post, 10 );
		$coupon_1  = $this->create_coupon();
		$coupon_2  = $this->create_coupon( [ 'sub_type' => 'flat', 'raw_amount' => 2 ] );

		$order = $this->create_order(
			[
				$ticket_id    => 1,
				$coupon_1->id => [
					'id'       => $this->get_unique_type_id( $coupon_1->id, 'coupon' ),
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		/** @var Value $total */
		$total = $order->total_value;

		$this->assertEquals( 9.0, $total->get_float() );
		$this->assertEquals( 1, count( $order->items ) );
		$this->assertEquals( 1, count( $order->coupons ) );

		$order_2 = $this->create_order(
			[
				$ticket_id    => 1,
				$coupon_2->id => [
					'id'       => $this->get_unique_type_id( $coupon_2->id, 'coupon' ),
					'quantity' => 1,
					'extras'   => [ 'type' => 'coupon' ],
				],
			]
		);

		/** @var Value $total */
		$total = $order_2->total_value;

		$this->assertEquals( 8.0, $total->get_float() );
		$this->assertEquals( 1, count( $order_2->items ) );
		$this->assertEquals( 1, count( $order_2->coupons ) );
	}
}
