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
}
