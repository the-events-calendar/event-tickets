<?php

namespace Tribe\Tickets\Repositories\Ticket;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;

/**
 * Test the RSVP Ticket Repository methods.
 *
 * @since TBD
 */
class RSVPTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * Test that adjust_sales increases sales and decreases stock atomically.
	 *
	 * @test
	 */
	public function test_adjust_sales_increases_sales_and_decreases_stock() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 0,
					'_stock'      => 10,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$new_sales  = $repository->adjust_sales( $ticket_id, 2 );

		$this->assertEquals( 2, $new_sales, 'adjust_sales should return new sales count' );
		$this->assertEquals( 2, get_post_meta( $ticket_id, 'total_sales', true ), 'Sales should be increased by 2' );
		$this->assertEquals( 8, get_post_meta( $ticket_id, '_stock', true ), 'Stock should be decreased by 2' );
	}

	/**
	 * Test that adjust_sales decreases sales and increases stock (refund scenario).
	 *
	 * @test
	 */
	public function test_adjust_sales_decreases_sales_and_increases_stock() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 5,
					'_stock'      => 5,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$new_sales  = $repository->adjust_sales( $ticket_id, -2 );

		$this->assertEquals( 3, $new_sales, 'adjust_sales should return new sales count after refund' );
		$this->assertEquals( 3, get_post_meta( $ticket_id, 'total_sales', true ), 'Sales should be decreased by 2' );
		$this->assertEquals( 7, get_post_meta( $ticket_id, '_stock', true ), 'Stock should be increased by 2' );
	}

	/**
	 * Test that adjust_sales handles zero stock correctly and doesn't go below 0.
	 *
	 * @test
	 */
	public function test_adjust_sales_handles_zero_stock_correctly() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 10,
					'total_sales' => 2,
					'_stock'      => 8,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Try to refund more than we have sales.
		$new_sales = $repository->adjust_sales( $ticket_id, -5 );

		$this->assertEquals( 0, $new_sales, 'Sales should not go below 0' );
		$this->assertEquals( 0, get_post_meta( $ticket_id, 'total_sales', true ), 'Sales should be 0' );
		// Stock should be increased by 2 (to remove all sales), not by 5.
		$this->assertEquals( 10, get_post_meta( $ticket_id, '_stock', true ), 'Stock should be restored to capacity' );
	}

	/**
	 * Test that adjust_sales returns false on invalid ticket.
	 *
	 * @test
	 */
	public function test_adjust_sales_returns_false_on_invalid_ticket() {
		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$result     = $repository->adjust_sales( 99999, 1 );

		$this->assertFalse( $result, 'adjust_sales should return false for invalid ticket ID' );
	}

	/**
	 * Test that get_field returns correct value for a valid field.
	 *
	 * @test
	 */
	public function test_get_field_returns_correct_value() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 25,
					'total_sales' => 5,
					'_stock'      => 20,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		$capacity = $repository->get_field( $ticket_id, 'capacity' );
		$this->assertEquals( 25, $capacity, 'get_field should return correct capacity value' );

		$sales = $repository->get_field( $ticket_id, 'sales' );
		$this->assertEquals( 5, $sales, 'get_field should return correct sales value' );

		$stock = $repository->get_field( $ticket_id, 'stock' );
		$this->assertEquals( 20, $stock, 'get_field should return correct stock value' );
	}

	/**
	 * Test that get_field returns null for invalid field.
	 *
	 * @test
	 */
	public function test_get_field_returns_null_for_invalid_field() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$result     = $repository->get_field( $ticket_id, 'invalid_field_name' );

		$this->assertNull( $result, 'get_field should return null for invalid field name' );
	}

	/**
	 * Test that get_field supports all field aliases.
	 *
	 * @test
	 */
	public function test_get_field_supports_aliases() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_tribe_rsvp_for_event'           => $post_id,
					'_price'                          => 25.00,
					'_capacity'                       => 50,
					'total_sales'                     => 10,
					'_stock'                          => 40,
					'_manage_stock'                   => 'yes',
					'_ticket_start_date'              => '2025-01-01 10:00:00',
					'_ticket_end_date'                => '2025-12-31 23:59:59',
					'_tribe_ticket_show_description'  => 'yes',
					'_tribe_rsvp_show_not_going'      => 'yes',
					'_global_stock_mode'              => 'own',
					'_global_stock_cap'               => 100,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Test all 12 field aliases from the implementation.
		$this->assertEquals( $post_id, $repository->get_field( $ticket_id, 'event_id' ), 'event_id alias should work' );
		$this->assertEquals( 25.00, $repository->get_field( $ticket_id, 'price' ), 'price alias should work' );
		$this->assertEquals( 50, $repository->get_field( $ticket_id, 'capacity' ), 'capacity alias should work' );
		$this->assertEquals( 10, $repository->get_field( $ticket_id, 'sales' ), 'sales alias should work' );
		$this->assertEquals( 40, $repository->get_field( $ticket_id, 'stock' ), 'stock alias should work' );
		$this->assertEquals( 'yes', $repository->get_field( $ticket_id, 'manage_stock' ), 'manage_stock alias should work' );
		$this->assertEquals( '2025-01-01 10:00:00', $repository->get_field( $ticket_id, 'start_date' ), 'start_date alias should work' );
		$this->assertEquals( '2025-12-31 23:59:59', $repository->get_field( $ticket_id, 'end_date' ), 'end_date alias should work' );
		$this->assertEquals( 'yes', $repository->get_field( $ticket_id, 'show_description' ), 'show_description alias should work' );
		$this->assertEquals( 'yes', $repository->get_field( $ticket_id, 'show_not_going' ), 'show_not_going alias should work' );
		$this->assertEquals( 'own', $repository->get_field( $ticket_id, 'global_stock_mode' ), 'global_stock_mode alias should work' );
		$this->assertEquals( 100, $repository->get_field( $ticket_id, 'global_stock_cap' ), 'global_stock_cap alias should work' );
	}

	/**
	 * Test that get_event_id returns correct event for a ticket.
	 *
	 * @test
	 */
	public function test_get_event_id_returns_correct_event() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$event_id   = $repository->get_event_id( $ticket_id );

		$this->assertEquals( $post_id, $event_id, 'get_event_id should return the correct event ID' );
		$this->assertIsInt( $event_id, 'get_event_id should return an integer' );
	}

	/**
	 * Test that get_event_id returns false for invalid ticket.
	 *
	 * @test
	 */
	public function test_get_event_id_returns_false_for_invalid_ticket() {
		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$result     = $repository->get_event_id( 99999 );

		$this->assertFalse( $result, 'get_event_id should return false for invalid ticket ID' );
	}

	/**
	 * Test that duplicate creates a new ticket with the same properties.
	 *
	 * @test
	 */
	public function test_duplicate_creates_new_ticket() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'post_title' => 'Original Ticket',
				'meta_input' => [
					'_capacity'   => 100,
					'total_sales' => 0,
					'_stock'      => 100,
				],
			]
		);

		$repository      = tribe( 'tickets.ticket-repository.rsvp' );
		$new_ticket_id   = $repository->duplicate( $ticket_id );

		$this->assertIsInt( $new_ticket_id, 'duplicate should return an integer ticket ID' );
		$this->assertGreaterThan( 0, $new_ticket_id, 'duplicate should return a valid ticket ID' );
		$this->assertNotEquals( $ticket_id, $new_ticket_id, 'duplicate should create a new ticket, not update the original' );

		// Verify the new ticket has the same properties.
		$original_title = get_post( $ticket_id )->post_title;
		$new_title      = get_post( $new_ticket_id )->post_title;
		$this->assertEquals( $original_title, $new_title, 'Duplicated ticket should have same title' );

		$original_capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$new_capacity      = get_post_meta( $new_ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( $original_capacity, $new_capacity, 'Duplicated ticket should have same capacity' );
	}

	/**
	 * Test that duplicate applies field overrides correctly.
	 *
	 * @test
	 */
	public function test_duplicate_applies_overrides() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'post_title' => 'Original Ticket',
				'meta_input' => [
					'_capacity'   => 100,
					'total_sales' => 0,
					'_stock'      => 100,
				],
			]
		);

		$repository    = tribe( 'tickets.ticket-repository.rsvp' );
		$new_ticket_id = $repository->duplicate(
			$ticket_id,
			[
				'title'    => 'Duplicated Ticket with Override',
				'capacity' => 50,
			]
		);

		$this->assertIsInt( $new_ticket_id, 'duplicate should return an integer ticket ID' );
		$this->assertGreaterThan( 0, $new_ticket_id, 'duplicate should return a valid ticket ID' );

		$new_title = get_post( $new_ticket_id )->post_title;
		$this->assertEquals( 'Duplicated Ticket with Override', $new_title, 'Duplicated ticket should have overridden title' );

		$new_capacity = get_post_meta( $new_ticket_id, '_tribe_ticket_capacity', true );
		$this->assertEquals( 50, $new_capacity, 'Duplicated ticket should have overridden capacity' );
	}

	/**
	 * Test that duplicate returns false for invalid ticket.
	 *
	 * @test
	 */
	public function test_duplicate_returns_false_for_invalid_ticket() {
		$repository = tribe( 'tickets.ticket-repository.rsvp' );
		$result     = $repository->duplicate( 99999 );

		$this->assertFalse( $result, 'duplicate should return false for invalid ticket ID' );
	}
}
