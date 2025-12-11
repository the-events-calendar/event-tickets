<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\RSVP\V2\Attendee;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\RSVP\V2\Ticket;
use TEC\Tickets\Test\Commerce\RSVP\V2\Ticket_Maker;
use WP_Error;

class Attendee_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_create_attendee(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $event_id );
		$order_id   = $this->create_mock_order();
		$attendee   = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
			'name'     => 'John Doe',
			'email'    => 'john@example.com',
		] );

		$this->assertIsInt( $attendee_id );
		$this->assertGreaterThan( 0, $attendee_id );

		// Verify it's a TC attendee post type.
		$post = get_post( $attendee_id );
		$this->assertSame( TC_Attendee::POSTTYPE, $post->post_type );

		// Verify meta was set.
		$this->assertSame( $event_id, (int) get_post_meta( $attendee_id, TC_Attendee::$event_relation_meta_key, true ) );
		$this->assertSame( $ticket_id, (int) get_post_meta( $attendee_id, TC_Attendee::$ticket_relation_meta_key, true ) );
		$this->assertSame( $order_id, (int) get_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, true ) );
		$this->assertSame( 'John Doe', get_post_meta( $attendee_id, TC_Attendee::$purchaser_name_meta_key, true ) );
		$this->assertSame( 'john@example.com', get_post_meta( $attendee_id, TC_Attendee::$purchaser_email_meta_key, true ) );
	}

	public function test_should_create_attendee_with_going_status_by_default(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $event_id );
		$order_id   = $this->create_mock_order();
		$attendee   = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
		] );

		$this->assertSame( Meta::STATUS_GOING, get_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, true ) );
	}

	public function test_should_create_attendee_with_not_going_status(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $event_id );
		$order_id   = $this->create_mock_order();
		$attendee   = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_NOT_GOING,
		] );

		$this->assertSame( Meta::STATUS_NOT_GOING, get_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, true ) );
	}

	public function test_should_return_error_for_invalid_order(): void {
		$attendee = tribe( Attendee::class );

		$result = $attendee->create( 999999, 1, [ 'event_id' => 1 ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_invalid_order', $result->get_error_code() );
	}

	public function test_should_return_error_for_invalid_ticket(): void {
		$order_id = $this->create_mock_order();
		$attendee = tribe( Attendee::class );

		$result = $attendee->create( $order_id, 999999, [ 'event_id' => 1 ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_invalid_ticket', $result->get_error_code() );
	}

	public function test_should_return_error_for_missing_event_id(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$order_id  = $this->create_mock_order();
		$attendee  = tribe( Attendee::class );

		$result = $attendee->create( $order_id, $ticket_id, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_missing_event', $result->get_error_code() );
	}

	public function test_get_status_returns_rsvp_status(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_GOING,
		] );

		$this->assertSame( Meta::STATUS_GOING, $attendee->get_status( $attendee_id ) );
	}

	public function test_set_status_updates_status(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_GOING,
		] );

		$result = $attendee->set_status( $attendee_id, Meta::STATUS_NOT_GOING );

		$this->assertTrue( $result );
		$this->assertSame( Meta::STATUS_NOT_GOING, get_post_meta( $attendee_id, Meta::RSVP_STATUS_KEY, true ) );
	}

	public function test_set_status_rejects_invalid_status(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
		] );

		$result = $attendee->set_status( $attendee_id, 'invalid_status' );

		$this->assertFalse( $result );
	}

	public function test_change_status_from_not_going_to_going(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 10 ] );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_NOT_GOING,
		] );

		$result = $attendee->change_status( $attendee_id, Meta::STATUS_GOING );

		$this->assertTrue( $result );
		$this->assertSame( Meta::STATUS_GOING, $attendee->get_status( $attendee_id ) );
	}

	public function test_change_status_from_going_to_not_going(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 10 ] );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );
		$ticket      = tribe( Ticket::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_GOING,
		] );

		// Mark as going first and reduce stock.
		$ticket->update_stock( $ticket_id, 1, 'decrease' );
		$initial_available = $ticket->get_available( $ticket_id );

		$result = $attendee->change_status( $attendee_id, Meta::STATUS_NOT_GOING );

		$this->assertTrue( $result );
		$this->assertSame( Meta::STATUS_NOT_GOING, $attendee->get_status( $attendee_id ) );

		// Stock should have increased.
		$this->assertSame( $initial_available + 1, $ticket->get_available( $ticket_id ) );
	}

	public function test_change_status_returns_error_when_capacity_full(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 1 ] );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );
		$ticket      = tribe( Ticket::class );

		// Fill up the capacity.
		$ticket->update_stock( $ticket_id, 1, 'decrease' );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_NOT_GOING,
		] );

		$result = $attendee->change_status( $attendee_id, Meta::STATUS_GOING );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_no_capacity', $result->get_error_code() );
	}

	public function test_change_status_succeeds_with_unlimited_capacity(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => -1 ] );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_NOT_GOING,
		] );

		$result = $attendee->change_status( $attendee_id, Meta::STATUS_GOING );

		$this->assertTrue( $result );
	}

	public function test_get_by_order_returns_attendees(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
			'name'     => 'Person 1',
		] );
		$attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
			'name'     => 'Person 2',
		] );

		$attendees = $attendee->get_by_order( $order_id );

		$this->assertCount( 2, $attendees );
	}

	public function test_get_by_ticket_returns_attendees(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_GOING,
		] );
		$attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_NOT_GOING,
		] );

		// All attendees.
		$all = $attendee->get_by_ticket( $ticket_id );
		$this->assertCount( 2, $all );

		// Only going.
		$going = $attendee->get_by_ticket( $ticket_id, Meta::STATUS_GOING );
		$this->assertCount( 1, $going );

		// Only not going.
		$not_going = $attendee->get_by_ticket( $ticket_id, Meta::STATUS_NOT_GOING );
		$this->assertCount( 1, $not_going );
	}

	public function test_is_rsvp_attendee_returns_true_for_rsvp_attendee(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
		] );

		$this->assertTrue( $attendee->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_is_rsvp_attendee_returns_false_for_regular_attendee(): void {
		$attendee = tribe( Attendee::class );

		// Create a regular TC attendee without RSVP status.
		$attendee_id = wp_insert_post( [
			'post_type'   => TC_Attendee::POSTTYPE,
			'post_status' => 'publish',
		] );

		$this->assertFalse( $attendee->is_rsvp_attendee( $attendee_id ) );
	}

	public function test_attendee_fires_created_action(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $event_id );
		$order_id   = $this->create_mock_order();
		$attendee   = tribe( Attendee::class );

		$action_fired = false;
		add_action( 'tec_tickets_rsvp_v2_attendee_created', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		$attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
		] );

		$this->assertTrue( $action_fired );
	}

	public function test_change_status_fires_status_changed_action(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 10 ] );
		$order_id    = $this->create_mock_order();
		$attendee    = tribe( Attendee::class );

		$attendee_id = $attendee->create( $order_id, $ticket_id, [
			'event_id'    => $event_id,
			'rsvp_status' => Meta::STATUS_GOING,
		] );

		$action_fired = false;
		add_action( 'tec_tickets_rsvp_v2_status_changed', function () use ( &$action_fired ) {
			$action_fired = true;
		} );

		$attendee->change_status( $attendee_id, Meta::STATUS_NOT_GOING );

		$this->assertTrue( $action_fired );
	}
}
