<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository as TC_Tickets_Repository;
use TEC\Tickets\Commerce\Traits\Is_Ticket;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * Tests for Repository_Filters.
 *
 * These integration tests verify that repository filters work correctly
 * by executing actual repository queries rather than calling the filter
 * methods directly.
 */
class Repository_Filters_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * Helper class to expose the is_ticket trait method for testing.
	 *
	 * @var object
	 */
	private $is_ticket_helper;

	/**
	 * @before
	 */
	public function set_up_is_ticket_helper(): void {
		// Create a helper class to expose the is_ticket method.
		$this->is_ticket_helper = new class {
			use Is_Ticket;

			public function check_is_ticket( array $thing ): bool {
				return $this->is_ticket( $thing );
			}
		};
	}

	public function test_should_exclude_rsvp_tickets_from_tc_repository_queries(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create a regular TC ticket (not RSVP).
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Query using the main Tickets Commerce repository.
		$repository = new TC_Tickets_Repository();
		$ticket_ids = $repository->get_ids();

		$this->assertContains(
			$regular_ticket_id,
			$ticket_ids,
			'Regular TC ticket should be returned from TC repository'
		);
		$this->assertNotContains(
			$rsvp_ticket_id,
			$ticket_ids,
			'TC-RSVP ticket should be excluded from TC repository'
		);
	}

	public function test_should_exclude_multiple_rsvp_tickets_from_tc_repository(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create multiple TC-RSVP tickets.
		$rsvp_ticket_ids = $this->create_many_tc_rsvp_tickets( 3, $post_id );

		// Create multiple regular TC tickets.
		$regular_ticket_ids = [
			$this->create_tc_ticket( $post_id, 10 ),
			$this->create_tc_ticket( $post_id, 20 ),
		];

		// Query using the main Tickets Commerce repository.
		$repository = new TC_Tickets_Repository();
		$ticket_ids = $repository->get_ids();

		foreach ( $regular_ticket_ids as $regular_id ) {
			$this->assertContains( $regular_id, $ticket_ids, "Regular TC ticket {$regular_id} should be returned" );
		}

		foreach ( $rsvp_ticket_ids as $rsvp_id ) {
			$this->assertNotContains( $rsvp_id, $ticket_ids, "TC-RSVP ticket {$rsvp_id} should be excluded" );
		}
	}

	public function test_should_not_exclude_rsvp_tickets_when_querying_by_specific_id(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Query by specific ID - this should include RSVP tickets.
		$repository = new TC_Tickets_Repository();
		$ticket     = $repository->by( 'id', $rsvp_ticket_id )->first();

		$this->assertNotNull( $ticket, 'TC-RSVP ticket should be found when querying by specific ID' );
		$this->assertEquals( $rsvp_ticket_id, $ticket->ID, 'Should return the correct TC-RSVP ticket' );
	}

	public function test_should_include_rsvp_tickets_when_explicitly_filtering_by_type(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		// Create a regular TC ticket.
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Query with explicit RSVP type filter.
		$repository = new TC_Tickets_Repository();
		$tickets    = $repository->by( 'type', Constants::TC_RSVP_TYPE )->all();
		$ticket_ids = array_map( static fn( $t ) => $t->ID, $tickets );

		$this->assertContains(
			$rsvp_ticket_id,
			$ticket_ids,
			'TC-RSVP ticket should be included when filtering by RSVP type'
		);
		$this->assertNotContains(
			$regular_ticket_id,
			$ticket_ids,
			'Regular TC ticket should be excluded when filtering by RSVP type'
		);
	}

	public function test_should_count_only_non_rsvp_tickets_in_tc_repository(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create 2 TC-RSVP tickets.
		$this->create_many_tc_rsvp_tickets( 2, $post_id );

		// Create 3 regular TC tickets.
		$this->create_tc_ticket( $post_id, 10 );
		$this->create_tc_ticket( $post_id, 20 );
		$this->create_tc_ticket( $post_id, 30 );

		// Count using the main TC repository should exclude RSVP.
		$repository = new TC_Tickets_Repository();
		$count      = $repository->count();

		$this->assertSame( 3, $count, 'TC repository count should only include non-RSVP tickets' );
	}

	public function test_should_recognize_tc_rsvp_as_ticket_type(): void {
		$thing = [
			'type' => Constants::TC_RSVP_TYPE,
		];

		$is_ticket = $this->is_ticket_helper->check_is_ticket( $thing );

		$this->assertTrue( $is_ticket, 'TC-RSVP type should be recognized as a ticket' );
	}

	public function test_should_recognize_regular_ticket_type(): void {
		$thing = [
			'type' => 'ticket',
		];

		$is_ticket = $this->is_ticket_helper->check_is_ticket( $thing );

		$this->assertTrue( $is_ticket, 'Regular ticket type should be recognized as a ticket' );
	}

	public function test_should_not_recognize_non_ticket_types(): void {
		$thing = [
			'type' => 'subscription',
		];

		$is_ticket = $this->is_ticket_helper->check_is_ticket( $thing );

		$this->assertFalse( $is_ticket, 'Non-ticket type should not be recognized as a ticket' );
	}

	public function test_should_assume_ticket_when_type_not_set(): void {
		$thing = [
			'name' => 'Some item',
		];

		$is_ticket = $this->is_ticket_helper->check_is_ticket( $thing );

		$this->assertTrue( $is_ticket, 'Items without type key should be assumed to be tickets' );
	}

	public function test_should_filter_tc_repository_by_event_excluding_rsvp(): void {
		$post_1_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post_2_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create TC-RSVP tickets for each post.
		$rsvp_ticket_1_id = $this->create_tc_rsvp_ticket( $post_1_id );
		$rsvp_ticket_2_id = $this->create_tc_rsvp_ticket( $post_2_id );

		// Create regular TC tickets for each post.
		$regular_ticket_1_id = $this->create_tc_ticket( $post_1_id, 10 );
		$regular_ticket_2_id = $this->create_tc_ticket( $post_2_id, 20 );

		// Query TC repository filtered by event should exclude RSVP.
		$repository = new TC_Tickets_Repository();
		$tickets    = $repository->by( 'event', $post_1_id )->all();
		$ticket_ids = array_map( static fn( $t ) => $t->ID, $tickets );

		$this->assertContains( $regular_ticket_1_id, $ticket_ids, 'Regular TC ticket for post 1 should be included' );
		$this->assertNotContains(
			$regular_ticket_2_id,
			$ticket_ids,
			'Regular TC ticket for post 2 should be excluded'
		);
		$this->assertNotContains( $rsvp_ticket_1_id, $ticket_ids, 'TC-RSVP ticket for post 1 should be excluded' );
		$this->assertNotContains( $rsvp_ticket_2_id, $ticket_ids, 'TC-RSVP ticket for post 2 should be excluded' );
	}

	public function test_should_not_duplicate_meta_query_when_filter_runs_multiple_times(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create tickets.
		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		// Create multiple repository instances to trigger the filter multiple times.
		$repository1 = new TC_Tickets_Repository();
		$repository2 = new TC_Tickets_Repository();

		$ids1 = $repository1->get_ids();
		$ids2 = $repository2->get_ids();

		// Both should return the same results.
		$this->assertEquals( $ids1, $ids2, 'Multiple repository instances should return consistent results' );
		$this->assertContains( $regular_ticket_id, $ids1 );
		$this->assertNotContains( $rsvp_ticket_id, $ids1 );
	}
}
