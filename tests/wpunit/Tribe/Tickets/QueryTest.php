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
	 * Data provider for should_return_correct_ticketed_count.
	 *
	 * @return Generator
	 */
	public function get_ticketed_count_provider(): Generator {
		yield 'no posts at all' => [
			function (): array {
				return [ 'post', 0 ];
			}
		];

		yield '3 unticketed posts, none ticketed' => [
			function (): array {
				static::factory()->post->create_many( 3 );

				return [ 'post', 0 ];
			}
		];

		yield '3 ticketed posts' => [
			function (): array {
				$ticketed = static::factory()->post->create_many( 3 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}

				return [ 'post', 3 ];
			}
		];

		yield '2 ticketed, 4 unticketed posts' => [
			function (): array {
				static::factory()->post->create_many( 4 );
				$ticketed = static::factory()->post->create_many( 2 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}

				return [ 'post', 2 ];
			}
		];
	}

	/**
	 * It should return correct int count of ticketed posts, including 0 when none exist.
	 *
	 * @test
	 * @dataProvider get_ticketed_count_provider
	 *
	 * @param Closure $fixture Fixture providing post_type and expected count.
	 */
	public function should_return_correct_ticketed_count( Closure $fixture ): void {
		[ $post_type, $expected ] = $fixture();

		$query  = tribe( 'tickets.query' );
		$result = $query->get_ticketed_count( $post_type );

		$this->assertIsInt( $result );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider for should_return_correct_unticketed_count.
	 *
	 * @return Generator
	 */
	public function get_unticketed_count_provider(): Generator {
		yield 'no posts at all' => [
			function (): array {
				return [ 'post', 0 ];
			}
		];

		yield '3 unticketed posts' => [
			function (): array {
				static::factory()->post->create_many( 3 );

				return [ 'post', 3 ];
			}
		];

		yield '3 ticketed posts, none unticketed' => [
			function (): array {
				$ticketed = static::factory()->post->create_many( 3 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}

				return [ 'post', 0 ];
			}
		];

		yield '2 ticketed, 4 unticketed posts' => [
			function (): array {
				static::factory()->post->create_many( 4 );
				$ticketed = static::factory()->post->create_many( 2 );
				foreach ( $ticketed as $id ) {
					$this->create_tc_ticket( $id );
				}

				return [ 'post', 4 ];
			}
		];
	}

	/**
	 * It should return correct int count of unticketed posts, including 0 when none exist.
	 *
	 * @test
	 * @dataProvider get_unticketed_count_provider
	 *
	 * @param Closure $fixture Fixture providing post_type and expected count.
	 */
	public function should_return_correct_unticketed_count( Closure $fixture ): void {
		[ $post_type, $expected ] = $fixture();

		$query  = tribe( 'tickets.query' );
		$result = $query->get_unticketed_count( $post_type );

		$this->assertIsInt( $result );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * It should return zero and not fatal when ticketed count query fails.
	 *
	 * @test
	 */
	public function should_return_zero_and_not_fatal_when_ticketed_count_query_fails(): void {
		add_filter( 'tec_tickets_query_ticketed_count_query', static fn() => 'SELECT NULL' );

		$result = tribe( 'tickets.query' )->get_ticketed_count( 'post' );

		$this->assertSame( 0, $result );
	}

	/**
	 * It should return zero and not fatal when unticketed count query fails.
	 *
	 * @test
	 */
	public function should_return_zero_and_not_fatal_when_unticketed_count_query_fails(): void {
		add_filter( 'tec_tickets_query_unticketed_count_query', static fn() => 'SELECT NULL' );

		$result = tribe( 'tickets.query' )->get_unticketed_count( 'post' );

		$this->assertSame( 0, $result );
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
