<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use ActionScheduler_Action;
use ActionScheduler_DBStore;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Migration\Provider;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Event;
use TEC\Tickets\Flexible_Tickets\Base as Base_Controller;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Attendees_Table;
use Tribe__Tickets__Metabox as Metabox;
use WP_Post;
use WP_REST_Request;
use WP_REST_Server;
use Tribe__Tickets__Admin__Move_Tickets as Move_Attendees; // Legacy names ...

class AttendeesTest extends Controller_Test_Case {
	use Series_Pass_Factory;
	use Attendee_Maker;
	use Order_Maker;
	use SnapshotAssertions;
	use With_Uopz;

	protected string $controller_class = Attendees::class;
	private $rest_server_backup;

	/**
	 * @before
	 */
	public function backup_rest_server(): void {
		/* @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->rest_server_backup = $wp_rest_server instanceof WP_REST_Server ?
			clone $wp_rest_server
			: $wp_rest_server;
	}

	/**
	 * @after
	 */
	public function restore_rest_server(): void {
		global $wp_rest_server;
		$wp_rest_server = $this->rest_server_backup;
	}

	/**
	 * @before
	 */
	public function ensure_action_scheduler_initialized(): void {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		tribe( Provider::class )->load_action_scheduler_late();
	}

	/**
	 * It should filter Attendees Table columns correctly
	 *
	 * @test
	 */
	public function should_filter_attendees_table_columns_correctly(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create an Event part of the Series.
		$series_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '2021-01-01 10:00:00',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series,
			]
		)->create()->ID;
		// Create an Event NOT part of the Series.
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event',
				'status'     => 'publish',
				'start_date' => '2021-01-01 10:00:00',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$this->make_controller()->register();

		// Simulate a request to look at the Event not in the Series.
		$_GET['event_id'] = $event_id;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayHasKey( 'check_in', $attendee_table->get_table_columns() );

		// Simulate a request to look at the Event in the Series.
		$_GET['event_id'] = $series_event_id;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayHasKey( 'check_in', $attendee_table->get_table_columns() );

		// Simulate a request to look at the Event in the Series.
		$_GET['event_id'] = $series;
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$this->assertArrayNotHasKey( 'check_in', $attendee_table->get_table_columns() );
	}

	/**
	 * It should fail to check in pass Attendee from context of Event not part of Series
	 *
	 * @test
	 */
	public function should_fail_to_check_in_pass_attendee_from_context_of_event_not_part_of_series(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create an Event not part of the Series.
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$event_provisional_id = Occurrence::find( $event_id, 'post_id' )->provisional_id;
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $event_id ) );
		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $event_provisional_id ) );
	}

	/**
	 * It should fail to check in pass Attendee from context of not an Event
	 *
	 * @test
	 */
	public function should_fail_to_check_in_pass_attendee_from_context_of_not_an_event(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a post
		$post_id = static::factory()->post->create();
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( $commerce->checkin( $series_attendee_id, false, $post_id ) );
	}

	/**
	 * It should handle Series Pass Attendee checkin correctly
	 *
	 * @test
	 */
	public function should_handle_series_pass_attendee_checkin_correctly(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a past Event part of the Series.
		$past_series_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '-1 week',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Ticket and an Attendee for the past Event part of the Series.
		$past_series_event_ticket_id = $this->create_tc_ticket( $past_series_event_id );
		$past_series_event_attendee_id = $this->create_attendee_for_ticket( $past_series_event_ticket_id, $past_series_event_id );
		$past_series_event_provisional_id = Occurrence::find( $past_series_event_id, 'post_id' )->provisional_id;
		// Create a current Event part of the Series.
		$current_series_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Ticket and an Attendee for the current Event part of the Series.
		$current_series_event_ticket_id = $this->create_tc_ticket( $current_series_event_id );
		$current_series_event_attendee_id = $this->create_attendee_for_ticket( $current_series_event_ticket_id, $current_series_event_id );
		$current_series_event_provisional_id = Occurrence::find( $current_series_event_id, 'post_id' )->provisional_id;
		// Create a near feature Event part of the Series.
		$near_future_series_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Ticket and an Attendee for the near feature Event part of the Series.
		$near_feature_series_event_ticket_id = $this->create_tc_ticket( $near_future_series_event_id );
		$near_future_series_event_attendee_id = $this->create_attendee_for_ticket( $near_feature_series_event_ticket_id, $near_future_series_event_id );
		$near_future_series_provisional_id = Occurrence::find( $near_future_series_event_id, 'post_id' )->provisional_id;
		// Create a far feature Event part of the Series.
		$far_future_series_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '+3 days',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Ticket and an Attendee for the far feature Event part of the Series.
		$far_future_seriees_event_attendee_id = $this->create_tc_ticket( $far_future_series_event_id );
		$far_future_series_event_attendee_id = $this->create_attendee_for_ticket( $far_future_seriees_event_attendee_id, $far_future_series_event_id );
		$far_future_series_provisional_id = Occurrence::find( $far_future_series_event_id, 'post_id' )->provisional_id;
		// Create a Recurring Event part of the Series.
		$near_future_recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Fetch the provisional IDs for the 3 Occurrences part of the Recurring Event.
		$near_future_recurring_event_provisional_ids = Occurrence::where( 'post_id', $near_future_recurring_event_id )
			->order_by( 'start_date', 'ASC' )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$this->assertCount( 3, $near_future_recurring_event_provisional_ids );
		$this->assertEquals(
			$near_future_recurring_event_id,
			Occurrence::normalize_id( $near_future_recurring_event_provisional_ids[0] ),
			'The first Occurrence provisional ID should map to the Recurring Event post ID.'
		);
		// Create a second Recurring Event part of the Series. This one will have only one Occurrence in the time window.
		$one_occurrence_recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$one_occurrence_recurring_event_provisional_ids = Occurrence::where( 'post_id', $one_occurrence_recurring_event_id )
			->order_by( 'start_date', 'ASC' )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Create an Event NOT part of the Series.
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event',
				'status'     => 'publish',
				'start_date' => '2021-01-01 10:00:00',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Create a Single Ticket and an Attendee for the Event.
		$event_ticket_id = $this->create_tc_ticket( $event_id );
		$event_attendee_id = $this->create_attendee_for_ticket( $event_ticket_id, $event_id );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;
		$attendee_to_event_meta_key = Module::ATTENDEE_EVENT_KEY;
		$attendee_to_ticket_meta_key = Module::ATTENDEE_PRODUCT_KEY;
		// Set the checkin candidates time window to 36 hours.
		$time_buffer = 36 * HOUR_IN_SECONDS;
		add_filter( 'tec_tickets_flexible_tickets_series_checkin_time_buffer', static fn() => $time_buffer );

		// Verify that, to start with, all Attendees are not checked in.
		$this->assertEmpty( get_post_meta( $series_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $past_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $current_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $near_future_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $far_future_series_event_attendee_id, $checkin_key, true ) );
		$this->assertEmpty( get_post_meta( $event_attendee_id, $checkin_key, true ) );

		$controller = $this->make_controller();
		$controller->register();

		// Checkin of Attendees for default Tickets.
		$this->assertTrue(
			$commerce->checkin( $event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $event_attendee_id, $checkin_key, true ) );
		$this->assertTrue(
			$commerce->checkin( $past_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $past_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue(
			$commerce->checkin( $current_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $current_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue(
			$commerce->checkin( $near_future_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $near_future_series_event_attendee_id, $checkin_key, true ) );

		$this->assertTrue(
			$commerce->checkin( $far_future_series_event_attendee_id ),
			'Checkin of an Attendee for a default Event Ticket should happen without issues.'
		);
		$this->assertEquals( 1, get_post_meta( $far_future_series_event_attendee_id, $checkin_key, true ) );

		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			function ( $clone_id ) use ( &$cloned_attendee_id ) {
				$cloned_attendee_id = $clone_id;
			}
		);

		// Checking in a Series Pass Attendee without providing an Event ID should fail.
		$cloned_attendee_id = null;
		$this->assertFalse(
			$commerce->checkin( $series_attendee_id, false ),
			'The check in of the Series Pass Attendee without specifying an Event ID should fail.'
		);
		$this->assertNull(
			$cloned_attendee_id,
			'The Series Pass Attendee should not have been cloned.'
		);
		$this->assertEquals(
			'',
			get_post_meta( $series_attendee_id, $checkin_key, true ),
			'The original Series Pass Attendee should not be checked in.'
		);

		// Let's make sure the checkin candidates are correct.
		$this->assertEqualSets(
			[
				$current_series_event_provisional_id,
				$near_future_series_provisional_id,
				$near_future_recurring_event_provisional_ids[0],
				$near_future_recurring_event_provisional_ids[1],
				$one_occurrence_recurring_event_provisional_ids[0],
			],
			$controller->fetch_checkin_candidates_for_series( $series_attendee_id, $series_id, true ),
			'The checkin candidates for the Series Pass Attendee should be the current Event, the near future Event and the Recurring Event.'
		);

		// Checking in the Series Pass Attendee from any of the Events part of the Series should succeed and clone the Attendee.
		// The cloned Attendee should be related to the provisional ID, not the Event post ID.
		// The assertions are using a mix of real Event post IDs and Occurrence provisional IDs to make sure both work.
		foreach (
			[
				'past_series_event_id'                => [
					$past_series_event_id,
					$past_series_event_id,
				],
				'current_series_event_provisional_id' => [
					$current_series_event_provisional_id,
					$current_series_event_id,
				],
				'near_future_series_event_id'         => [
					$near_future_series_event_id,
					$near_future_series_event_id,
				],
				'far_future_series_provisional_id'    => [
					$far_future_series_provisional_id,
					$far_future_series_event_id,
				],
			] as $set => [$event_id, $attendee_target_id]
		) {
			$cloned_attendee_id = null;
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
			);
			$this->assertNotNull(
				$cloned_attendee_id,
				"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
			);
			$this->assertEquals(
				'',
				get_post_meta( $series_attendee_id, $checkin_key, true ),
				"The original Series Pass Attendee should not be checked in. | {$set}"
			);
			$this->assertEquals(
				$attendee_target_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
				"The cloned Attendee should be related to the Event provisional ID | {$set}"
			);
			$this->assertEquals(
				$series_pass_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
				"The cloned Attendee should be related to the Series Pass | {$set}"
			);
			$this->assertEquals(
				'1',
				get_post_meta( $cloned_attendee_id, $checkin_key, true ),
				"The cloned Attendee should be checked in. | {$set}"
			);
		}

		$this->assertFalse(
			$commerce->checkin( $series_attendee_id, false, $near_future_recurring_event_id ),
			'The check in of the Series Pass Attendee from a Recurring Event ID should fail if there are multiple Occurrences in the time window.'
		);

		// Checking in Series Pass Attendees from Recurring Event part of the Series by means of real of Provisional ID
		// should succeed. Use the actual Recurring Event ID to check in the Attendee for the first Occurrence.
		$this->assertEqualSets(
			[
				$near_future_recurring_event_provisional_ids[0],
				$near_future_recurring_event_provisional_ids[1],
			],
			$controller->fetch_checkin_candidates_for_event( $series_attendee_id, $near_future_recurring_event_id, true ),
			'The checking candidates for the near future Recurring Event should be the first and second Occurrence.'
		);
		foreach (
			[
				'near_future_recurring_event_id, 1st occ.' => $near_future_recurring_event_provisional_ids[0],
				'near_future_recurring_event_id, 2nd occ.' => $near_future_recurring_event_provisional_ids[1],
				'near_future_recurring_event_id, 3rd occ.' => $near_future_recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$attendee_target_id = $event_id;
			$cloned_attendee_id = null;
			$condition = $commerce->checkin( $series_attendee_id, false, $event_id );
			$this->assertTrue(
				$condition,
				"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
			);
			$this->assertNotNull(
				$cloned_attendee_id,
				"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
			);
			$this->assertEquals(
				'',
				get_post_meta( $series_attendee_id, $checkin_key, true ),
				"The original Series Pass Attendee should not be checked in. | {$set}"
			);
			$this->assertEquals(
				$attendee_target_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
				"The cloned Attendee should be related to the Event provisional ID | {$set}"
			);
			$this->assertEquals(
				$series_pass_id,
				get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
				"The cloned Attendee should be related to the Series Pass | {$set}"
			);
			$this->assertEquals(
				'1',
				get_post_meta( $cloned_attendee_id, $checkin_key, true ),
				"The cloned Attendee should be checked in. | {$set}"
			);
		}

		// Checking in Series Pass Attendees from the Event ID of the Recurring Event part of the Series that only
		// has one Occurrence in the time window should succeed.
		$set = 'one_occurrence_recurring_event_id';
		// The Attendee should be checked into the first Occurrence.
		$attendee_target_id = $one_occurrence_recurring_event_provisional_ids[0];
		$cloned_attendee_id = null;
		$this->assertTrue(
			$commerce->checkin( $series_attendee_id, true, $one_occurrence_recurring_event_id ),
			"The check in of the Series Pass Attendee from an Event part of the Series should be successful. | {$set}"
		);
		$this->assertNotNull(
			$cloned_attendee_id,
			"On checkin, the Series Pass Attendee should have been cloned. | {$set}"
		);
		$this->assertEquals(
			'',
			get_post_meta( $series_attendee_id, $checkin_key, true ),
			"The original Series Pass Attendee should not be checked in. | {$set}"
		);
		$this->assertEquals(
			$attendee_target_id,
			get_post_meta( $cloned_attendee_id, $attendee_to_event_meta_key, true ),
			"The cloned Attendee should be related to the Event provisional ID | {$set}"
		);
		$this->assertEquals(
			$series_pass_id,
			get_post_meta( $cloned_attendee_id, $attendee_to_ticket_meta_key, true ),
			"The cloned Attendee should be related to the Series Pass | {$set}"
		);
		$this->assertEquals(
			'1',
			get_post_meta( $cloned_attendee_id, $checkin_key, true ),
			"The cloned Attendee should be checked in. | {$set}"
		);

		// Checking in Series Pass Attendees a second time should be handled by default logic and not clone again.
		// Use the provisional ID for one to make sure it works.
		foreach (
			[
				Occurrence::find( $past_series_event_id, 'post_id' )->provisional_id,
				$current_series_event_id,
				$near_future_series_event_id,
				$far_future_series_event_id,
			] as $event_id
		) {
			$cloned_attendee_id = null;
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				'A 2nd checkin of the Series Pass Attendee from an Event part of the Series should be successful.'
			);
			$this->assertNull(
				$cloned_attendee_id,
				'On a 2nd checkin, the Series Pass Attendee should not have been cloned again.'
			);
			$this->assertEquals(
				'',
				get_post_meta( $series_attendee_id, $checkin_key, true ),
				'On a 2nd checkin, the original Series Pass Attendee should not be checked in.'
			);
		}
	}

	/**
	 * It should correctly handle REST check-in requests when check-in is not restricted
	 *
	 * @test
	 */
	public function should_correctly_handle_rest_request_to_checkin_with_qr(): void {
		// Ensure check-in is not restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', false );
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Event 1',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Event 2',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Event 3',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Ensure Tickets REST routes will register.
		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		// Set the time buffer to 6 hours.
		$time_buffer = 6 * HOUR_IN_SECONDS;
		add_filter(
			'tec_tickets_flexible_tickets_series_checkin_time_buffer',
			static function () use ( &$time_buffer ) {
				return $time_buffer;
			}
		);

		// Try and scan in a Series pass Attendee from the context of a post.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', static::factory()->post->create() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			403,
			$response->status,
			'Trying to check in a Series Pass Attendee from the context of a post that is not an Event should fail.'
		);

		// Trying to scan in a Series pass Attendee without providing an Event ID should fail if there are multiple candidates.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			300,
			$response->status,
			'Trying to check in a Series Pass Attendee with multiple candidate Events should fail and require a choice.'
		);
		$this->assertArrayHasKey( 'attendee_id', $response->data );
		$this->assertEquals( $series_attendee_id, $response->data['attendee_id'] );
		$this->assertArrayHasKey( 'candidates', $response->data );
		$this->assertEqualSets(
			[
				Occurrence::find( $event_1, 'post_id' )->provisional_id,
				Occurrence::find( $event_2, 'post_id' )->provisional_id,
				Occurrence::find( $event_3, 'post_id' )->provisional_id,
				$recurring_event_provisional_ids[0],
			],
			array_map(
				static fn( array $candidate ) => $candidate['id'],
				$response->data['candidates']
			)
		);

		// Set the time buffer to 1.5 hours.
		$time_buffer = 1.5 * HOUR_IN_SECONDS;

		// Trying to scan in a Series pass Attendee without providing an Event ID should succeed if there is only one
		// candidate in the checkin timeframe of 1.5 hours.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			201,
			$response->status,
			'Trying to check in a Series Pass Attendee with only one candidate Event should succeed.'
		);

		// Trying to scan in a Series Pass Attendee providing the ID of an Event outside of the current timeframe
		// of 1.5 hours that only has one candidate should succeed.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_3 );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			403,
			$response->status,
			'Trying to check in a Series Pass Attendee with an Event ID outside of the current timeframe should fail.'
		);

		// Trying to scan in a Series Pass Attendee providing the ID of a Recurring Event with no Occurrences in the
		// current timeframe of 1.5 hours should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->status );

		// Set the time buffer to 36 hours.
		$time_buffer = 36 * HOUR_IN_SECONDS;

		// Trying to scan in a Series Pass Attendee providing the ID of a Recurring Event with multiple Occurrences
		// in the current timeframe of 36 hours should fail and require to pick one.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 300, $response->status );

		// Trying to scan in a Series Pass Attendee providing the Provisional ID of an Occurrence outside time frame
		// of 36 hours should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $recurring_event_provisional_ids[2] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->status );
	}

	/**
	 * It should correctly handle check-in request with Series ID context
	 *
	 * @test
	 */
	public function should_correctly_handle_check_in_request_with_series_id_context(): void {
		// Ensure check-in is not restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', false );
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create 2 Single Events part of the Series, both happening in the next 3 hours.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Event #1',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Event #2',
				'status'     => 'publish',
				'start_date' => '+2 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		// Set the time buffer to 6 hours.
		$time_buffer = 6 * HOUR_IN_SECONDS;
		add_filter(
			'tec_tickets_flexible_tickets_series_checkin_time_buffer',
			static function () use ( &$time_buffer ) {
				return $time_buffer;
			}
		);

		// Checking in a Series Pass Attendee providing the Series ID as context should require choosing an Event.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $series_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 300, $response->status );
	}

	/**
	 * It should correctly handle REST check-in requests when check-in is restricted
	 *
	 * @test
	 */
	public function should_correctly_handle_rest_check_in_requests_when_check_in_is_restricted(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 6 hours.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 6 * HOUR_IN_SECONDS );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Event 1',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Event 2',
				'status'     => 'publish',
				'start_date' => '+12 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Event 3',
				'status'     => 'publish',
				'start_date' => '+36 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Ensure Tickets REST routes will register.
		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_attendee_id );
		$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals(
			300,
			$response->status,
			'A request to check-in a Series Pass Attendee for a series with multiple candidates should fail and require a choice.'
		);

		foreach (
			[
				'event_1'                  => [ $event_1, 201 ],
				'event_2'                  => [ $event_2, 403 ],
				'event_3'                  => [ $event_3, 403 ],
				'recurring_event 1st occ.' => [ $recurring_event_provisional_ids[0], 201 ],
				'recurring_event 2nd occ.' => [ $recurring_event_provisional_ids[1], 403 ],
				'recurring_event 3rd occ.' => [ $recurring_event_provisional_ids[2], 403 ],
			] as $set => [$event_id, $expected_status]
		) {
			$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
			$request->set_param( 'api_key', $api_key );
			$request->set_param( 'ticket_id', (string) $series_attendee_id );
			$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
			$request->set_param( 'event_id', $event_id );

			$response = rest_get_server()->dispatch( $request );

			$this->assertEquals( $expected_status, $response->status, "Set: {$set}" );
		}
	}

	/**
	 * It should allow manual check-in of Series Pass Attendees from Events always
	 *
	 * @test
	 */
	public function should_allow_manual_check_in_of_series_pass_attendees_from_events_always(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 0 seconds. This prevents any QR check-in from succeeding.
		// But it should never prevent manual check-ins from succeeding.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 0 );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Event 1',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Event 2',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Event 3',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		foreach (
			[
				'event_1'                  => $event_1,
				'event_2'                  => $event_2,
				'event_3'                  => $event_3,
				'recurring_event 1st occ.' => $recurring_event_provisional_ids[0],
				'recurring_event 2nd occ.' => $recurring_event_provisional_ids[1],
				'recurring_event 3rd occ.' => $recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$this->assertTrue(
				$commerce->checkin( $series_attendee_id, false, $event_id ),
				"Manual checkin of a Series Pass Attendee should succeed even when the restricted check-in time is 0. | {$set}"
			);
		}
	}

	/**
	 * It should not allow any QR check-in when the restricted check-in time is 0
	 *
	 * @test
	 */
	public function should_not_allow_any_qr_check_in_when_the_restricted_check_in_time_is_0(): void {
		// Ensure check-in is restricted.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		// And that the check-in time window is 6 hours.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 6 * HOUR_IN_SECONDS );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create three current and upcoming events the Series Pass Attendee might check into.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Event 1',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Event 2',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Event 3',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the series.
		$recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'status'     => 'publish',
				'start_date' => '+4 hours',
				'duration'   => HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$recurring_event_provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse(
			$commerce->checkin( $series_attendee_id ),
			'Checkin of a Series Pass Attendee should fail when the restricted check-in time is 0.'
		);
		foreach (
			[
				'event_1'                  => $event_1,
				'event_2'                  => $event_2,
				'event_3'                  => $event_3,
				'recurring_event 1st occ.' => $recurring_event_provisional_ids[0],
				'recurring_event 2nd occ.' => $recurring_event_provisional_ids[1],
				'recurring_event 3rd occ.' => $recurring_event_provisional_ids[2],
			] as $set => $event_id
		) {
			$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
			$request->set_param( 'ticket_id', (string) $series_attendee_id );
			$request->set_param( 'security_code', get_post_meta( $series_attendee_id, $commerce->security_code, true ) );
			$request->set_param( 'event_id', $event_id );

			$response = rest_get_server()->dispatch( $request );

			$this->assertEquals(
				400,
				$response->status,
				"Checkin of a Series Pass Attendee should fail when the restricted check-in time is 0. | {$set}"
			);
		}
	}

	/**
	 * It should update cloned Attendee when original updated
	 *
	 * @test
	 */
	public function should_update_cloned_attendee_when_original_updated(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$original = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create an Event part of the Series.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Test Event #1',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event Occurrence.
		$clone_1 = $controller->clone_attendee_to_event( $original, $event_1 );
		// Create a second Event part of the Series.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Test Event #2',
				'status'     => 'publish',
				'start_date' => '+27 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event Occurrence.
		$clone_2 = $controller->clone_attendee_to_event( $original, $event_2 );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;

		$controller->register();

		$this->assertEquals(
			'',
			get_post_meta( $original, $checkin_key, true ),
			'The original Attendee should not be checked in at the start'
		);
		$this->assertEquals(
			'',
			get_post_meta( $clone_1, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start'
		);
		$this->assertEquals(
			'',
			get_post_meta( $clone_2, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start'
		);

		// Update the original: 1st and 2nd clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Bob',
				'post_excerpt' => 'That famous Bob from the movie no one saw',
				'post_parent'  => 2389, // Not really an existing post.
				'post_status'  => 'private',
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post(
				[
					'ID'        => $original,
					$post_field => $updated_post_field_value,
				]
			);

			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following an original Attendee post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following an original Attendee post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following an original Attendee post field update.'
			);
		}

		/*
		 * Note, the original Series Attendee cannot be checked in using the UI or the App (i.e. QR code)
		 * Here we trigger a low-level check-in/out flow based on meta functions to make sure the controller will
		 * handle them.
		 */
		update_post_meta( $original, $checkin_key, '1' );
		update_post_meta( $original, $checkin_key . '_details', 'some-details' );
		$this->assertEquals( '1', get_post_meta( $original, $checkin_key, true ) );
		$this->assertEquals( 'some-details', get_post_meta( $original, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key . '_details', true ) );

		// Adding a meta value to the original should add it to the clones.
		add_post_meta( $original, 'some_test_key', 23 );
		$this->assertEquals( 23, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating a meta value on the original should update the clones meta values.
		update_post_meta( $original, 'some_test_key', 89 );
		$this->assertEquals( 89, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Removing a meta value from the original should remove it from the clones.
		delete_post_meta( $original, 'some_test_key' );
		$this->assertEquals( '', get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, 'some_test_key', true ) );

		// Trashing the original Attendee should trash the posts.
		wp_trash_post( $original );
		$this->assertEquals( 'trash', get_post_status( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertEquals( 'trash', get_post_status( $clone_2 ) );

		// Deleting the original Attendee should trigger the deletion of the clones.
		wp_delete_post( $original, true );
		$this->assertNull( get_post( $original ) );
		$this->assertNull( get_post( $clone_1 ) );
		$this->assertNull( get_post( $clone_2 ) );
	}

	/**
	 * It should update original Attendee when cloned Attendee updated
	 *
	 * @test
	 */
	public function should_update_original_attendee_when_cloned_attendee_updated(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$original = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create an Event part of the Series.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Test Event #1',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event.
		$clone_1 = $controller->clone_attendee_to_event( $original, $event_1 );
		// Create a second Event part of the Series.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Test Event #2',
				'status'     => 'publish',
				'start_date' => '+27 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to the Event.
		$clone_2 = $controller->clone_attendee_to_event( $original, $event_2 );
		$commerce = Module::get_instance();
		$checkin_key = $commerce->checkin_key;

		$controller->register();

		$this->assertEquals(
			'',
			get_post_meta( $original, $checkin_key, true ),
			'The original Attendee should not be checked in at the start'
		);
		$this->assertEquals(
			'',
			get_post_meta( $clone_1, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start'
		);
		$this->assertEquals(
			'',
			get_post_meta( $clone_2, $checkin_key, true ),
			'The cloned Attendee should not be checked in at the start'
		);

		// Update the 1st clone: original and 2nd clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Bob',
				'post_excerpt' => 'That famous Bob from the movie no one saw',
				'post_parent'  => 2389, // Not really an existing post.
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post(
				[
					'ID'        => $clone_1,
					$post_field => $updated_post_field_value,
				]
			);

			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following an original Attendee post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following an original Attendee post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following an original Attendee post field update.'
			);
		}

		// Update the 2nd clone: original and 1st clone should be updated.
		foreach (
			[
				'post_title'   => 'Famous Alice',
				'post_excerpt' => 'That famous Alice from the movie everyone saw',
				'post_parent'  => 23892389, // Not really an existing post.
			] as $post_field => $updated_post_field_value
		) {
			wp_update_post(
				[
					'ID'        => $clone_2,
					$post_field => $updated_post_field_value,
				]
			);

			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $original ),
				'The original Attendee post field should have been updated following the 2nd Attendee clone post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_1 ),
				'The 1st cloned Attendee post field should have been updated following the 2nd Attendee clone post field update.'
			);
			$this->assertEquals(
				$updated_post_field_value,
				get_post_field( $post_field, $clone_2 ),
				'The 2nd cloned Attendee post field should have been updated following the 2nd Attendee clone post field update.'
			);
		}

		// Adding a meta value to the 2nd clone should add it to the original and 1st clone.
		add_post_meta( $clone_2, 'some_test_key', 23 );
		$this->assertEquals( 23, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 23, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating a meta value on the 1st clone should update the original and 2nd clone meta_value
		update_post_meta( $clone_1, 'some_test_key', 89 );
		$this->assertEquals( 89, get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( 89, get_post_meta( $clone_2, 'some_test_key', true ) );

		// Removing a meta value from the 2nd clone should remove it from the original and 2nd clone.
		delete_post_meta( $clone_2, 'some_test_key' );
		$this->assertEquals( '', get_post_meta( $original, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_1, 'some_test_key', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, 'some_test_key', true ) );

		// Updating or removing the check-in, or details, meta_key of a clone should not affect any other Attendee.
		update_post_meta( $clone_1, $checkin_key, '1' );
		update_post_meta( $clone_1, $checkin_key . '_details', 'some-details' );
		$this->assertEquals( '', get_post_meta( $original, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $original, $checkin_key . '_details', true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key, true ) );
		$this->assertEquals( '', get_post_meta( $clone_2, $checkin_key . '_details', true ) );

		// Trashing the 1st clone should trash the original or the 2nd clone.
		wp_trash_post( $clone_1 );
		$this->assertEquals( 'publish', get_post_status( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertEquals( 'publish', get_post_status( $clone_2 ) );

		// Deleting the 2nd clone should not affect the original or the 1st clone.
		wp_delete_post( $clone_2, true );
		$this->assertInstanceOf( WP_Post::class, get_post( $original ) );
		$this->assertEquals( 'trash', get_post_status( $clone_1 ) );
		$this->assertNull( get_post( $clone_2 ) );
	}

	/**
	 * It should update linked meta values of Series Pass cloned Attendees when Provisional ID base updated
	 *
	 * @test
	 */
	public function should_update_linked_meta_values_of_series_pass_cloned_attendees_when_provisional_id_base_updated(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Update the provisional ID base a first time to set the option initial value.
		$id_generator = tribe( ID_Generator::class );
		$id_generator->update();
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$original = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create a REcurring Event part of the Series happening 10 times.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Test Event #1',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=10',
				'series'     => $series_id,
			]
		)->create()->ID;
		$controller = $this->make_controller();
		// Clone the Attendee to each Event Occurrence.
		$provisional_ids = Occurrence::where( 'post_id', '=', $event_1 )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$clones = [];
		foreach ( $provisional_ids as $provisional_id ) {
			$clones[ $provisional_id ] = $controller->clone_attendee_to_event( $original, $provisional_id );
		}
		$commerce = Module::get_instance();
		$attendee_event_key = $commerce->attendee_event_key;

		// To start, the cloned Attendees should be related to the Occurrence provisional IDs of the Events.
		foreach ( $provisional_ids as $provisional_id ) {
			$this->assertEquals(
				$provisional_id,
				get_post_meta( $clones[ $provisional_id ], $attendee_event_key, true )
			);
		}

		$old_base = $id_generator->current();
		// Filter the update batch size to set it to 3.
		add_filter( 'tec_tickets_flexible_tickets_attendee_event_value_update_batch_size', static fn() => 3 );
		// Update the Provisional Post base, this could happen if the real post IDs got too close to the provisional IDs.
		// This will enqueue an Action Scheduler action to update the Attendees.
		$new_base_diff = 30000000;
		$new_base = $old_base + $new_base_diff;
		add_filter( 'tec_events_pro_custom_tables_v1_provisional_post_base_initial', static fn() => $new_base_diff );
		$id_generator->update();
		$this->assertEquals( $new_base, $id_generator->current() );

		// There should be a first action to process Attendees.
		$this->assertTrue( as_has_scheduled_action( Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION ) );
		$actions = as_get_scheduled_actions(
			[
				'hook' => Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			]
		);
		$this->assertCount( 1, $actions );
		/** @var ActionScheduler_Action $action */
		$action = reset( $actions );
		$action_id = ( array_keys( $actions ) )[0];

		// Execute the Action Scheduler action a first time like Action Scheduler would.
		$action->execute();
		// Delete the executed action like Action Scheduler would do.
		ActionScheduler_DBStore::instance()->delete_action( $action_id );

		// There should be a second action to process Attendees.
		$this->assertTrue( as_has_scheduled_action( Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION ) );
		$actions = as_get_scheduled_actions(
			[
				'hook' => Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			]
		);
		$this->assertCount( 1, $actions );
		/** @var ActionScheduler_Action $action */
		$action = reset( $actions );
		$action_id = ( array_keys( $actions ) )[0];

		// Execute the Action Scheduler action a second time like Action Scheduler would.
		$action->execute();
		// Delete the executed action like Action Scheduler would do.
		ActionScheduler_DBStore::instance()->delete_action( $action_id );

		// There should be a third action to process Attendees.
		$this->assertTrue( as_has_scheduled_action( Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION ) );
		$actions = as_get_scheduled_actions(
			[
				'hook' => Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			]
		);
		$this->assertCount( 1, $actions );
		/** @var ActionScheduler_Action $action */
		$action = reset( $actions );
		$action_id = ( array_keys( $actions ) )[0];

		// Execute the Action Scheduler action a third time like Action Scheduler would.
		$action->execute();
		// Delete the executed action like Action Scheduler would do.
		ActionScheduler_DBStore::instance()->delete_action( $action_id );

		// There should be a fourth and last action to process Attendees.
		$this->assertTrue( as_has_scheduled_action( Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION ) );
		$actions = as_get_scheduled_actions(
			[
				'hook' => Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION,
			]
		);
		$this->assertCount( 1, $actions );
		/** @var ActionScheduler_Action $action */
		$action = reset( $actions );
		$action_id = ( array_keys( $actions ) )[0];

		// Execute the Action Scheduler action a fourth time like Action Scheduler would.
		$action->execute();
		// Delete the executed action like Action Scheduler would do.
		ActionScheduler_DBStore::instance()->delete_action( $action_id );

		// There should be a fourth and last action to process Attendees.
		$this->assertFalse( as_has_scheduled_action( Base_Controller::AS_ATTENDEE_EVENT_VALUE_UPDATE_ACTION ) );

		$diff = $new_base - $old_base;
		foreach ( $provisional_ids as $old_provisional_id ) {
			$new_provisional_id = $old_provisional_id + $diff;
			$attendee_id = $clones[ $old_provisional_id ];
			$this->assertEquals(
				$new_provisional_id,
				get_post_meta( $attendee_id, $attendee_event_key, true )
			);
		}
	}

	/**
	 * It should add Series ID to Event IDs when fetching Attendees of Event in Series
	 *
	 * @test
	 */
	public function should_add_series_id_to_event_i_ds_when_fetching_attendees_of_event_in_series(): void {
		$series = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		$event_in_series = tribe_events()->set_args(
			[
				'title'      => 'Event in Series',
				'status'     => 'publish',
				'start_date' => '2020-02-11 17:30:00',
				'end_date'   => '2020-02-11 18:00:00',
				'series'     => $series,
			]
		)->create()->ID;
		$event_not_in_series = tribe_events()->set_args(
			[
				'title'      => 'Event not in Series',
				'status'     => 'publish',
				'start_date' => '2020-02-11 17:30:00',
				'end_date'   => '2020-02-11 18:00:00',
			]
		)->create()->ID;
		$series_pass = $this->create_tc_series_pass( $series, 66 )->ID;
		$ticket_1 = $this->create_tc_ticket( $event_in_series, 23 );
		$ticket_2 = $this->create_tc_ticket( $event_not_in_series, 89 );
		[ $attendee_1, $attendee_2 ] = $this->create_many_attendees_for_ticket( 2, $ticket_1, $event_in_series );
		[ $attendee_3, $attendee_4 ] = $this->create_many_attendees_for_ticket( 2, $ticket_2, $event_not_in_series );
		[
			$pass_attendee_1,
			$pass_attendee_2,
		] = $this->create_many_attendees_for_ticket( 2, $series_pass, $series );

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2 ],
			tribe_attendees()->where( 'event', $event_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event', $event_not_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event', $series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_3, $attendee_4, $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event__not_in', $event_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event__not_in', $event_not_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event__not_in', $series )->get_ids()
		);

		// Build and register the controller.
		$controller = $this->make_controller()->register();

		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event', $event_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event', $event_not_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event', $series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event__not_in', $event_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $pass_attendee_1, $pass_attendee_2 ],
			tribe_attendees()->where( 'event__not_in', $event_not_in_series )->get_ids()
		);
		$this->assertEqualSets(
			[ $attendee_1, $attendee_2, $attendee_3, $attendee_4 ],
			tribe_attendees()->where( 'event__not_in', $series )->get_ids()
		);
	}

	/**
	 * It should correctly filter the JS Attendee report configuration
	 *
	 * @test
	 */
	public function should_correctly_filter_the_js_attendee_report_configuration(): void {
		$this->make_controller()->register();

		$filtered = apply_filters( 'tribe_tickets_attendees_report_js_config', [] );

		$this->assertMatchesJsonSnapshot( json_encode( $filtered, JSON_PRETTY_PRINT ) );
	}


	/**
	 * It should query for attendees correctly when there are cloned Attendees
	 *
	 * @test
	 */
	public function should_query_for_attendees_correctly_when_there_are_cloned_attendees(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Update the provisional ID base a first time to set the option initial value.
		$id_generator = tribe( ID_Generator::class );
		$id_generator->update();
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee_id = tribe_attendees()->where( 'event_id', $series_id )->first_id();
		// Create a Recurring Event part of the Series happening 10 times.
		$recurring_event = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event in Series',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=10',
				'series'     => $series_id,
			]
		)->create()->ID;
		$provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		$this->assertCount( 10, $provisional_ids );
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Single Event in Series',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a control Single Event not part of the Series.
		$control_event = tribe_events()->set_args(
			[
				'title'      => 'Control Event',
				'status'     => 'publish',
				'start_date' => '+3 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Create 2 Attendees for the control event not part of the Series.
		$control_ticket_id = $this->create_tc_ticket( $control_event );
		$this->create_order( [ $control_ticket_id => 2 ] );
		$controls_attendees = tribe_attendees()->where( 'event', $control_event )
			->get_ids();
		$this->assertCount( 2, $controls_attendees );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The original Attendee should be the only Series Attendee.'
		);
		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $recurring_event )->get_ids(),
			'The original Attendee should be the only Recurring Event Attendee.'
		);
		foreach ( $provisional_ids as $provisional_id ) {
			$this->assertEquals(
				[ $series_pass_attendee_id ],
				tribe_attendees()->where( 'event', $provisional_id )->get_ids(),
				'The original Attendee should be the only Recurring Event Occurrence Attendee.'
			);
		}
		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $single_event )->get_ids(),
			'The original Attendee should be the only Single Event Attendee.'
		);
		$this->assertEqualSets(
			$controls_attendees,
			tribe_attendees()->where( 'event', $control_event )->get_ids(),
			'The control Event Attendees should not be affected.'
		);

		// Clone the Attendees to each Occurrence part of the Recurring Event part of the Series.
		$controller = $this->make_controller();
		$clones = [];
		foreach ( $provisional_ids as $provisional_id ) {
			$clones[ $provisional_id ] = $controller->clone_attendee_to_event( $series_pass_attendee_id, $provisional_id );
		}

		// Check the results after the Attendee cloning to the Recurring Event Ocurrences.
		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The original Attendee should be the only Series Attendee.'
		);
		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $recurring_event )->get_ids(),
			'The original Attendee should be the only Recurring Event Attendee.'
		);
		foreach ( $provisional_ids as $provisional_id ) {
			$occurrence_attendee_id = $clones[ $provisional_id ];
			$this->assertEquals(
				[ $occurrence_attendee_id ],
				tribe_attendees()->where( 'event', $provisional_id )->get_ids(),
				'The cloned Attendee should be the only Recurring Event Occurrence Attendee.'
			);
		}
		$this->assertEquals(
			[ $series_pass_attendee_id ],
			tribe_attendees()->where( 'event', $single_event )->get_ids(),
			'The original Attendee should be the only Single Event Attendee.'
		);
		$this->assertEqualSets(
			$controls_attendees,
			tribe_attendees()->where( 'event', $control_event )->get_ids(),
			'The control Event Attendees should not be affected.'
		);
	}

	/**
	 * It should correctly check-in Attendees manually, through the metabox AJAX action
	 *
	 * @test
	 */
	public function should_correctly_check_in_attendees_manually_through_the_metabox_ajax_action(): void {
		// Record the send JSON payloads, avoid die.
		$response_data = null;
		$this->set_fn_return(
			'wp_send_json_success',
			static function ( $did_checkin ) use ( &$response_data ) {
				$response_data = $did_checkin;

				return null;
			},
			true
		);
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);
		// Become administrator to manually check-in Attendees.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the Series.
		$recurring_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			]
		)->create()->ID;
		$provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Create an Event not part of the Series.
		$control_event = tribe_events()->set_args(
			[
				'title'      => 'Control Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Set up the context to manually check in an Attendee providing the Attendee ID, but not the Event ID.
		$_POST['nonce'] = wp_create_nonce( 'checkin' );
		$_POST['provider'] = Module::class;
		$_POST['attendee_id'] = $series_attendee_id;

		$controller = $this->make_controller();
		$controller->register();
		$metabox = tribe( Metabox::class );

		$response_data = $clone_id = null;
		$controller->reset_reload_trigger();
		$metabox->ajax_attendee_checkin();

		$this->assertEquals( [ 'did_checkin' => false ], $response_data );
		$this->assertNull( $clone_id );

		/*
		 * Send the same request again, this time including the Series ID as context of the check-in.
		 * This is not possible from the UI, but it should be covered nonetheless.
		 */
		$_POST['event_ID'] = $series_id;

		$response_data = $clone_id = null;
		$controller->reset_reload_trigger();
		$metabox->ajax_attendee_checkin();

		$this->assertEquals( [ 'did_checkin' => false ], $response_data );
		$this->assertNull( $clone_id );

		/*
		 * Send the request again, this time checking in the Attendee from the context of an Event not part of the
		 * Series; again: not possible from the UI, but better safe than sorry.
		 */
		$_POST['event_ID'] = $control_event;

		$response_data = $clone_id = null;
		$controller->reset_reload_trigger();
		$metabox->ajax_attendee_checkin();

		$this->assertEquals( [ 'did_checkin' => false ], $response_data );
		$this->assertNull( $clone_id );

		/*
		 * Send the request again, this time checking in the Attendee from the context of the Single Event part of the
		 * Series.
		 */
		$_POST['event_ID'] = $single_event;

		$response_data = $clone_id = null;
		$controller->reset_reload_trigger();
		$metabox->ajax_attendee_checkin();

		$this->assertEquals(
			[
				'did_checkin' => true,
				'reload'      => true,
			],
			$response_data
		);
		$this->assertTrue( $controller->attendee_is_clone_of( $clone_id, $series_attendee_id ) );

		/*
		 * Send the request again, this time checking in the Attendee from the context of the Recurring Event post ID.
		 * This should not be possible from the UI, but better safe than sorry.
		 */
		$_POST['event_ID'] = $recurring_event;

		$response_data = $clone_id = null;
		$controller->reset_reload_trigger();
		$metabox->ajax_attendee_checkin();

		$this->assertEquals( [ 'did_checkin' => false ], $response_data );
		$this->assertNull( $clone_id );

		/*
		 * Send the request again, this time from each one of the Recurring Event part of the Series provisional IDs.
		 */
		foreach ( $provisional_ids as $provisional_id ) {
			$_POST['event_ID'] = $provisional_id;

			$response_data = $clone_id = null;
			$controller->reset_reload_trigger();
			$metabox->ajax_attendee_checkin();

			$this->assertEquals(
				[
					'did_checkin' => true,
					'reload'      => true,
				],
				$response_data
			);
			$this->assertTrue( $controller->attendee_is_clone_of( $clone_id, $series_attendee_id ) );
		}
	}

	/**
	 * It should correctly filter the Event ID depending on the context
	 *
	 * @test
	 */
	public function should_correctly_filter_the_event_id_depending_on_the_context(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Recurring Event part of the Series.
		$recurring_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
			]
		)->create()->ID;
		$provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event )
			->map( fn( Occurrence $o ) => $o->provisional_id );
		// Create an Event not part of the Series.
		$control_event = tribe_events()->set_args(
			[
				'title'      => 'Control Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$controller = $this->make_controller();
		$controller->register();

		// Default context, no changes.
		foreach (
			[
				$series_id          => $series_id,
				$single_event       => $single_event,
				$recurring_event    => $recurring_event,
				$control_event      => $control_event,
				$provisional_ids[0] => $recurring_event,
				$provisional_ids[1] => $recurring_event,
				$provisional_ids[2] => $recurring_event,
			] as $input => $expected
		) {
			$this->assertEquals( $expected, Event::filter_event_id( $input ) );
		}

		foreach ( $controller->get_controlled_event_filter_contexts() as $controlled_context ) {
			foreach (
				[
					$series_id          => $series_id,
					$single_event       => $single_event,
					$recurring_event    => $recurring_event,
					$control_event      => $control_event,
					$provisional_ids[0] => $provisional_ids[0],
					$provisional_ids[1] => $provisional_ids[1],
					$provisional_ids[2] => $provisional_ids[2],
				] as $input => $expected
			) {
				$this->assertEquals( $expected, Event::filter_event_id( $input, $controlled_context ) );
			}
		}
	}

	/**
	 * It should not clone Attendees again when checking in clones
	 *
	 * @test
	 */
	public function should_not_clone_attendees_again_when_checking_in_clones(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		// Start by checking-in the Attendee from the context of the Event to create a clone.
		$this->assertTrue( $commerce->checkin( $series_attendee_id, false, $single_event ) );
		$this->assertTrue( $controller->attendee_is_clone_of( $clone_id, $series_attendee_id ) );

		$event_attendee_id = $clone_id;

		// Checking in the Series Pass Attendee again from the context of the same Event should not generate new clones.
		// Start by checking-in the Attendee from the context of the Event to create a clone.
		$clone_id = null;
		$this->assertTrue( $commerce->checkin( $series_attendee_id, false, $single_event ) );
		$this->assertNull( $clone_id );

		// Checking in the clone from the context of the Event should not trigger the creation of new clones.
		$clone_id = null;
		$this->assertTrue( $commerce->checkin( $event_attendee_id, false, $single_event ) );
		$this->assertNull( $clone_id );

		// Checking out the clone from the context of the Event should not generate new clones.
		$clone_id = null;
		$this->assertTrue( $commerce->uncheckin( $series_attendee_id ) );
		$this->assertNull( $clone_id );

		// Checking in the Series Pass Attendee again from the context of the same Event should not generate new clones.
		// Start by checking-in the Attendee from the context of the Event to create a clone.
		$clone_id = null;
		$this->assertTrue( $commerce->checkin( $series_attendee_id, false, $single_event ) );
		$this->assertNull( $clone_id );

		// Checking in  again the clone from the context of the Event should not trigger the creation of new clones.
		$clone_id = null;
		$this->assertTrue( $commerce->checkin( $event_attendee_id, false, $single_event ) );
		$this->assertNull( $clone_id );
	}

	/**
	 * It should reference real post ID of Single Event from cloned Attendee
	 *
	 * @test
	 */
	public function should_reference_real_post_id_of_single_event_from_cloned_attendee(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_attendee_id = $this->create_attendee_for_ticket( $series_pass_id, $series_id );
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		// Start by checking-in the Attendee from the context of the Event to create a clone.
		$this->assertTrue( $commerce->checkin( $series_attendee_id, false, $series_id ) );
		$this->assertTrue( $controller->attendee_is_clone_of( $clone_id, $series_attendee_id ) );
		$this->assertEquals( $single_event, get_post_meta( $clone_id, $commerce->attendee_event_key, true ) );
	}

	/**
	 * It should remove checkin row action from Series page
	 *
	 * @test
	 */
	public function should_remove_checkin_row_action_from_series_page(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create a Series Pass and an Attendee for the Series.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_attendee_id = tribe_attendees()->where( 'event', $series_id )->first_id();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		// Simulate the request to access the Series Attendees page.
		$_GET['event_id'] = $series_id;
		$_GET['post_type'] = get_post_type( $series_id );
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$attendee_table->prepare_items();
		$attendee_item = $attendee_table->items[0];
		$attendee_email = '';
		if ( ! empty( $attendee_item['holder_email'] ) ) {
			$attendee_email = $attendee_item['holder_email'];
		} elseif ( ! empty( $attendee_item['purchaser_email'] ) ) {
			$attendee_email = $attendee_item['purchaser_email'];
		}

		$this->assertEquals( $series_attendee_id, $attendee_item['ID'] );

		$this->make_controller()->register();

		$column_primary_info = $attendee_table->column_primary_info( $attendee_item );
		$this->assertMatchesHtmlSnapshot(
			str_replace( [
					$series_attendee_id,
					$series_pass_id,
					$series_id,
					$attendee_email,
				]
				, [
					'{{attendee_id}}',
					'{{series_pass_id}}',
					'{{series_id}}',
					'{{attendee_email}}',
				],
				$column_primary_info )
		);
		$this->assertEqualSets( [ 'delete_attendee' ], array_keys( $attendee_table->get_bulk_actions() ) );
	}

	/**
	 * It should remove checkin row action from Series Passes Attendees not yet cloned to Event
	 *
	 * @test
	 */
	public function should_remove_checkin_row_action_from_series_passes_attendeees_not_yet_cloned_to_event(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create two Series Pass Attendees.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 2 ] );
		$series_pass_attendees = tribe_attendees()->where( 'event', $series_id )->get_ids();
		$this->assertCount( 2, $series_pass_attendees );
		[ $series_attendee_1, $series_attendee_2 ] = $series_pass_attendees;
		// Create a Single Event part of the Series.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+2 hours',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);
		$commerce = Module::get_instance();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$controller = $this->make_controller();
		$controller->register();

		// Check-in the first Series Pass Attendee into the Event, thus cloning it to the Event.
		$this->assertTrue( $commerce->checkin( $series_attendee_1, false, $single_event ) );
		$this->assertTrue( $controller->attendee_is_clone_of( $clone_id, $series_attendee_1 ) );

		// Simulate the request to access the Event Attendees page.
		$_GET['event_id'] = $single_event;
		$_GET['post_type'] = get_post_type( $single_event );
		$attendee_table = new Tribe__Tickets__Attendees_Table();
		$attendee_table->prepare_items();
		$attendee_item = $attendee_table->items[0];
		$prepared_items_ids = array_map( fn( array $item ) => $item['ID'], $attendee_table->items );
		$this->assertEqualSets( [ $clone_id, $series_attendee_2 ], $prepared_items_ids );

		$attendee_email = '';
		if ( ! empty( $attendee_item['holder_email'] ) ) {
			$attendee_email = $attendee_item['holder_email'];
		} elseif ( ! empty( $attendee_item['purchaser_email'] ) ) {
			$attendee_email = $attendee_item['purchaser_email'];
		}

		// Attendees order in the table is not reliable, work them out.
		$cloned_attendee_item = $attendee_table->items[0]['ID'] === $clone_id ? $attendee_table->items[0]
			: $attendee_table->items[1];
		$series_pass_attendee_item = $attendee_table->items[0]['ID'] === $clone_id ? $attendee_table->items[1]
			: $attendee_table->items[0];

		// The check-in actions should be available on the cloned Attendee, but not on the Series Pass one.
		$cloned_attendee_html = str_replace( [
			$clone_id,
			$series_pass_id,
			$series_id,
			$single_event,
			$attendee_email,
		], [
			'{{attendee_email}}',
			'{{cloned_attendee_id}}',
			'{{series_pass_id}}',
			'{{series_id}}',
			'{{event_id}}',
		],
			$attendee_table->column_primary_info( $cloned_attendee_item )
		);
		$series_pass_attendee_html = str_replace( [
			$series_pass_attendee_item['ID'],
			$series_pass_id,
			$series_id,
			$single_event,
			$attendee_email,
		], [
			'{{series_pass_attendee_id}}',
			'{{series_pass_id}}',
			'{{series_id}}',
			'{{event_id}}',
			'{{attendee_email}}',
		],
			$attendee_table->column_primary_info( $series_pass_attendee_item )
		);

		$this->assertMatchesHtmlSnapshot( $cloned_attendee_html . "\n\n" . $series_pass_attendee_html );
		$this->assertEqualSets( [
			'delete_attendee',
			'check_in',
			'uncheck_in'
		], array_keys( $attendee_table->get_bulk_actions() ) );
	}

	/**
	 * It should correctly check-in of running events in diff. timezones by readl post ID
	 *
	 * @test
	 */
	public function should_correctly_check_in_of_running_events_in_diff_timezones_by_real_post_id(): void {
		// Set the site timezone to America/Sao_Paulo
		update_option( 'timezone_string', 'America/Sao_Paulo' );
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertTrue( $commerce->checkin( $series_pass_attendee, true, $event_1 ) );
		$this->assertTrue( $commerce->checkin( $series_pass_attendee, true, $event_2 ) );
	}

	/**
	 * It should correctly check-in of running events in diff. timezones by provisional ID
	 *
	 * @test
	 */
	public function should_correctly_check_in_of_running_events_in_diff_timezones_by_provisional_id(): void {
		// Set the site timezone to America/Sao_Paulo
		update_option( 'timezone_string', 'America/Sao_Paulo' );
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$commerce = Module::get_instance();

		$controller = $this->make_controller();
		$controller->register();

		$this->assertTrue( $commerce->checkin( $series_pass_attendee, true, Occurrence::find($event_1,'post_id')->provisional_id ) );
		$this->assertTrue( $commerce->checkin( $series_pass_attendee, true, Occurrence::find($event_2,'post_id')->provisional_id ) );
	}

	/**
	 * It should move only Series Pass Attendee when moving series pass Attendee
	 *
	 * The UI will not allow users to move the Series Pass Attendee, not a clone, directly.
	 * But the programmatic API should allow and support it.
	 *
	 * @test
	 */
	public function should_move_only_series_pass_attendee_when_moving_series_pass_attendee(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		$commerce = Module::get_instance();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			static function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);

		$controller = $this->make_controller();
		$controller->register();

		// Check-in the Series Pass Attendee into the Event, thus cloning it to the Event 1.
		$this->assertTrue($commerce->checkin($series_pass_attendee,false, $event_1));
		$this->assertNotNull($clone_id);
		$this->assertNotEquals($series_pass_attendee, $clone_id);
		$event_1_clone_attendee = $clone_id;

		// Check-in the Series Pass Attendee into the Event, thus cloning it to the Event 2.
		$this->assertTrue($commerce->checkin($series_pass_attendee,false, $event_2));
		$this->assertNotNull($clone_id);
		$this->assertNotEquals($series_pass_attendee, $clone_id);
		$event_2_clone_attendee = $clone_id;

		// Create an Event that is not part of the Series with its own Single Ticket.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours'
			]
		)->create()->ID;
		$single_ticket = $this->create_tc_ticket($single_event,1);

		// Sanity check.
		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( [ $event_1_clone_attendee ], tribe_attendees()->where( 'event', $event_1 )->get_ids() );
		$this->assertEquals( [ $event_2_clone_attendee ], tribe_attendees()->where( 'event', $event_2 )->get_ids() );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $single_event )->get_ids() );

		// Move the Series Pass Attendee to the Single Event.
		$mover = tribe( Move_Attendees::class );
		$moved = $mover->move_tickets( [ $series_pass_attendee ], $single_ticket, $series_id, $single_event );

		$this->assertEquals( 1, $moved );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The Series Pass Attendee should have been removed from the Series.'
		);
		$this->assertEquals( [], tribe_attendees()->where( 'event', $event_1 )->get_ids(),
			'The cloned Attendee should have been removed from the Event 1.'
		);
		$this->assertEquals( [], tribe_attendees()->where( 'event', $event_2 )->get_ids(),
			'The cloned Attendee should have been removed from the Event 2.'
		);
		$this->assertEquals('publish', get_post_status($series_pass_attendee),
			'The status of the Series Pass Attendee should not have been changed.'
		);
		$this->assertNull( get_post( $event_1_clone_attendee ),
			'The cloned Attendee for Event 1 should have been deleted.'
		);
		$this->assertNull( get_post( $event_2_clone_attendee ),
			'The cloned Attendee for Event 2 should have been deleted.'
		);
		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $single_event )->get_ids(),
			'Only the Series Pass Attendee should have been moved to the Single Event.'
		);
	}

	/**
	 * It should move only Series Pass Attendee when moving cloned Attendee
	 *
	 * This handles the request coming from the Attendees list screen of the Event to move the
	 * cloned Attendee to another Ticket on another post.
	 *
	 * @test
	 */
	public function should_move_only_series_pass_attendee_when_moving_cloned_attendee(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		$commerce = Module::get_instance();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendee ID.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			static function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);

		$controller = $this->make_controller();
		$controller->register();

		// Check-in the Series Pass Attendee into the Event, thus cloning it to the Event 1.
		$this->assertTrue($commerce->checkin($series_pass_attendee,false, $event_1));
		$this->assertNotNull($clone_id);
		$this->assertNotEquals($series_pass_attendee, $clone_id);
		$event_1_clone_attendee = $clone_id;

		// Check-in the Series Pass Attendee into the Event, thus cloning it to the Event 2.
		$this->assertTrue($commerce->checkin($series_pass_attendee,false, $event_2));
		$this->assertNotNull($clone_id);
		$this->assertNotEquals($series_pass_attendee, $clone_id);
		$event_2_clone_attendee = $clone_id;

		// Create an Event that is not part of the Series with its own Single Ticket.
		$single_event = tribe_events()->set_args(
			[
				'title'      => 'Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours'
			]
		)->create()->ID;
		$single_ticket = $this->create_tc_ticket($single_event,1);

		// Sanity check.
		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $series_id )->get_ids() );
		$this->assertEquals( [ $event_1_clone_attendee ], tribe_attendees()->where( 'event', $event_1 )->get_ids() );
		$this->assertEquals( [ $event_2_clone_attendee ], tribe_attendees()->where( 'event', $event_2 )->get_ids() );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $single_event )->get_ids() );

		// Move Event 2 cloned Attendee to the Single Event.
		// This simulates the user request coming from Event 2 Attendees list screen.
		$mover = tribe( Move_Attendees::class );
		$moved = $mover->move_tickets( [ $event_2_clone_attendee ], $single_ticket, $event_2, $single_event );

		$this->assertEquals( 1, $moved );
		$this->assertEquals( [], tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The Series Pass Attendee should have been removed from the Series.'
		);
		$this->assertEquals( [], tribe_attendees()->where( 'event', $event_1 )->get_ids(),
			'The cloned Attendee should have been removed from the Event 1.'
		);
		$this->assertEquals( [], tribe_attendees()->where( 'event', $event_2 )->get_ids(),
			'The cloned Attendee should have been removed from the Event 2.'
		);
		$this->assertEquals('publish', get_post_status($series_pass_attendee),
			'The status of the Series Pass Attendee should not have been changed.'
		);
		$this->assertNull( get_post( $event_1_clone_attendee ),
			'The cloned Attendee for Event 1 should have been deleted.'
		);
		$this->assertNull( get_post( $event_2_clone_attendee ),
			'The cloned Attendee for Event 2 should have been deleted.'
		);
		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $single_event )->get_ids(),
			'Only the Series Pass Attendee should have been moved to the Single Event.'
		);
	}

	/**
	 * It should handle uncheckin of Series Pass Attendee correctly from single Event part of Series
	 *
	 * @test
	 */
	public function should_handle_uncheckin_of_series_pass_attendee_correctly_from_single_event_part_of_series(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		$commerce = Module::get_instance();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_1_provisional_id = Occurrence::find( $event_1, 'post_id' )->provisional_id;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2_provisional_id = Occurrence::find( $event_2, 'post_id' )->provisional_id;
		// Create a Recurring Event part of the Series.
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3_occurrences = Occurrence::where( 'post_id', '=', $event_3 )->get();
		// Subscribe to the Attendee clone action to capture the cloned Attendees IDs.
		$clone_ids = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			static function ( $cloned_attendee_id ) use ( &$clone_ids ) {
				$clone_ids[]= $cloned_attendee_id;
			}
		);
		// Check-in the Series Pass Attendee. This is not possible using the UI, but could be done in the first release.
		$this->assertTrue( $commerce->checkin( $series_pass_attendee, false, $series_id ) );
		$this->assertEquals( 1, get_post_meta( $series_pass_attendee, $commerce->checkin_key, true ),
			'The Series Pass Attendee should be checked in.'
		);

		$controller = $this->make_controller();
		$controller->register();

		// Sanity check.
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $series_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_1 )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_1_provisional_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_2 )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_2_provisional_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_3 )->where( 'checkedin', true )->get_ids()
		);
		foreach ( $event_3_occurrences as $occurrence ) {
			$this->assertEquals(
				[ $series_pass_attendee ],
				tribe_attendees()->where( 'event', $occurrence->provisional_id )->where( 'checkedin', true )->get_ids()
			);
		}

		// Uncheck-in the Series Pass Attendee directly from the context of Event 2. Set the context ID.
		// This simulates what the AJAX or the QR code scan from teh ET+ app would do.
		$_GET['event_ID'] = $event_2;
		$this->assertTrue( $commerce->uncheckin( $series_pass_attendee ) );

		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The Series Pass Attendee should not have moved.'
		);
		$this->assertEquals( 1, get_post_meta( $series_pass_attendee, $commerce->checkin_key, true ),
			'The Series Pass Attendee should not have been un-checked in.'
		);
		$this->assertCount( 5, $clone_ids,
			'Five cloned Attendees should have been created: 2 for the Single Events, 3 for the Recurring Event Occurrences.'
		);
		$event_1_clone_attendee = tribe_attendees()->where( 'event', $event_1 )->first_id();
		$this->assertContains( $event_1_clone_attendee, $clone_ids,
			'One of the cloned Attendees should be related to the Event 1.'
		);
		$this->assertEquals( 1, get_post_meta( $event_1_clone_attendee, $commerce->checkin_key, true ),
			'The cloned Attendee for Event 1 should still be checked in.'
		);
		$event_2_clone_attendee = tribe_attendees()->where( 'event', $event_2 )->first_id();
		$this->assertContains( $event_2_clone_attendee, $clone_ids,
			'One of the cloned Attendees should be related to the Event 2.'
		);
		$this->assertEmpty( get_post_meta( $event_2_clone_attendee, $commerce->checkin_key, true ),
			'The cloned Attendee for Event 2 should be no more checked-in.'
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_3 )->where( 'checkedin', true )->get_ids(),
			'The only Attendee related to the Recurring Event real post ID should be the Series Pass Attendee.'
		);
		foreach ( $event_3_occurrences as $k => $occurrence ) {
			$occurrence_clone_attendee_id = tribe_attendees()->where( 'event', $occurrence->provisional_id )->first_id();
			$this->assertContains( $occurrence_clone_attendee_id, $clone_ids,
				"One of the cloned Attendees should be related to the {$k} Recurring Event Occurrence."
			);
			$this->assertEquals( 1, get_post_meta( $occurrence_clone_attendee_id, $commerce->checkin_key, true ),
				"{$k} Recurring Event Occurrence cloned Attendee should still be checked in."
			);
		}
	}

	/**
	 * It should handle uncheckin of Series Pass Attendee correctly from Recurring Event Occurrence part of Series
	 *
	 * @test
	 */
	public function should_handle_uncheckin_of_series_pass_attendee_correctly_from_recurring_event_occurrence_part_of_series(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		$commerce = Module::get_instance();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_1 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_1_provisional_id = Occurrence::find( $event_1, 'post_id' )->provisional_id;
		// Create a Single Event part of the Series that starts in 1 hour.
		$event_2 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_2_provisional_id = Occurrence::find( $event_2, 'post_id' )->provisional_id;
		// Create a Recurring Event part of the Series.
		$event_3 = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '+1 hour',
				'end_date'   => '+3 hours',
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;
		$event_3_occurrences = Occurrence::where( 'post_id', '=', $event_3 )->get();
		// Subscribe to the Attendee clone action to capture the cloned Attendees IDs.
		$clone_ids = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			static function ( $cloned_attendee_id ) use ( &$clone_ids ) {
				$clone_ids[]= $cloned_attendee_id;
			}
		);
		// Check-in the Series Pass Attendee. This is not possible using the UI, but could be done in the first release.
		$this->assertTrue( $commerce->checkin( $series_pass_attendee, false, $series_id ) );
		$this->assertEquals( 1, get_post_meta( $series_pass_attendee, $commerce->checkin_key, true ),
			'The Series Pass Attendee should be checked in.'
		);

		$controller = $this->make_controller();
		$controller->register();

		// Sanity check.
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $series_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_1 )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_1_provisional_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_2 )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_2_provisional_id )->where( 'checkedin', true )->get_ids()
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_3 )->where( 'checkedin', true )->get_ids()
		);
		foreach ( $event_3_occurrences as $occurrence ) {
			$this->assertEquals(
				[ $series_pass_attendee ],
				tribe_attendees()->where( 'event', $occurrence->provisional_id )->where( 'checkedin', true )->get_ids()
			);
		}

		// Uncheck-in the Series Pass Attendee directly from the context of the Recurring Event 2nd Occurrence.
		// This simulates what the AJAX or the QR code scan from teh ET+ app would do.
		$_GET['event_ID'] = $event_3_occurrences[1]->provisional_id;
		$this->assertTrue( $commerce->uncheckin( $series_pass_attendee ) );

		$this->assertEquals( [ $series_pass_attendee ], tribe_attendees()->where( 'event', $series_id )->get_ids(),
			'The Series Pass Attendee should not have moved.'
		);
		$this->assertEquals( 1, get_post_meta( $series_pass_attendee, $commerce->checkin_key, true ),
			'The Series Pass Attendee should not have been un-checked in.'
		);
		$this->assertCount( 5, $clone_ids,
			'Five cloned Attendees should have been created: 2 for the Single Events, 3 for the Recurring Event Occurrences.'
		);
		$event_1_clone_attendee = tribe_attendees()->where( 'event', $event_1 )->first_id();
		$this->assertContains( $event_1_clone_attendee, $clone_ids,
			'One of the cloned Attendees should be related to the Event 1.'
		);
		$this->assertEquals( 1, get_post_meta( $event_1_clone_attendee, $commerce->checkin_key, true ),
			'The cloned Attendee for Event 1 should still be checked in.'
		);
		$event_2_clone_attendee = tribe_attendees()->where( 'event', $event_2 )->first_id();
		$this->assertContains( $event_2_clone_attendee, $clone_ids,
			'One of the cloned Attendees should be related to the Event 2.'
		);
		$this->assertEquals(1, get_post_meta( $event_2_clone_attendee, $commerce->checkin_key, true ),
			'The cloned Attendee for Event 2 should still be checked in.'
		);
		$this->assertEquals(
			[ $series_pass_attendee ],
			tribe_attendees()->where( 'event', $event_3 )->where( 'checkedin', true )->get_ids(),
			'The only Attendee related to the Recurring Event real post ID should be the Series Pass Attendee.'
		);
		$occurrence_1_clone_id = tribe_attendees()->where( 'event', $event_3_occurrences[0]->provisional_id )->first_id();
		$this->assertContains( $occurrence_1_clone_id, $clone_ids,
			'One of the cloned Attendees should be related to Recurring Event first Occurrence.'
		);
		$this->assertEquals(1, get_post_meta( $occurrence_1_clone_id, $commerce->checkin_key, true ),
			'The cloned Attendee for the Recurring Event first Occurrence should still be checked in.'
		);
		$occurrence_2_clone_id = tribe_attendees()->where( 'event', $event_3_occurrences[1]->provisional_id )->first_id();
		$this->assertContains( $occurrence_2_clone_id, $clone_ids,
			'One of the cloned Attendees should be related to Recurring Event second Occurrence.'
		);
		$this->assertEmpty(get_post_meta( $occurrence_2_clone_id, $commerce->checkin_key, true ),
			'The cloned Attendee for the Recurring Event second Occurrence should be no more checked-in.'
		);
		$occurrence_3_clone_id = tribe_attendees()->where( 'event', $event_3_occurrences[2]->provisional_id )->first_id();
		$this->assertContains( $occurrence_3_clone_id, $clone_ids,
			'One of the cloned Attendees should be related to Recurring Event third Occurrence.'
		);
		$this->assertEquals(1, get_post_meta( $occurrence_3_clone_id, $commerce->checkin_key, true ),
			'The cloned Attendee for the Recurring Event third Occurrence should still be checked in.'
		);
	}

	/**
	 * It should not allow cloned attendee for only candidate Event to check-in more than once
	 *
	 * @test
	 */
	public function should_not_allow_cloned_attendee_for_only_candidate_event_to_check_in_more_than_once(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series.
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		// Create one Series Pass Attendee.
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$this->create_order( [ $series_pass_id => 1 ] );
		$series_pass_attendee = tribe_attendees()->where( 'event', $series_id )->first_id();
		// Create a Single Event part of the Series that started one hour ago and will end in one hour.
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Series Single Event',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'end_date'   => '+1 hour',
				'series'     => $series_id,
			]
		)->create()->ID;
		// Subscribe to the Attendee clone action to capture the cloned Attendees IDs.
		$clone_id = null;
		add_action(
			'tec_tickets_flexible_tickets_series_pass_attendee_cloned',
			static function ( $cloned_attendee_id ) use ( &$clone_id ) {
				$clone_id = $cloned_attendee_id;
			}
		);
		$commerce = Module::get_instance();
		$api_key = 'secrett-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$controller = $this->make_controller();
		$controller->register();

		// Become an app user trying to scan Attendees in.
		wp_set_current_user( 0 );

		// Check-in the Series Pass Attendee a first time: the Attendee will be cloned to the Event.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_pass_attendee );
		$request->set_param( 'security_code', get_post_meta( $series_pass_attendee, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertNotNull(
			$clone_id,
			'The cloned Attendee ID should have been captured.'
		);
		$this->assertNotSame(
			$clone_id,
			$series_pass_attendee,
			'The cloned Attendee should not be the same as the Series Pass Attendee.'
		);
		$this->assertEquals(
			1,
			get_post_meta( $clone_id, '_tribe_qr_status', true ),
			'The cloned Attendee should have been checked in with QR code.'
		);

		// Attempt another check-in using the Series Pass Attendee: it should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $series_pass_attendee );
		$request->set_param( 'security_code', get_post_meta( $series_pass_attendee, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );

		// Attempt another check-in using the Cloned Attendee: it should fail.
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $clone_id );
		$request->set_param( 'security_code', get_post_meta( $clone_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}
}
