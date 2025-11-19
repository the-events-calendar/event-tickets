<?php

namespace Tribe\Tickets\Repositories\Ticket\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Test the get_event_id() method in the RSVP Ticket Repository.
 *
 * @package Tribe\Tickets\Repositories\Ticket\RSVP
 */
class GetEventIdTest extends \Codeception\TestCase\WPTestCase {

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
	 * It should return correct event ID.
	 *
	 * @test
	 */
	public function should_return_correct_event_id() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_event_id( $ticket_id );

		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * It should return false for non-existent ticket.
	 *
	 * @test
	 */
	public function should_return_false_for_nonexistent_ticket() {
		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_event_id( 99999 );

		$this->assertFalse( $event_id );
	}

	/**
	 * It should work with different post types.
	 *
	 * @test
	 */
	public function should_work_with_different_post_types() {
		// Create a page as the event
		$page_id = $this->factory->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_rsvp_ticket( $page_id );

		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_event_id( $ticket_id );

		$this->assertEquals( $page_id, $event_id );
	}

	/**
	 * It should return integer event ID.
	 *
	 * @test
	 */
	public function should_return_integer_event_id() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_event_id( $ticket_id );

		$this->assertIsInt( $event_id );
		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * It should return false if event_id meta is missing.
	 *
	 * @test
	 */
	public function should_return_false_if_event_id_meta_is_missing() {
		$rsvp = tribe( 'tickets.rsvp' );
		$ticket_id = $this->factory->post->create( [
			'post_type' => $rsvp->ticket_object,
		] );
		// Don't set the _tribe_rsvp_for_event meta

		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_event_id( $ticket_id );

		$this->assertFalse( $event_id );
	}
}
