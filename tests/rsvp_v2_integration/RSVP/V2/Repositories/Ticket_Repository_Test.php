<?php
namespace TEC\Tickets\RSVP\V2\Repositories;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\RSVP_V2\TC_RSVP_Ticket_Maker;

class Ticket_Repository_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function set_up_ticketable_post_types(): void {
		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
	}

	/**
	 * @test
	 */
	public function it_should_return_only_tc_rsvp_tickets(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create a regular TC ticket (not RSVP).
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$repository = new Ticket_Repository();
		$tickets = $repository->all();

		$ticket_ids = array_map( static fn( $ticket ) => $ticket->ID, $tickets );

		$this->assertContains( $rsvp_ticket_id, $ticket_ids, 'TC-RSVP ticket should be returned' );
		$this->assertNotContains( $regular_ticket_id, $ticket_ids, 'Regular TC ticket should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_filter_by_event(): void {
		$post_1_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );
		$post_2_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create TC-RSVP tickets for each post.
		$ticket_1_id = $this->create_tc_rsvp_ticket( $post_1_id );
		$ticket_2_id = $this->create_tc_rsvp_ticket( $post_2_id );

		$repository = new Ticket_Repository();
		$tickets = $repository->by( 'event', $post_1_id )->all();

		$ticket_ids = array_map( static fn( $ticket ) => $ticket->ID, $tickets );

		$this->assertContains( $ticket_1_id, $ticket_ids, 'Ticket for post 1 should be returned' );
		$this->assertNotContains( $ticket_2_id, $ticket_ids, 'Ticket for post 2 should not be returned' );
	}

	/**
	 * @test
	 */
	public function it_should_return_count_of_tc_rsvp_tickets(): void {
		$repository = new Ticket_Repository();
		$count = $repository->fields('ids')->all();

		$this->assertSame(0, count($count), 'Got IDs: ' . implode(', ', $count));

		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create 3 TC-RSVP tickets.
		$this->create_many_tc_rsvp_tickets( 3, $post_id );

		// Create 2 regular TC tickets.
		$this->create_tc_ticket( $post_id, 10 );
		$this->create_tc_ticket( $post_id, 20 );

		$repository = new Ticket_Repository();
		$count = $repository->count();

		$this->assertSame( 3, $count, 'Should count only TC-RSVP tickets' );
	}

	/**
	 * @test
	 */
	public function it_should_return_first_tc_rsvp_ticket(): void {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		$first_ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$this->create_tc_rsvp_ticket( $post_id );

		$repository = new Ticket_Repository();
		$first = $repository->first();

		$this->assertNotNull( $first, 'First ticket should not be null' );
		$this->assertSame( $first_ticket_id, $first->ID, 'Should return the first TC-RSVP ticket' );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_when_no_tc_rsvp_tickets_exist(): void {
		$repository = new Ticket_Repository();
		$count = $repository->count();

		$this->assertSame(0, $count);

		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create only regular TC tickets.
		$this->create_tc_ticket( $post_id, 10 );
		$this->create_tc_ticket( $post_id, 20 );

		$repository = new Ticket_Repository();
		$tickets = $repository->all();
		$count = $repository->count();
		$first = $repository->first();

		$this->assertEmpty( $tickets, 'Should return empty array when no TC-RSVP tickets exist' );
		$this->assertSame( 0, $count, 'Count should be 0 when no TC-RSVP tickets exist' );
		$this->assertNull( $first, 'First should be null when no TC-RSVP tickets exist' );
	}
}
