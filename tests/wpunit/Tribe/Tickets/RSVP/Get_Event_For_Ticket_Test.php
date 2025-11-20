<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Get_Event_For_Ticket_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @var RSVP
	 */
	protected $rsvp;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( RSVP::class );
	}

	/**
	 * It should return event post for valid ticket
	 *
	 * @test
	 */
	public function should_return_event_post_for_valid_ticket(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$event = $this->rsvp->get_event_for_ticket( $ticket_id );

		$this->assertInstanceOf( \WP_Post::class, $event );
		$this->assertEquals( $event_id, $event->ID );
		$this->assertEquals( 'tribe_events', $event->post_type );
	}

	/**
	 * It should return event post when passed ticket object
	 *
	 * @test
	 */
	public function should_return_event_post_when_passed_ticket_object(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$ticket_object = get_post( $ticket_id );
		$event         = $this->rsvp->get_event_for_ticket( $ticket_object );

		$this->assertInstanceOf( \WP_Post::class, $event );
		$this->assertEquals( $event_id, $event->ID );
	}

	/**
	 * It should return false for non-existent ticket
	 *
	 * @test
	 */
	public function should_return_false_for_non_existent_ticket(): void {
		$event = $this->rsvp->get_event_for_ticket( 999999 );

		$this->assertFalse( $event );
	}

	/**
	 * It should return false for ticket without event ID
	 *
	 * @test
	 */
	public function should_return_false_for_ticket_without_event_id(): void {
		// Create a ticket post but don't link it to any event
		$ticket_id = static::factory()->post->create( [
			'post_type' => RSVP::ATTENDEE_OBJECT,
		] );

		$event = $this->rsvp->get_event_for_ticket( $ticket_id );

		$this->assertFalse( $event );
	}

	/**
	 * It should return false for event with invalid post type
	 *
	 * @test
	 */
	public function should_return_false_for_event_with_invalid_post_type(): void {
		// Create a regular post (not an event)
		$post_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		// Create a ticket linked to the regular post
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$event = $this->rsvp->get_event_for_ticket( $ticket_id );

		// Should return false because 'post' is not a valid ticketable post type by default
		$this->assertFalse( $event );
	}

	/**
	 * It should handle attendee event key fallback
	 *
	 * @test
	 */
	public function should_handle_attendee_event_key_fallback(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = static::factory()->post->create( [
			'post_type' => RSVP::ATTENDEE_OBJECT,
		] );

		// Set event using the attendee event key instead of ticket event key
		update_post_meta( $ticket_id, RSVP::ATTENDEE_EVENT_KEY, $event_id );

		$event = $this->rsvp->get_event_for_ticket( $ticket_id );

		$this->assertInstanceOf( \WP_Post::class, $event );
		$this->assertEquals( $event_id, $event->ID );
	}

	/**
	 * It should use repository get_event_id method
	 *
	 * @test
	 */
	public function should_use_repository_get_event_id_method(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Verify repository returns correct event ID
		$repository = tribe_tickets( 'rsvp' );
		$retrieved_event_id = $repository->get_event_id( $ticket_id );

		$this->assertEquals( $event_id, $retrieved_event_id );

		// Now test that get_event_for_ticket uses this repository method
		$event = $this->rsvp->get_event_for_ticket( $ticket_id );

		$this->assertInstanceOf( \WP_Post::class, $event );
		$this->assertEquals( $event_id, $event->ID );
	}
}
