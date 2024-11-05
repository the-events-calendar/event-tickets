<?php

namespace Tribe\Tickets\Admin;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as Main;
use Tribe__Events__Main as TEC;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__RSVP__Attendance_Totals as Tickets_Attendance;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;


class Move_TicketsTest extends WPTestCase {
	use With_Uopz;
	use RSVP_Ticket_Maker;
	use Commerce_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		add_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

		// Ensure Ticket Commerce is enabled.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			add_filter( 'tec_tickets_commerce_is_enabled', '__return_true', 100 );
			tribe()->register( Commerce_Provider::class );
			tribe( Commerce::class );
		}

		$path = Main::instance()->plugin_path;
		require_once $path . 'src/functions/commerce/orm.php';
		require_once $path . 'src/functions/commerce/orders.php';
		require_once $path . 'src/functions/commerce/attendees.php';
		require_once $path . 'src/functions/commerce/tickets.php';
	}


	/**
	 * @return \Tribe__Tickets__RSVP__Attendance_Totals
	 */
	private function make_instance( $event_id ) {

		return new Tickets_Attendance( $event_id );
	}

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

	/**
	 * @test
	 */
	public function it_should_count_total_rsvps_correctly() {
		// Create an event with 1 RSVP ticket and 20 attendees.
		$event_id  = $this->factory()->event->create();
		$rsvp_id   = $this->create_rsvp_ticket( $event_id );
		$attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );
		$ticket    = $this->make_instance( $event_id );
		$rsvps     = $ticket->get_total_rsvps();

		// Confirm the RSVP count is as expected.
		$this->assertEquals( count( $attendees ), $rsvps );
	}

	/**
	 * @test
	 */
	public function it_should_count_rsvps_correctly_after_moving_one() {
		// First event with 1 RSVP ticket and 1 attendee.
		$src_event_id   = $this->factory()->event->create();
		$src_rsvp_id    = $this->create_rsvp_ticket( $src_event_id );
		$src_attendee   = $this->create_attendee_for_ticket( $src_rsvp_id, $src_event_id );
		$src_attendance = $this->make_instance( $src_event_id );
		$src_rsvps      = $src_attendance->get_total_rsvps();

		// Second event with 1 RSVP ticket and 0 attendees.
		$trg_event_id   = $this->factory()->event->create();
		$trg_rsvp_id    = $this->create_rsvp_ticket( $trg_event_id );
		$trg_attendance = $this->make_instance( $trg_event_id );
		$trg_rsvps      = $trg_attendance->get_total_rsvps();

		// Confirm the RSVP counts are as expected.
		$this->assertEquals( 1, $src_rsvps );
		$this->assertEquals( 0, $trg_rsvps );

		// Move the attendee from the first event to the second event.
		$ticket_admin = new Move_Tickets();
		$ticket_admin->move_tickets( [ $src_attendee ], $trg_rsvp_id, $src_event_id, $trg_event_id );

		// Get the attendee counts after the move.
		$src_attendance = $this->make_instance( $src_event_id );
		$trg_attendance = $this->make_instance( $trg_event_id );
		$src_rsvps_after_move = $src_attendance->get_total_rsvps();
		$trg_rsvps_after_move = $trg_attendance->get_total_rsvps();

		// Confirm the RSVP counts are as expected after the move.
		$this->assertEquals( 0, $src_rsvps_after_move );
		$this->assertEquals( 1, $trg_rsvps_after_move );
	}

	/**
	 * @test
	 */
	public function it_should_count_rsvps_correctly_after_moving_many() {
		// First event with 1 RSVP ticket and 5 attendees.
		$src_event_id   = $this->factory()->event->create();
		$src_rsvp_id    = $this->create_rsvp_ticket( $src_event_id );
		$src_attendees  = $this->create_many_attendees_for_ticket( 5, $src_rsvp_id, $src_event_id );
		$src_attendance = $this->make_instance( $src_event_id );
		$src_rsvps      = $src_attendance->get_total_rsvps();

		// Second event with 1 RSVP ticket and 0 attendees.
		$trg_event_id   = $this->factory()->event->create();
		$trg_rsvp_id    = $this->create_rsvp_ticket( $trg_event_id );
		$trg_attendance = $this->make_instance( $trg_event_id );
		$trg_rsvps      = $trg_attendance->get_total_rsvps();

		// Confirm the RSVP counts are as expected.
		$this->assertEquals( 5, $src_rsvps );
		$this->assertEquals( 0, $trg_rsvps );

		// Move all the attendees from the first event to the second event.
		$ticket_admin = new Move_Tickets();
		$ticket_admin->move_tickets( $src_attendees, $trg_rsvp_id, $src_event_id, $trg_event_id );

		// Get the attendee counts after the move.
		$src_attendance = $this->make_instance( $src_event_id );
		$trg_attendance = $this->make_instance( $trg_event_id );
		$src_rsvps_after_move = $src_attendance->get_total_rsvps();
		$trg_rsvps_after_move = $trg_attendance->get_total_rsvps();

		// Confirm the RSVP counts are as expected after the move.
		$this->assertEquals( 0, $src_rsvps_after_move );
		$this->assertEquals( 5, $trg_rsvps_after_move );
	}


	/**
	 * @test
	 */
	public function it_should_count_tc_ticket_attendees_correctly() {
		// Create an event with 1 commerce ticket and 20 attendees.
		$event_id   = $this->factory()->event->create();
		$ticket_id  = $this->create_tc_ticket( $event_id );
		$attendees  = $this->create_many_attendees_for_ticket( 20, $ticket_id, $event_id );
		$attendance = tribe_attendees()->where( 'event', $event_id )->get_ids();

		// Confirm the attendee count is as expected.
		$this->assertEquals( count( $attendees ), count( $attendance ) );
	}

	/**
	 * @test
	 */
	public function it_should_count_tc_ticket_attendees_correctly_after_moving_one() {
		// First event with 1 commerce ticket and 1 attendee.
		$src_event_id   = $this->factory()->event->create();
		$src_ticket_id  = $this->create_tc_ticket( $src_event_id );
		$src_attendee   = $this->create_attendee_for_ticket( $src_ticket_id, $src_event_id );
		$src_attendance = tribe_attendees()->where( 'event', $src_event_id )->get_ids();

		// Second event with 1 commerce ticket and 0 attendees.
		$trg_event_id   = $this->factory()->event->create();
		$trg_ticket_id  = $this->create_tc_ticket( $trg_event_id );
		$trg_attendance = tribe_attendees()->where( 'event', $trg_event_id )->get_ids();

		// Confirm the attendee counts are as expected.
		$this->assertEquals( 1, count( $src_attendance ) );
		$this->assertEquals( 0, count( $trg_attendance ) );

		// Move the attendee from the first event to the second event.
		$ticket_admin = new Move_Tickets();
		$ticket_admin->move_tickets( [ $src_attendee ], $trg_ticket_id, $src_event_id, $trg_event_id );

		// Get the attendee counts after the move.
		$src_attendance = tribe_attendees()->where( 'event', $src_event_id )->get_ids();
		$trg_attendance = tribe_attendees()->where( 'event', $trg_event_id )->get_ids();

		// Confirm the attendee counts are as expected after the move.
		$this->assertEquals( 0, count( $src_attendance ) );
		$this->assertEquals( 1, count( $trg_attendance ) );
	}

	/**
	 * @test
	 */
	public function it_should_count_tc_ticket_attendees_correctly_after_moving_many() {
		// First event with 1 commerce ticket and 5 attendees.
		$src_event_id   = $this->factory()->event->create();
		$src_ticket_id  = $this->create_tc_ticket( $src_event_id );
		$src_attendees  = $this->create_many_attendees_for_ticket( 5, $src_ticket_id, $src_event_id );
		$src_attendance = tribe_attendees()->where( 'event', $src_event_id )->get_ids();

		// Second event with 1 commerce ticket and 0 attendees.
		$trg_event_id   = $this->factory()->event->create();
		$trg_ticket_id  = $this->create_tc_ticket( $trg_event_id );
		$trg_attendance = tribe_attendees()->where( 'event', $trg_event_id )->get_ids();

		// Confirm the attendee counts are as expected.
		$this->assertEquals( 5, count( $src_attendance ) );
		$this->assertEquals( 0, count( $trg_attendance ) );

		// Move all the attendees from the first event to the second event.
		$ticket_admin = new Move_Tickets();
		$ticket_admin->move_tickets( $src_attendees, $trg_ticket_id, $src_event_id, $trg_event_id );

		// Get the attendee counts after the move.
		$src_attendance = tribe_attendees()->where( 'event', $src_event_id )->get_ids();
		$trg_attendance = tribe_attendees()->where( 'event', $trg_event_id )->get_ids();

		// Confirm the attendee counts are as expected after the move.
		$this->assertEquals( 0, count( $src_attendance ) );
		$this->assertEquals( 5, count( $trg_attendance ) );
	}
}
