<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Test\Commerce\RSVP\V2\Ticket_Maker;

class RSVP_Cart_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_get_mode_returns_rsvp(): void {
		$cart = new RSVP_Cart();

		$this->assertSame( 'rsvp', $cart->get_mode() );
	}

	public function test_has_public_page_returns_false(): void {
		$cart = new RSVP_Cart();

		$this->assertFalse( $cart->has_public_page() );
	}

	public function test_get_cart_total_returns_zero(): void {
		$cart = new RSVP_Cart();

		$this->assertSame( 0.0, $cart->get_cart_total() );
	}

	public function test_get_cart_subtotal_returns_zero(): void {
		$cart = new RSVP_Cart();

		$this->assertSame( 0.0, $cart->get_cart_subtotal() );
	}

	public function test_requires_payment_returns_false(): void {
		$cart = new RSVP_Cart();

		$this->assertFalse( $cart->requires_payment() );
	}

	public function test_upsert_item_adds_item_to_cart(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 2 );

		$this->assertTrue( (bool) $cart->has_item( $ticket_id ) );
		$this->assertSame( 2, $cart->get_item_quantity( $ticket_id ) );
	}

	public function test_upsert_item_updates_existing_item(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 2 );
		$cart->upsert_item( $ticket_id, 5 ); // Replace, not add.

		$this->assertSame( 5, $cart->get_item_quantity( $ticket_id ) );
	}

	public function test_upsert_item_with_zero_removes_item(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 2 );
		$cart->upsert_item( $ticket_id, 0 );

		$this->assertFalse( $cart->has_item( $ticket_id ) );
	}

	public function test_remove_item_removes_from_cart(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 2 );
		$cart->remove_item( $ticket_id );

		$this->assertFalse( $cart->has_item( $ticket_id ) );
	}

	public function test_has_items_returns_count(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id1 = $this->create_rsvp_ticket( $event_id, [ 'name' => 'RSVP 1' ] );
		$ticket_id2 = $this->create_rsvp_ticket( $event_id, [ 'name' => 'RSVP 2' ] );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id1, 1 );
		$cart->upsert_item( $ticket_id2, 1 );

		$this->assertSame( 2, $cart->has_items() );
	}

	public function test_has_items_returns_false_when_empty(): void {
		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );

		$this->assertFalse( $cart->has_items() );
	}

	public function test_clear_removes_all_items(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 2 );
		$cart->clear();

		$this->assertFalse( $cart->has_items() );
		$this->assertEmpty( $cart->get_items() );
	}

	public function test_save_and_get_items_persistence(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-persist-hash' );
		$cart->upsert_item( $ticket_id, 3, [ 'custom' => 'data' ] );

		// Create a new cart instance with the same hash.
		$cart2 = new RSVP_Cart();
		$cart2->set_hash( 'test-persist-hash' );

		$items = $cart2->get_items();

		$this->assertArrayHasKey( $ticket_id, $items );
		$this->assertSame( 3, $items[ $ticket_id ]['quantity'] );
		$this->assertSame( [ 'custom' => 'data' ], $items[ $ticket_id ]['extra_data'] );
	}

	public function test_exists_returns_true_for_saved_cart(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-exists-hash' );
		$cart->upsert_item( $ticket_id, 1 );

		$cart2 = new RSVP_Cart();
		$cart2->set_hash( 'test-exists-hash' );

		$this->assertTrue( $cart2->exists() );
	}

	public function test_exists_returns_false_for_nonexistent_cart(): void {
		$cart = new RSVP_Cart();
		$cart->set_hash( 'nonexistent-hash-' . uniqid() );

		$this->assertFalse( $cart->exists() );
	}

	public function test_process_adds_items_to_cart(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 100 ] );

		$cart = new RSVP_Cart();
		$result = $cart->process( [
			'tickets' => [
				[
					'ticket_id' => $ticket_id,
					'quantity'  => 5,
				],
			],
		] );

		$this->assertTrue( $result );
		$this->assertSame( 5, $cart->get_item_quantity( $ticket_id ) );
	}

	public function test_process_clears_existing_cart(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id1 = $this->create_rsvp_ticket( $event_id, [ 'name' => 'RSVP 1', 'capacity' => 100 ] );
		$ticket_id2 = $this->create_rsvp_ticket( $event_id, [ 'name' => 'RSVP 2', 'capacity' => 100 ] );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-clear-hash' );
		$cart->upsert_item( $ticket_id1, 2 );

		// Process should clear and add only new items.
		$cart->process( [
			'tickets' => [
				[
					'ticket_id' => $ticket_id2,
					'quantity'  => 3,
				],
			],
		] );

		$this->assertFalse( $cart->has_item( $ticket_id1 ) );
		$this->assertSame( 3, $cart->get_item_quantity( $ticket_id2 ) );
	}

	public function test_process_returns_errors_for_invalid_ticket(): void {
		$cart = new RSVP_Cart();
		$result = $cart->process( [
			'tickets' => [
				[
					'ticket_id' => 999999, // Non-existent ticket.
					'quantity'  => 1,
				],
			],
		] );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertInstanceOf( \WP_Error::class, $result[0] );
	}

	public function test_process_returns_false_for_empty_data(): void {
		$cart = new RSVP_Cart();

		$this->assertFalse( $cart->process( [] ) );
		$this->assertFalse( $cart->process( [ 'tickets' => [] ] ) );
	}

	public function test_get_items_returns_empty_array_without_hash(): void {
		$cart = new RSVP_Cart();

		$this->assertEmpty( $cart->get_items() );
	}

	public function test_get_hash_returns_set_hash(): void {
		$cart = new RSVP_Cart();
		$cart->set_hash( 'my-hash' );

		$this->assertSame( 'my-hash', $cart->get_hash() );
	}

	public function test_cart_uses_separate_storage_from_tc_cart(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Create an RSVP cart.
		$rsvp_cart = new RSVP_Cart();
		$rsvp_cart->set_hash( 'same-hash' );
		$rsvp_cart->upsert_item( $ticket_id, 5 );

		// The RSVP cart transient should use the RSVP prefix.
		$transient_key = 'tec_rsvp_cart_' . md5( 'same-hash' );
		$stored_items = get_transient( $transient_key );

		$this->assertNotFalse( $stored_items );
		$this->assertArrayHasKey( $ticket_id, $stored_items );
	}
}
