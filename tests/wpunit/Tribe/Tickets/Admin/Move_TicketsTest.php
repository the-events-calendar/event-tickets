<?php

namespace Tribe\Tickets\Admin;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use RuntimeException;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as Main;
use Tribe__Events__Main as TEC;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Commerce_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__RSVP__Attendance_Totals as Tickets_Attendance;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use TEC\Tickets\Admin\Attendees\Page as Attendees_Page;
use TEC\Tickets\Commerce\Module as Commerce;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use WP_Hook;
use WP_Screen;


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
					array_map( fn( int $id ) => get_post_field( 'post_title', $id ), $post_ids ),
					$post_ids
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
					array_map( fn( int $id ) => get_post_field( 'post_title', $id ), $post_ids_2 ),
					$post_ids_2
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
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, $event_ids ),
					$event_ids
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
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, $event_ids_2 ),
					$event_ids_2
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
					array_map( static function ( int $id ) {
						return get_post_field( 'post_title', $id ) . ' (' . tribe_get_start_date( $id ) . ')';
					}, [ $daily_event_occurrence_2, $daily_event_occurrence_3 ] ),
					[ $daily_event_occurrence_2, $daily_event_occurrence_3 ]
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

		// Sort expected by keys to match the alphabetical sorting in format_post_list.
		ksort( $expected );

		$this->assertEquals( $expected, $posts );
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
		// Set Tickets Commerce to enabled.
		$original_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=1' );
		
		// Verify Tickets Commerce is enabled.
		$this->assertTrue( tec_tickets_commerce_is_enabled(), 'Tickets Commerce should be enabled.' );
		
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

	/**
	 * @test
	 */
	public function it_should_render_the_dialog_with_a_resolved_admin_screen(): void {
		$event_id = $this->factory()->event->create();

		$get_backup         = $_GET;
		$hook_suffix_backup = $GLOBALS['hook_suffix'] ?? null;
		$_GET               = [
			'dialog'   => 'move_tickets',
			'check'    => wp_create_nonce( 'move_tickets' ),
			'event_id' => $event_id,
		];

		/* Isolate the hooks admin.php fires around its `$hook_suffix` assignment. */
		$GLOBALS['wp_filter']['admin_init']     = new WP_Hook();
		$GLOBALS['wp_filter']['current_screen'] = new WP_Hook();

		( new Move_Tickets() )->setup();

		$received = [];
		add_action(
			'admin_enqueue_scripts',
			static function ( $hook_suffix ) use ( &$received ) {
				$received[] = $hook_suffix;

				/* iframe_header() has been reached; stop before dialog() exits the process. */
				throw new RuntimeException( 'Dialog rendered.' );
			},
			1
		);

		/*
		 * admin.php dispatches `admin_init` while `$hook_suffix` is still null, assigns it, and only
		 * then calls set_current_screen(). Replay that order.
		 */
		$GLOBALS['hook_suffix'] = null;
		ob_start();

		try {
			do_action( 'admin_init' );
			$GLOBALS['hook_suffix'] = 'edit.php';
			set_current_screen();
		} catch ( RuntimeException $e ) {
			/* Expected: the listener above interrupts the dialog mid-render. */
		} finally {
			ob_end_clean();
			$_GET                   = $get_backup;
			$GLOBALS['hook_suffix'] = $hook_suffix_backup;
		}

		$this->assertSame( [ 'edit.php' ], $received );
	}

	/**
	 * @test
	 */
	public function it_should_offer_bulk_move_when_the_permission_depends_on_the_event(): void {
		$event_id = $this->factory()->event->create();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		remove_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );
		/* Like Community, grant attendee management per event and deny it when no event ID is given. */
		add_filter(
			'tribe_tickets_user_can_manage_attendees',
			static fn( $user_can, $user_id, $checked_event_id ) => $checked_event_id === $event_id ? $user_can : false,
			10,
			3
		);

		$actions = $this->get_bulk_actions( $this->get_event_attendees_page_request( $event_id ), WP_Screen::get( 'edit-' . TEC::POSTTYPE ) );

		$this->assertArrayHasKey( 'move', $actions );

		$other_event_id = $this->factory()->event->create();
		$other_actions  = $this->get_bulk_actions( $this->get_event_attendees_page_request( $other_event_id ), WP_Screen::get( 'edit-' . TEC::POSTTYPE ) );

		$this->assertArrayNotHasKey( 'move', $other_actions );
	}

	/**
	 * @test
	 */
	public function it_should_not_offer_bulk_move_to_a_user_who_cannot_manage_attendees(): void {
		$event_id = $this->factory()->event->create();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		remove_filter( 'tribe_tickets_user_can_manage_attendees', '__return_true' );

		$actions = $this->get_bulk_actions( $this->get_event_attendees_page_request( $event_id ), WP_Screen::get( 'edit-' . TEC::POSTTYPE ) );

		$this->assertArrayNotHasKey( 'move', $actions );
	}

	/**
	 * @test
	 */
	public function it_should_not_offer_bulk_move_on_the_all_attendees_page(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$actions = $this->get_bulk_actions( [ 'page' => Attendees_Page::$slug ], WP_Screen::get( Attendees_Page::$slug ) );

		$this->assertArrayNotHasKey( 'move', $actions );
	}

	/**
	 * Returns the request variables of a single event's attendees page.
	 *
	 * @param int $event_id The ID of the event whose attendees page is being viewed.
	 *
	 * @return array{post_type: string, page: string, event_id: int} The request variables.
	 */
	private function get_event_attendees_page_request( int $event_id ): array {
		return [
			'post_type' => TEC::POSTTYPE,
			'page'      => tribe( 'tickets.attendees' )->slug(),
			'event_id'  => $event_id,
		];
	}

	/**
	 * Returns the bulk actions Move_Tickets adds on the admin page described by the request and screen.
	 *
	 * @param array<string,int|string> $request The request variables of the page being viewed.
	 * @param WP_Screen                $screen  The screen of the page being viewed.
	 *
	 * @return array<string,string> The bulk actions, keyed by action slug.
	 */
	private function get_bulk_actions( array $request, WP_Screen $screen ): array {
		$get_backup    = $_GET;
		$screen_backup = $GLOBALS['current_screen'] ?? null;
		$admin_pages   = tribe( 'admin.pages' );

		$_GET                      = $request;
		$GLOBALS['current_screen'] = $screen;
		$admin_pages->determine_current_page();

		try {
			return ( new Move_Tickets() )->bulk_actions( [] );
		} finally {
			$_GET                      = $get_backup;
			$GLOBALS['current_screen'] = $screen_backup;
			$admin_pages->determine_current_page();
		}
	}
}
