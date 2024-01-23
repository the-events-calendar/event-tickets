<?php

namespace Tribe\Tickets\Admin;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as Main;
use Tribe__Events__Main as TEC;

class Move_TicketsTest extends WPTestCase {
	use MatchesSnapshots;
	use With_Uopz;

	public function get_post_choices_provider(): Generator {
		yield 'looking for posts' => [
			function (): array {
				$post_ids = static::factory()->post->create_many( 3 );

				$ignore_id = array_shift( $post_ids );

				$_POST['check']     = '1234567890';
				$_POST['ignore']    = [ $ignore_id ];
				$_POST['post_type'] = 'post';

				return array_combine(
					$post_ids,
					array_map( fn( int $id ) => get_post_field( 'post_title', $id ), $post_ids )
				);
			}
		];

		yield 'looking for posts by string' => [
			function (): array {
				$post_ids_1 = static::factory()->post->create_many( 3, [ 'post_title' => 'Alice' ] );
				$post_ids_2 = static::factory()->post->create_many( 3, [ 'post_title' => 'Bob' ] );

				$ignore_id = array_shift( $post_ids_1 );

				$_POST['check']        = '1234567890';
				$_POST['ignore']       = [ $ignore_id ];
				$_POST['post_type']    = 'post';
				$_POST['search_terms'] = 'Bob';

				return array_combine(
					$post_ids_2,
					array_map( fn( int $id ) => get_post_field( 'post_title', $id ), $post_ids_2 )
				);
			}
		];

		yield 'looking for events' => [
			function (): array {
				$event_ids = array_map( function ( int $k ) {
					return tribe_events()->set_args( [
						'title'      => 'Event ' . $k,
						'status'     => 'publish',
						'start_date' => '2220-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					] )->create()->ID;
				}, range( 1, 3 ) );

				$ignore_id = array_shift( $event_ids );

				$_POST['check']     = '1234567890';
				$_POST['ignore']    = [ $ignore_id ];
				$_POST['post_type'] = TEC::POSTTYPE;

				return array_combine(
					$event_ids,
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, $event_ids )
				);
			}
		];

		yield 'looking for events by string' => [
			function (): array {
				$event_ids_1 = array_map( function ( int $k ) {
					return tribe_events()->set_args( [
						'title'      => 'Alice Event ' . $k,
						'status'     => 'publish',
						'start_date' => '2220-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					] )->create()->ID;
				}, range( 1, 3 ) );
				$event_ids_2 = array_map( function ( int $k ) {
					return tribe_events()->set_args( [
						'title'      => 'Bob Event ' . $k,
						'status'     => 'publish',
						'start_date' => '2220-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					] )->create()->ID;
				}, range( 1, 3 ) );

				$ignore_id = array_shift( $event_ids_1 );

				$_POST['check']        = '1234567890';
				$_POST['ignore']       = [ $ignore_id ];
				$_POST['post_type']    = TEC::POSTTYPE;
				$_POST['search_terms'] = 'Bob';

				return array_combine(
					$event_ids_2,
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, $event_ids_2 )
				);
			}
		];


		// ECP is not active: simulate recurring events by creating some events child of another event.
		yield 'legacy recurring events' => [
			function (): array {
				$daily_event_occurrence_1 = tribe_events()->set_args( [
					'title'      => 'Daily Event',
					'status'     => 'publish',
					'start_date' => '2220-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$daily_event_occurrence_2 = tribe_events()->set_args( [
					'title'      => 'Daily Event',
					'status'     => 'publish',
					'start_date' => '2220-01-02 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$daily_event_occurrence_3 = tribe_events()->set_args( [
					'title'      => 'Daily Event',
					'status'     => 'publish',
					'start_date' => '2220-01-03 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;

				$ignore_id = $daily_event_occurrence_1;

				$_POST['check']     = '1234567890';
				$_POST['ignore']    = [ $ignore_id ];
				$_POST['post_type'] = TEC::POSTTYPE;

				return array_combine(
					[ $daily_event_occurrence_2, $daily_event_occurrence_3 ],
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, [ $daily_event_occurrence_2, $daily_event_occurrence_3 ] )
				);
			}
		];
	}

	/**
	 * @dataProvider get_post_choices_provider
	 */
	public function test_get_post_choices( Closure $fixture ): void {
		$expected = $fixture();
		$this->set_fn_return( 'wp_verify_nonce', true );

		$move = Main::instance()->move_tickets();

		$posts = null;
		$this->set_fn_return( 'wp_send_json_success', function ( array $payload ) use ( &$posts ): void {
			$posts = $payload['posts'];
		}, true );
		$move->get_post_choices();

		$this->assertEqualSets( $expected, $posts );
	}
}
