<?php
/**
 * Tests for the V2 RSVP_Cart class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */

namespace TEC\Tickets\RSVP\V2\Cart;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Constants;

/**
 * Class RSVP_Cart_Test
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */
class RSVP_Cart_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_start_with_no_items(): void {
		$cart = new RSVP_Cart();

		$this->assertEmpty( $cart->get_items() );
		$this->assertFalse( $cart->has_items() );
	}

	/**
	 * @test
	 */
	public function it_should_add_item_to_cart(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 2 );

		$this->assertTrue( $cart->has_item( 123 ) );
		$items = $cart->get_items();
		$this->assertCount( 1, $items );
		$this->assertEquals( 2, $items[123]['quantity'] );
	}

	/**
	 * @test
	 */
	public function it_should_update_existing_item_quantity(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 2 );
		$cart->upsert_item( 123, 5 );

		$items = $cart->get_items();
		$this->assertCount( 1, $items );
		$this->assertEquals( 5, $items[123]['quantity'] );
	}

	/**
	 * @test
	 */
	public function it_should_remove_item_when_quantity_is_zero(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 2 );
		$cart->upsert_item( 123, 0 );

		$this->assertFalse( $cart->has_item( 123 ) );
		$this->assertEmpty( $cart->get_items() );
	}

	/**
	 * @test
	 */
	public function it_should_remove_item_directly(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 2 );
		$cart->upsert_item( 456, 3 );
		$cart->remove_item( 123 );

		$this->assertFalse( $cart->has_item( 123 ) );
		$this->assertTrue( $cart->has_item( 456 ) );
		$this->assertCount( 1, $cart->get_items() );
	}

	/**
	 * @test
	 */
	public function it_should_report_correct_item_count(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 2 );
		$cart->upsert_item( 456, 3 );
		$cart->upsert_item( 789, 1 );

		$this->assertEquals( 3, $cart->has_items() );
	}

	/**
	 * @test
	 */
	public function it_should_store_tc_rsvp_id_when_adding_item(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 1 );

		$items = $cart->get_items();
		$this->assertEquals( 123, $items[123]['tc-rsvp_id'] );
		$this->assertEquals( 123, $items[123]['ticket_id'] );
	}

	/**
	 * @test
	 */
	public function it_should_store_extra_data_with_item(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 1, [ 'attendee_name' => 'John Doe' ] );

		$items = $cart->get_items();
		$this->assertEquals( [ 'attendee_name' => 'John Doe' ], $items[123]['extra'] );
	}

	/**
	 * @test
	 */
	public function it_should_use_tc_rsvp_type_as_default_item_type(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 1, [ 'type' => Constants::TC_RSVP_TYPE ] );

		$items = $cart->get_items();
		$this->assertEquals( Constants::TC_RSVP_TYPE, $items[123]['type'] );
	}

	/**
	 * @test
	 */
	public function it_should_filter_items_by_type(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 1, [ 'type' => Constants::TC_RSVP_TYPE ] );
		$cart->upsert_item( 456, 1, [ 'type' => 'other-type' ] );

		$tc_rsvp_items = $cart->get_items_in_cart( false, Constants::TC_RSVP_TYPE );
		$other_items   = $cart->get_items_in_cart( false, 'other-type' );
		$all_items     = $cart->get_items_in_cart( false, 'all' );

		$this->assertCount( 1, $tc_rsvp_items );
		$this->assertCount( 1, $other_items );
		$this->assertCount( 2, $all_items );
	}

	/**
	 * @test
	 */
	public function it_should_not_have_public_page(): void {
		$cart = new RSVP_Cart();

		$this->assertFalse( $cart->has_public_page() );
	}

	/**
	 * @test
	 */
	public function it_should_get_item_quantity(): void {
		$cart = new RSVP_Cart();

		$cart->upsert_item( 123, 5 );

		$this->assertEquals( 5, $cart->get_item_quantity( 123 ) );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_item_quantity(): void {
		$cart = new RSVP_Cart();

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Item not found in cart.' );

		$cart->get_item_quantity( 999 );
	}
}
