<?php

namespace Tribe\Tickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use WP_Query;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Query as Query;

class QueryTest extends WPTestCase {
	use Ticket_Maker;

	public function restrict_by_ticketed_status_provider(): Generator {
		yield 'no posts' => [
			function (): array {
				return [ new WP_Query(), [] ];
			}
		];

		yield '3 unticketed posts, not filtering' => [
			function (): array {
				$wp_query = new WP_Query();
				$wp_query->set( 'post_type', 'post' );
				$wp_query->set( 'fields', 'ids' );
				$ids = static::factory()->post->create_many( 3 );

				return [ $wp_query, $ids ];
			}
		];

		yield '3 ticketed, 4 unticketed posts, not filtering' => [
			function () {
				$wp_query = new WP_Query();
				$wp_query->set( 'post_type', 'post' );
				$wp_query->set( 'fields', 'ids' );
				$ids      = static::factory()->post->create_many( 4 );
				$ticketed = static::factory()->post->create_many( 3 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}

				return [ $wp_query, [ ...$ticketed, ...$ids ] ];
			}
		];

		yield '3 ticketed, 4 unticketed posts, filtering for ticketed' => [
			function () {
				$wp_query = new WP_Query();
				$wp_query->set( 'post_type', 'post' );
				$wp_query->set( 'fields', 'ids' );
				$unticketed = static::factory()->post->create_many( 4 );
				$ticketed   = static::factory()->post->create_many( 3 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}
				$wp_query->set( Query::$has_tickets, true );

				return [ $wp_query, $ticketed ];
			}
		];

		yield '3 ticketed, 4 unticketed posts, filtering for unticketed' => [
			function () {
				$wp_query = new WP_Query();
				$wp_query->set( 'post_type', 'post' );
				$wp_query->set( 'fields', 'ids' );
				$unticketed = static::factory()->post->create_many( 4 );
				$ticketed   = static::factory()->post->create_many( 3 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}
				$wp_query->set( Query::$has_tickets, false );

				return [ $wp_query, $unticketed ];
			}
		];
	}

	/**
	 * It should correctly restrict by ticketed status
	 *
	 * @test
	 * @dataProvider restrict_by_ticketed_status_provider
	 */
	public function should_correctly_restrict_by_ticketed_status( Closure $fixture ): void {
		[ $wp_query, $expected ] = $fixture();

		$query = tribe( 'tickets.query' );
		$query->restrict_by_ticketed_status( $wp_query );
		$posts = $wp_query->get_posts();

		$this->assertEqualSets( $expected, $posts );
	}

	/**
	 * It should count ticketed and unticketed posts correctly
	 *
	 * @test
	 */
	public function should_count_ticketed_and_unticketed_posts(): void {
		static::factory()->post->create_many( 4 );
		$ticketed = static::factory()->post->create_many( 3 );
		foreach ( $ticketed as $id ) {
			$this->create_tc_ticket( $id );
		}

		$query = tribe( 'tickets.query' );

		$this->assertEquals( 3, $query->get_ticketed_count( 'post' ) );
		$this->assertEquals( 4, $query->get_unticketed_count( 'post' ) );
	}

	/**
	 * It should exclude auto-draft and trashed posts from the counts
	 *
	 * @test
	 */
	public function should_exclude_auto_draft_and_trashed_posts_from_counts(): void {
		$ticketed = static::factory()->post->create_many( 2 );
		foreach ( $ticketed as $id ) {
			$this->create_tc_ticket( $id );
		}
		static::factory()->post->create_many( 3 );

		// A trashed and an auto-draft post should not be counted as unticketed.
		static::factory()->post->create( [ 'post_status' => 'trash' ] );
		static::factory()->post->create( [ 'post_status' => 'auto-draft' ] );

		$query = tribe( 'tickets.query' );

		$this->assertEquals( 2, $query->get_ticketed_count( 'post' ) );
		$this->assertEquals( 3, $query->get_unticketed_count( 'post' ) );
	}

	/**
	 * It should not count orphaned ticket meta whose event no longer exists
	 *
	 * A ticket-to-event meta can be left behind when its event is deleted. The count joins on
	 * `wp_posts` via the primary key, so such orphaned meta must not be counted as ticketed.
	 *
	 * @test
	 */
	public function should_not_count_orphaned_ticket_meta(): void {
		$ticketed = static::factory()->post->create_many( 2 );
		foreach ( $ticketed as $id ) {
			$this->create_tc_ticket( $id );
		}
		static::factory()->post->create_many( 3 );

		// Orphaned ticket meta pointing at an event that does not exist.
		$missing_event_id = PHP_INT_MAX;
		add_post_meta( static::factory()->post->create(), '_tec_tickets_commerce_event', $missing_event_id );

		// Orphaned ticket meta pointing at a deleted event.
		$deleted_event = static::factory()->post->create();
		add_post_meta( static::factory()->post->create(), '_tec_tickets_commerce_event', $deleted_event );
		wp_delete_post( $deleted_event, true );

		$query = tribe( 'tickets.query' );

		// Only the 2 real tickets are counted; the orphaned meta are ignored.
		$this->assertEquals( 2, $query->get_ticketed_count( 'post' ) );
	}

	/**
	 * It should honor the count query filters
	 *
	 * @test
	 */
	public function should_honor_the_count_query_filters(): void {
		$query = tribe( 'tickets.query' );

		add_filter( 'tec_tickets_query_ticketed_count_query', static fn() => 'SELECT 0' );
		add_filter( 'tec_tickets_query_unticketed_count_query', static fn() => 'SELECT 0' );

		$this->assertEquals( 0, $query->get_ticketed_count( 'post' ) );
		$this->assertEquals( 0, $query->get_unticketed_count( 'post' ) );
	}
}
