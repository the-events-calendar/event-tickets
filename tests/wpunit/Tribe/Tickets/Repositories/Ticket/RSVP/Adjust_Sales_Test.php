<?php

namespace Tribe\Tickets\Repositories\Ticket\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Test the adjust_sales() method in the RSVP Ticket Repository.
 *
 * @package Tribe\Tickets\Repositories\Ticket\RSVP
 */
class Adjust_Sales_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
	}

	/**
	 * It should increase sales and decrease stock atomically.
	 *
	 * @test
	 */
	public function should_increase_sales_and_decrease_stock_atomically() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 0,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, 2 );

		$this->assertEquals( 2, $new_sales );
		$this->assertEquals( 2, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 8, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should decrease sales and increase stock atomically.
	 *
	 * @test
	 */
	public function should_decrease_sales_and_increase_stock_atomically() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 5,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, -2 );

		$this->assertEquals( 3, $new_sales );
		$this->assertEquals( 3, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 7, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should prevent negative sales using GREATEST.
	 *
	 * @test
	 */
	public function should_prevent_negative_sales() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 2,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, -5 );

		// Should be clamped to 0, not -3
		$this->assertEquals( 0, $new_sales );
		$this->assertEquals( 0, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * It should prevent negative stock using GREATEST.
	 *
	 * @test
	 */
	public function should_prevent_negative_stock() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 8,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, 5 );

		// Sales should increase by 5
		$this->assertEquals( 13, $new_sales );
		$this->assertEquals( 13, get_post_meta( $ticket_id, 'total_sales', true ) );
		// Stock should be clamped to 0, not -3
		$this->assertEquals( 0, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should clear post meta cache after adjustment.
	 *
	 * @test
	 */
	public function should_clear_cache_after_adjustment() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 0,
			],
		] );

		// Prime the cache
		$initial_sales = get_post_meta( $ticket_id, 'total_sales', true );
		$this->assertEquals( 0, $initial_sales );

		$repository = tribe_tickets( 'rsvp' );
		$repository->adjust_sales( $ticket_id, 3 );

		// Cache should be cleared, so we get the new value
		$new_sales = get_post_meta( $ticket_id, 'total_sales', true );
		$this->assertEquals( 3, $new_sales );
	}

	/**
	 * It should handle zero delta (no change).
	 *
	 * @test
	 */
	public function should_handle_zero_delta() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'total_sales' => 5,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, 0 );

		$this->assertEquals( 5, $new_sales );
		$this->assertEquals( 5, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 5, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should return false for non-existent ticket.
	 *
	 * @test
	 */
	public function should_return_false_for_nonexistent_ticket() {
		$repository = tribe_tickets( 'rsvp' );
		$result = $repository->adjust_sales( 99999, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * It should handle large delta values.
	 *
	 * @test
	 */
	public function should_handle_large_delta_values() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 1000,
				'total_sales' => 0,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$new_sales = $repository->adjust_sales( $ticket_id, 500 );

		$this->assertEquals( 500, $new_sales );
		$this->assertEquals( 500, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 500, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should initialize meta keys if missing.
	 *
	 * @test
	 */
	public function should_initialize_meta_keys_if_missing() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->factory->post->create( [
			'post_type' => 'tribe_rsvp_tickets',
		] );

		// Don't create meta keys initially
		// (normally created by save_ticket() but we skip it)

		$repository = tribe_tickets( 'rsvp' );
		$result = $repository->adjust_sales( $ticket_id, 5 );

		$this->assertNotFalse( $result );
		$this->assertEquals( 5, get_post_meta( $ticket_id, 'total_sales', true ) );
		// Stock starts at 0, decreasing by 5 would be -5, but GREATEST clamps to 0
		$this->assertEquals( 0, get_post_meta( $ticket_id, '_stock', true ) );
	}
}
