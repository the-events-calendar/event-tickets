<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Performance benchmarking tests for RSVP repository pattern implementation.
 *
 * Compares query counts and execution efficiency between:
 * - Repository pattern operations
 * - Direct WordPress meta operations
 *
 * Metrics measured:
 * - Query count for ticket creation
 * - Query count for ticket updates
 * - Query count for single attendee reads
 * - Query count for bulk attendee updates
 *
 * Expected improvements:
 * - Bulk operations: -50% to -70% query reduction
 * - Single operations: Similar or slightly better
 * - No regression in simple read operations
 *
 * @since TBD
 */
class RSVP_Performance_Test extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var \Tribe__Tickets__RSVP
	 */
	private $rsvp;

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_user_can_manage_tickets(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( 'tickets.rsvp' );
	}

	/**
	 * Benchmark: Ticket creation query count.
	 *
	 * Measures queries needed to create a ticket using repository pattern.
	 *
	 * @test
	 */
	public function test_benchmark_ticket_creation_queries() {
		global $wpdb;

		$post_id = static::factory()->post->create();

		// Reset query count.
		$wpdb->num_queries = 0;

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->name        = 'Benchmark Ticket';
		$ticket->description = 'Benchmark Description';
		$ticket->price       = 0;
		$ticket->show_description = true;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 100,
				'stock'    => 100,
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		$queries_for_creation = $wpdb->num_queries;

		$this->assertGreaterThan( 0, $ticket_id, 'Ticket should be created' );

		// Log the query count for reference.
		// Expected: Reasonable number of queries (typically 10-20 for a new post with meta).
		$this->assertLessThan(
			30,
			$queries_for_creation,
			"Ticket creation should use reasonable queries. Used: $queries_for_creation"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Ticket creation queries: %d\n",
			$queries_for_creation
		) );
	}

	/**
	 * Benchmark: Ticket update query count.
	 *
	 * Measures queries needed to update ticket meta using repository.
	 *
	 * Expected improvement: Repository batch updates should use fewer queries
	 * than multiple update_post_meta calls.
	 *
	 * @test
	 */
	public function test_benchmark_ticket_update_queries() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Reset query count.
		$wpdb->num_queries = 0;

		// Update ticket using repository pattern.
		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->ID          = $ticket_id;
		$ticket->name        = 'Updated Ticket';
		$ticket->description = 'Updated Description';
		$ticket->price       = 10;
		$ticket->show_description = false;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 50,
				'stock'    => 50,
			],
		];

		$this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		$queries_for_update = $wpdb->num_queries;

		// Expected: Fewer queries than individual update_post_meta calls.
		// Typically 5-15 queries for updates with multiple meta fields.
		$this->assertLessThan(
			25,
			$queries_for_update,
			"Ticket update should use reasonable queries. Used: $queries_for_update"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Ticket update queries: %d\n",
			$queries_for_update
		) );
	}

	/**
	 * Benchmark: Bulk attendee update query count.
	 *
	 * This is where repository pattern should show significant improvement.
	 * Bulk updates should use far fewer queries than individual updates.
	 *
	 * Expected improvement: -50% to -70% query reduction.
	 *
	 * @test
	 */
	public function test_benchmark_bulk_attendee_update_queries() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create 20 attendees.
		$attendee_ids = $this->create_many_attendees_for_ticket(
			20,
			$ticket_id,
			$post_id,
			[ 'rsvp_status' => 'yes' ]
		);

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		// Measure repository bulk_update.
		$wpdb->num_queries = 0;

		$repository->bulk_update(
			$attendee_ids,
			[ 'attendee_status' => 'no' ]
		);

		$queries_bulk = $wpdb->num_queries;

		// Now measure equivalent operation without bulk (for comparison).
		// Reset attendees to 'yes'.
		foreach ( $attendee_ids as $attendee_id ) {
			update_post_meta( $attendee_id, '_tribe_rsvp_status', 'yes' );
		}

		// Measure individual updates.
		$wpdb->num_queries = 0;

		foreach ( $attendee_ids as $attendee_id ) {
			update_post_meta( $attendee_id, '_tribe_rsvp_status', 'no' );
		}

		$queries_individual = $wpdb->num_queries;

		// Repository bulk update should use significantly fewer queries.
		$improvement_percent = ( ( $queries_individual - $queries_bulk ) / $queries_individual ) * 100;

		$this->assertLessThan(
			$queries_individual,
			$queries_bulk,
			'Bulk update should use fewer queries than individual updates'
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Bulk update (20 attendees):\n" .
			"  Repository bulk_update: %d queries\n" .
			"  Individual updates: %d queries\n" .
			"  Improvement: %.1f%%\n",
			$queries_bulk,
			$queries_individual,
			$improvement_percent
		) );

		// Verify improvement meets target (at least 30% reduction).
		$this->assertGreaterThan(
			30,
			$improvement_percent,
			sprintf(
				'Bulk update should reduce queries by at least 30%%. Actual: %.1f%%',
				$improvement_percent
			)
		);
	}

	/**
	 * Benchmark: Single attendee read query count.
	 *
	 * Measures queries for reading attendee data using repository.
	 * Should be similar to direct meta access (no regression).
	 *
	 * @test
	 */
	public function test_benchmark_single_attendee_read_queries() {
		global $wpdb;

		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		// Measure repository get_field.
		$wpdb->num_queries = 0;

		$full_name = $repository->get_field( $attendee_id, 'full_name' );
		$email     = $repository->get_field( $attendee_id, 'email' );
		$status    = $repository->get_field( $attendee_id, 'attendee_status' );

		$queries_repository = $wpdb->num_queries;

		// Measure direct meta access.
		$wpdb->num_queries = 0;

		$full_name_direct = get_post_meta( $attendee_id, '_tribe_rsvp_full_name', true );
		$email_direct     = get_post_meta( $attendee_id, '_tribe_rsvp_email', true );
		$status_direct    = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );

		$queries_direct = $wpdb->num_queries;

		// Repository should be similar or better than direct access.
		$this->assertLessThanOrEqual(
			$queries_direct + 2, // Allow small margin
			$queries_repository,
			'Repository reads should not significantly increase queries'
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Single attendee reads (3 fields):\n" .
			"  Repository get_field: %d queries\n" .
			"  Direct get_post_meta: %d queries\n",
			$queries_repository,
			$queries_direct
		) );
	}

	/**
	 * Benchmark: Atomic sales adjustment query count.
	 *
	 * Measures queries for adjust_sales repository method.
	 * Should be minimal (ideally 1-2 queries).
	 *
	 * @test
	 */
	public function test_benchmark_atomic_sales_adjustment_queries() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 100,
					'total_sales' => 0,
					'_stock'      => 100,
				],
			]
		);

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Reset query count.
		$wpdb->num_queries = 0;

		$repository->adjust_sales( $ticket_id, 1 );

		$queries_adjust = $wpdb->num_queries;

		// Atomic operation should use very few queries.
		// Expected: 2-4 queries (get current value, update, maybe verify).
		$this->assertLessThan(
			6,
			$queries_adjust,
			"Atomic sales adjustment should use minimal queries. Used: $queries_adjust"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Atomic sales adjustment queries: %d\n",
			$queries_adjust
		) );
	}

	/**
	 * Benchmark: Get ticket by event query count.
	 *
	 * Measures queries for retrieving tickets by event using repository.
	 *
	 * @test
	 */
	public function test_benchmark_get_tickets_by_event_queries() {
		global $wpdb;

		$post_id = static::factory()->post->create();

		// Create 5 tickets for the event.
		$this->create_many_rsvp_tickets( 5, $post_id );

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Reset query count.
		$wpdb->num_queries = 0;

		$tickets = $repository->by( 'event', $post_id )->get_ids();

		$queries_get_tickets = $wpdb->num_queries;

		$this->assertCount( 5, $tickets, 'Should retrieve 5 tickets' );

		// Expected: Single query to get posts, possible meta queries.
		$this->assertLessThan(
			10,
			$queries_get_tickets,
			"Get tickets by event should use reasonable queries. Used: $queries_get_tickets"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Get tickets by event (5 tickets) queries: %d\n",
			$queries_get_tickets
		) );
	}

	/**
	 * Benchmark: Get status counts query count.
	 *
	 * Measures queries for get_status_counts repository method.
	 * Should use optimized SQL for counting.
	 *
	 * @test
	 */
	public function test_benchmark_get_status_counts_queries() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Create attendees with different statuses.
		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$this->create_many_attendees_for_ticket( 5, $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );

		$repository = tribe( 'tickets.attendee-repository.rsvp' );

		// Reset query count.
		$wpdb->num_queries = 0;

		$counts = $repository->get_status_counts( $post_id );

		$queries_status_counts = $wpdb->num_queries;

		$this->assertArrayHasKey( 'yes', $counts );
		$this->assertArrayHasKey( 'no', $counts );

		// Should use a single optimized query with GROUP BY.
		$this->assertLessThan(
			5,
			$queries_status_counts,
			"Get status counts should use optimized query. Used: $queries_status_counts"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Get status counts (15 attendees) queries: %d\n",
			$queries_status_counts
		) );
	}

	/**
	 * Benchmark: Ticket duplication query count.
	 *
	 * Measures queries for duplicating a ticket using repository.
	 *
	 * @test
	 */
	public function test_benchmark_ticket_duplication_queries() {
		global $wpdb;

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

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Reset query count.
		$wpdb->num_queries = 0;

		$new_ticket_id = $repository->duplicate( $ticket_id );

		$queries_duplicate = $wpdb->num_queries;

		$this->assertGreaterThan( 0, $new_ticket_id, 'Ticket should be duplicated' );

		// Expected: Similar to ticket creation (get original data, create new post with meta).
		$this->assertLessThan(
			35,
			$queries_duplicate,
			"Ticket duplication should use reasonable queries. Used: $queries_duplicate"
		);

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Ticket duplication queries: %d\n",
			$queries_duplicate
		) );
	}

	/**
	 * Benchmark: Multiple field updates with repository vs direct meta.
	 *
	 * Compares repository batch operations to individual meta updates.
	 *
	 * @test
	 */
	public function test_benchmark_multiple_field_updates_comparison() {
		global $wpdb;

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe( 'tickets.ticket-repository.rsvp' );

		// Test repository approach.
		$wpdb->num_queries = 0;

		$repository->by( 'id', $ticket_id )
			->set( 'capacity', 50 )
			->set( 'stock', 50 )
			->set( 'price', 25.00 )
			->set( 'show_description', 'yes' )
			->set( 'show_not_going', 'no' )
			->save();

		$queries_repository = $wpdb->num_queries;

		// Reset values for fair comparison.
		$ticket_id_2 = $this->create_rsvp_ticket( $post_id );

		// Test direct meta approach.
		$wpdb->num_queries = 0;

		update_post_meta( $ticket_id_2, '_tribe_ticket_capacity', 50 );
		update_post_meta( $ticket_id_2, '_stock', 50 );
		update_post_meta( $ticket_id_2, '_price', 25.00 );
		update_post_meta( $ticket_id_2, '_tribe_ticket_show_description', 'yes' );
		update_post_meta( $ticket_id_2, '_tribe_rsvp_show_not_going', 'no' );

		$queries_direct = $wpdb->num_queries;

		// Output comparison.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Update 5 fields:\n" .
			"  Repository pattern: %d queries\n" .
			"  Direct meta updates: %d queries\n",
			$queries_repository,
			$queries_direct
		) );

		// Repository should be competitive or better.
		$this->assertLessThanOrEqual(
			$queries_direct + 3, // Allow small margin for repository overhead
			$queries_repository,
			'Repository should not significantly increase queries for multiple updates'
		);
	}

	/**
	 * Summary benchmark: Overall performance comparison.
	 *
	 * Runs a complete workflow and compares overall query counts.
	 *
	 * @test
	 */
	public function test_benchmark_complete_workflow() {
		global $wpdb;

		$post_id = static::factory()->post->create();

		// Workflow: Create ticket, create 10 attendees, update ticket, bulk update attendees.
		$wpdb->num_queries = 0;

		// Create ticket.
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 50,
					'total_sales' => 0,
					'_stock'      => 50,
				],
			]
		);

		// Create attendees.
		$attendee_ids = $this->create_many_attendees_for_ticket(
			10,
			$ticket_id,
			$post_id,
			[ 'rsvp_status' => 'yes' ]
		);

		// Update ticket capacity.
		$ticket_repo = tribe( 'tickets.ticket-repository.rsvp' );
		$ticket_repo->by( 'id', $ticket_id )
			->set( 'capacity', 100 )
			->set( 'stock', 90 )
			->save();

		// Bulk update attendees.
		$attendee_repo = tribe( 'tickets.attendee-repository.rsvp' );
		$attendee_repo->bulk_update(
			$attendee_ids,
			[ 'attendee_status' => 'no' ]
		);

		$total_queries = $wpdb->num_queries;

		// Output for manual review.
		fwrite( STDERR, sprintf(
			"\n[BENCHMARK] Complete workflow (create ticket + 10 attendees + updates):\n" .
			"  Total queries: %d\n",
			$total_queries
		) );

		// Should use reasonable number of queries.
		// This is more of a reference than a strict limit.
		$this->assertGreaterThan( 0, $total_queries, 'Workflow should execute queries' );
	}
}
