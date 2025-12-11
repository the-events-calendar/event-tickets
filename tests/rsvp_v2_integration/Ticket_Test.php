<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\RSVP\V2\Ticket;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Error;

class Ticket_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_create_rsvp_ticket(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'        => 'Test RSVP',
			'description' => 'Test Description',
		] );

		$this->assertIsInt( $ticket_id );
		$this->assertGreaterThan( 0, $ticket_id );

		// Verify ticket was created correctly.
		$ticket_post = get_post( $ticket_id );
		$this->assertSame( TC_Ticket::POSTTYPE, $ticket_post->post_type );
		$this->assertSame( 'Test RSVP', $ticket_post->post_title );
		$this->assertSame( 'Test Description', $ticket_post->post_excerpt );

		// Verify it's an RSVP ticket.
		$this->assertTrue( $ticket->is_rsvp_ticket( $ticket_id ) );

		// Verify pinged column is set.
		$this->assertSame( Meta::TC_RSVP_TYPE, $ticket_post->pinged );

		// Verify _type meta is set.
		$this->assertSame( Meta::TC_RSVP_TYPE, get_post_meta( $ticket_id, Meta::TYPE_META_KEY, true ) );

		// Verify price is 0 (RSVPs are free).
		$this->assertSame( '0', get_post_meta( $ticket_id, '_price', true ) );
	}

	public function test_should_require_name_to_create_ticket(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$result = $ticket->create( $post_id, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_missing_name', $result->get_error_code() );
	}

	public function test_should_return_error_for_invalid_post_id(): void {
		$ticket = tribe( Ticket::class );

		$result = $ticket->create( 999999, [ 'name' => 'Test' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_invalid_post', $result->get_error_code() );
	}

	public function test_should_create_ticket_with_limited_capacity(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		$this->assertIsInt( $ticket_id );
		$this->assertSame( 100, $ticket->get_available( $ticket_id ) );
		$this->assertTrue( $ticket->has_capacity( $ticket_id ) );
	}

	public function test_should_create_ticket_with_unlimited_capacity(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => -1,
		] );

		$this->assertIsInt( $ticket_id );
		$this->assertSame( -1, $ticket->get_available( $ticket_id ) );
		$this->assertTrue( $ticket->has_capacity( $ticket_id ) );
	}

	public function test_should_create_ticket_with_show_not_going_option(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'           => 'Test RSVP',
			'show_not_going' => true,
		] );

		// The meta is registered as boolean type, so it stores '1' instead of 'yes'.
		$this->assertTrue( tribe_is_truthy( get_post_meta( $ticket_id, Ticket::SHOW_NOT_GOING_META_KEY, true ) ) );
	}

	public function test_should_update_ticket(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name' => 'Original Name',
		] );

		$result = $ticket->update( $ticket_id, [
			'name'        => 'Updated Name',
			'description' => 'New Description',
		] );

		$this->assertTrue( $result );

		$ticket_post = get_post( $ticket_id );
		$this->assertSame( 'Updated Name', $ticket_post->post_title );
		$this->assertSame( 'New Description', $ticket_post->post_excerpt );
	}

	public function test_should_not_update_non_rsvp_ticket(): void {
		$post_id   = static::factory()->post->create();
		$ticket    = tribe( Ticket::class );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$result = $ticket->update( $ticket_id, [
			'name' => 'Updated Name',
		] );

		$this->assertFalse( $result );
	}

	public function test_should_delete_ticket(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name' => 'Test RSVP',
		] );

		$result = $ticket->delete( $ticket_id );

		$this->assertTrue( $result );

		// Verify ticket is trashed.
		$ticket_post = get_post( $ticket_id );
		$this->assertSame( 'trash', $ticket_post->post_status );
	}

	public function test_should_not_delete_non_rsvp_ticket(): void {
		$post_id   = static::factory()->post->create();
		$ticket    = tribe( Ticket::class );
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$result = $ticket->delete( $ticket_id );

		$this->assertFalse( $result );
	}

	public function test_should_decrease_stock(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		$result = $ticket->update_stock( $ticket_id, 10, 'decrease' );

		$this->assertTrue( $result );
		$this->assertSame( 90, $ticket->get_available( $ticket_id ) );
	}

	public function test_should_increase_stock(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		// First decrease.
		$ticket->update_stock( $ticket_id, 10, 'decrease' );

		// Then increase.
		$result = $ticket->update_stock( $ticket_id, 5, 'increase' );

		$this->assertTrue( $result );
		$this->assertSame( 95, $ticket->get_available( $ticket_id ) );
	}

	public function test_should_not_allow_negative_stock(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 10,
		] );

		// Try to decrease more than available.
		$result = $ticket->update_stock( $ticket_id, 100, 'decrease' );

		$this->assertTrue( $result );
		$this->assertSame( 0, $ticket->get_available( $ticket_id ) );
		$this->assertFalse( $ticket->has_capacity( $ticket_id ) );
	}

	public function test_should_return_false_for_invalid_stock_operation(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		$ticket_id = $ticket->create( $post_id, [
			'name'     => 'Test RSVP',
			'capacity' => 100,
		] );

		$result = $ticket->update_stock( $ticket_id, 10, 'invalid' );

		$this->assertFalse( $result );
	}

	public function test_should_filter_out_rsvp_tickets(): void {
		$post_id = static::factory()->post->create();
		$ticket  = tribe( Ticket::class );

		// Create a regular TC ticket.
		$tc_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Create an RSVP ticket.
		$rsvp_ticket_id = $ticket->create( $post_id, [
			'name' => 'Test RSVP',
		] );

		$tickets = [
			(object) [ 'ID' => $tc_ticket_id ],
			(object) [ 'ID' => $rsvp_ticket_id ],
		];

		$filtered = $ticket->filter_out_rsvp_tickets( $tickets );

		$this->assertCount( 1, $filtered );
		$this->assertSame( $tc_ticket_id, reset( $filtered )->ID );
	}

	public function test_should_add_post_type_to_cache(): void {
		$ticket = tribe( Ticket::class );

		$post_types = $ticket->add_post_type_to_cache( [] );

		$this->assertContains( TC_Ticket::POSTTYPE, $post_types );
	}

	public function test_should_fire_created_action(): void {
		$post_id      = static::factory()->post->create();
		$ticket       = tribe( Ticket::class );
		$action_fired = false;

		add_action( 'tec_tickets_rsvp_v2_ticket_created', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		$ticket->create( $post_id, [
			'name' => 'Test RSVP',
		] );

		$this->assertTrue( $action_fired );
	}

	public function test_should_fire_updated_action(): void {
		$post_id      = static::factory()->post->create();
		$ticket       = tribe( Ticket::class );
		$action_fired = false;

		$ticket_id = $ticket->create( $post_id, [
			'name' => 'Test RSVP',
		] );

		add_action( 'tec_tickets_rsvp_v2_ticket_updated', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		$ticket->update( $ticket_id, [
			'name' => 'Updated RSVP',
		] );

		$this->assertTrue( $action_fired );
	}

	public function test_should_fire_deleted_action(): void {
		$post_id      = static::factory()->post->create();
		$ticket       = tribe( Ticket::class );
		$action_fired = false;

		$ticket_id = $ticket->create( $post_id, [
			'name' => 'Test RSVP',
		] );

		add_action( 'tec_tickets_rsvp_v2_ticket_deleted', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		$ticket->delete( $ticket_id );

		$this->assertTrue( $action_fired );
	}
}
