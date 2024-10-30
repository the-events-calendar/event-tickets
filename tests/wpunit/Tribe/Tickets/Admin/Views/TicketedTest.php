<?php

namespace Tribe\Tickets\Admin\Views;

use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Admin__Views__Ticketed as Ticketed;
use Closure;
use Generator;
use Tribe__Events__Main as TEC;

class TicketedTest extends \Codeception\TestCase\WPTestCase {
	use MatchesSnapshots;
	use Ticket_Maker;

	public function ticketed_and_unticketed_counts_provider(): Generator {
		yield 'no posts' => [
			static function (): void {
			}
		];

		yield '3 unticketed posts' => [
			function (): void {
				$posts = $this->factory()->post->create_many( 3 );
			}
		];

		yield '3 ticketed posts' => [
			function (): void {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}
			}
		];

		yield '3 ticketed and 4 unticketed posts' => [
			function (): void {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}

				$this->factory()->post->create_many( 4 );
			}
		];

		yield 'auto-draft and trash posts' => [
			function (): void {
				// Create 2 published posts.
				$ticketed_published = $this->factory()->post->create_many( 2, [ 'post_status' => 'publish' ] );
				// Create 1 auto-draft post.
				$ticketed_auto_draft = $this->factory()->post->create( [ 'post_status' => 'auto-draft' ] );
				// Create 1 trash post.
				$ticketed_trashed = $this->factory()->post->create( [ 'post_status' => 'trash' ] );

				// Ticket each one of the above posts.
				foreach ( [ $ticketed_auto_draft, $ticketed_trashed, ...$ticketed_published ] as $post_id ) {
					$this->create_tc_ticket( $post_id );
				}

				// Create 2 published posts.
				$unticketed_published = $this->factory()->post->create_many( 2, [ 'post_status' => 'publish' ] );
				// Create 1 auto-draft post.
				$unticketed_auto_draft = $this->factory()->post->create( [ 'post_status' => 'auto-draft' ] );
				// Create 1 trash post.
				$unticketed_trashed = $this->factory()->post->create( [ 'post_status' => 'trash' ] );
			}
		];

		// This will return 3 Ticketed Events, 4 Unticketed Events.
		yield 'ticketed and unticketed events' => [
			function (): string {
				$factory = new Event();
				// Create 2 ticketed events in the future.
				foreach ( range( 1, 2 ) as $k ) {
					$ticketed_future_event = $factory->create( [
						'when'        => '2220-10-01 09:00:00',
						'post_status' => 'publish',
					] );
					$this->create_tc_ticket( $ticketed_future_event );
				}
				// Create 1 ticketed event in the past.
				$ticketed_past_event = $factory->create( [
					'when'        => '2000-10-01 09:00:00',
					'post_status' => 'publish',
				] );
				$this->create_tc_ticket( $ticketed_past_event );
				// Create a ticketed auto-draft event in the future.
				$ticketed_auto_draft_event = $factory->create( [
					'when'        => '2220-10-01 09:00:00',
					'post_status' => 'auto-draft',
				] );
				$this->create_tc_ticket( $ticketed_auto_draft_event );
				// Create a ticketed trash event in the future.
				$ticketed_trash_event = $factory->create( [
					'when'        => '2220-10-01 09:00:00',
					'post_status' => 'trash',
				] );
				$this->create_tc_ticket( $ticketed_trash_event );

				// Create 3 unticketed events in the future.
				foreach ( range( 1, 3 ) as $k ) {
					$factory->create( [
						'when'        => '2220-10-01 09:00:00',
						'post_status' => 'publish',
					] );
				}
				// Create 1 unticketed event in the past.
				$unticketed_past_event = $factory->create( [
					'when'        => '2000-10-01 09:00:00',
					'post_status' => 'publish',
				] );
				// Create an unticketed auto-draft event in the future.
				$unticketed_auto_draft_event = $factory->create( [
					'when'        => '2220-10-01 09:00:00',
					'post_status' => 'auto-draft',
				] );
				// Create an unticketed trash event in the future.
				$unticketed_trash_event = $factory->create( [
					'when'        => '2220-10-01 09:00:00',
					'post_status' => 'trash',
				] );

				return TEC::POSTTYPE;
			}
		];
	}

	/**
	 * It should correctly report ticketed and unticketed counts
	 *
	 * @test
	 * @dataProvider ticketed_and_unticketed_counts_provider
	 */
	public function should_correctly_report_ticketed_and_unticketed_counts( Closure $fixture ): void {
		$post_type = $fixture() ?? 'post';

		$ticketed = new Ticketed( $post_type );
		$filtered = $ticketed->filter_edit_link( [] );

		$this->assertMatchesSnapshot( $filtered );
	}
}
