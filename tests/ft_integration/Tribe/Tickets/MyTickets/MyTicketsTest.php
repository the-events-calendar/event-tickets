<?php

namespace Tribe\Tickets\MyTickets;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class MyTicketsTest extends WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;
	use Attendee_Maker;
	use Order_Maker;
	use Series_Pass_Factory;

	/**
	 * Setup a user to avoid 0 user ID.
	 *
	 * @before
	 */
	public function setup_preconditions(): void {
		// we should create and use a user to avoid 0 as user ID.
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
	}

	public function get_purchased_ticket_data(): Generator {
		yield 'post with no rsvp or tickets purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

				return [ $post_id, 'is_empty' => true ];
			}
		];

		yield 'post with 1 rsvp purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 3 rsvp purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				//create a rsvp ticket for that post.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 3 tickets purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 3,  $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 RSVP and 1 ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket..
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 3 RSRVP and 2 Ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 2 RSVP and 1 ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 1, $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'post with 1 RSVP and 2 ticket purchased' => [
			function (): array {
				$post_id = $this->factory()->post->create();
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
				$attendee       = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $post_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $post_id ];
			}
		];

		yield 'event with 1 RSVP and 2 ticket purchased' => [
			function (): array {
				$event_id = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
				] )->create()->ID;
				// create a tc ticket.
				$ticket_id = $this->create_tc_ticket( $event_id, 10 );
				$attendee  = $this->create_many_attendees_for_ticket( 2, $ticket_id, $event_id, [ 'user_id' => get_current_user_id(), ] );

				// create rsvp ticket.
				$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
				$attendee       = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $event_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $event_id ];
			}
		];

		yield 'event with 1 series pass and 1 ticket purchased' => [
			function(): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );

				$event_id = tribe_events()->set_args( [
					'title'      => 'Test event with a series pass and single ticket orders',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;

				$order = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_user_id' => get_current_user_id() ] );
				$order = $this->create_order( [ $series_pass_id => 1 ], [ 'purchaser_user_id' => get_current_user_id() ] );

				return [ $event_id ];
			}
		];

		yield 'event with 1 series pass and 1 ticket and 1 RSVP purchased' => [
			function(): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );

				$event_id = tribe_events()->set_args( [
					'title'      => 'Test event with a series pass and single ticket orders',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;
				$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );

				$order = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_user_id' => get_current_user_id() ] );
				$order = $this->create_order( [ $series_pass_id => 1 ], [ 'purchaser_user_id' => get_current_user_id() ] );
				$attendee = $this->create_many_attendees_for_ticket( 1, $rsvp_ticket_id, $event_id, [ 'user_id' => get_current_user_id(), ] );
				return [ $event_id ];
			}
		];

		yield 'event with multiple series pass, ticket and RSVPs purchased' => [
			function(): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );

				$event_id = tribe_events()->set_args( [
					'title'      => 'Test event with a series pass and single ticket orders',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;
				$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );

				$order = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_user_id' => get_current_user_id() ] );
				$order = $this->create_order( [ $series_pass_id => 2 ], [ 'purchaser_user_id' => get_current_user_id() ] );
				$attendee = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $event_id, [ 'user_id' => get_current_user_id(), ] );
				return [ $event_id ];
			}
		];

		yield 'series with 1 series pass purchased' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;
				$attendee  = $this->create_many_attendees_for_ticket( 1, $series_pass_id, $series_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $series_id ];
			}
		];

		yield 'series with multiple series pass purchased' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;
				$attendee  = $this->create_many_attendees_for_ticket( 2, $series_pass_id, $series_id, [ 'user_id' => get_current_user_id(), ] );

				return [ $series_id ];
			}
		];
	}

	/**
	 * Replace dynamic ids with placeholders to avoid snapshot collision.
	 *
	 * @param string $snapshot
	 * @param array $ids
	 *
	 * @return string
	 */
	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			array_values( $ids ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$snapshot
		);
	}

	/**
	 * @test
	 * @dataProvider get_purchased_ticket_data
	 */
	public function test_my_tickets_view_link( Closure $fixture ): void {
		$data     = $fixture();
		$post_id  = reset( $data );
		$is_empty = $data['is_empty'] ?? false;

		global $post;
		$post = get_post( $post_id );

		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( 'blocks/attendees/view-link', [ 'post_id' => $post->ID ], false );
		$html     = $this->placehold_post_ids( $html, [
			'post_id' => $post_id
		] );

		$is_empty ? $this->assertEmpty( $html ) : $this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 * @dataProvider get_purchased_ticket_data
	 */
	public function test_legacy_my_tickets_view_link( Closure $fixture ): void {
		$data     = $fixture();
		$post_id  = reset( $data );
		$is_empty = $data['is_empty'] ?? false;

		global $post;
		$post = get_post( $post_id );

		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template( '/tickets/view-link', [ 'post_id' => $post->ID ], false );
		$html     = $this->placehold_post_ids( $html, [
			'post_id' => $post_id
		] );

		$is_empty ? $this->assertEmpty( $html ) : $this->assertMatchesHtmlSnapshot( $html );
	}
}
