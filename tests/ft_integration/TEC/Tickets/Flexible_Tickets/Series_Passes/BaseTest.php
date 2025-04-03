<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Closure;
use Generator;
use Stripe\SearchResult;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Editors\Block\Ajax;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Admin__Notices as Notices;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Attendees as Attendees;
use WP_Hook;

class BaseTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;
	use With_Uopz;

	protected string $controller_class = Base::class;

	/**
	 * @before
	 */
	public function ensure_ticketables(): void {
		$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable_post_types[] = 'post';
		$ticketable_post_types[] = 'page';
		$ticketable_post_types[] = TEC::POSTTYPE;
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		$ticketable_post_types   = array_values( array_unique( $ticketable_post_types ) );
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
	}

	/**
	 * It should disable Tickets and RSVPs for Series
	 *
	 * @test
	 */
	public function should_disable_tickets_and_rsvps_for_series(): void {
		$controller = $this->make_controller();

		$filtered = $controller->enable_ticket_forms_for_series( [
			'default' => true,
			'rsvp'    => true,
		] );

		$this->assertEquals( [
			'default'                  => false,
			'rsvp'                     => false,
			Series_Passes::TICKET_TYPE => true,
		], $filtered );
	}

	/**
	 * It should not replace tickets block on post
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_post(): void {
		$post_id  = static::factory()->post->create( [
			'post_type' => 'page',
		] );
		$ticket_1 = $this->create_tc_ticket( $post_id, 23 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 24 );
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $ticket_1, 'menu_order' => 1 ] );
		wp_update_post( [ 'ID' => $ticket_2, 'menu_order' => 2 ] );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, $ticket_1, $ticket_2 ],
			[ '{{post_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should not replace tickets block on Series
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_series(): void {
		$series = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$pass_1 = $this->create_tc_series_pass( $series, 23 )->ID;
		$pass_2 = $this->create_tc_series_pass( $series, 89 )->ID;
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $pass_1, 'menu_order' => 1 ] );
		wp_update_post( [ 'ID' => $pass_2, 'menu_order' => 0 ] );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $series );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $series, $pass_1, $pass_2 ],
			[ '{{series_id}}', '{{pass_1}}', '{{pass_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should not replace tickets block on Events not in Series
	 *
	 * @test
	 */
	public function should_not_replace_tickets_block_on_events_not_in_series(): void {
		$event    = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 00:00:00',
		] )->create()->ID;
		$ticket_1 = $this->create_tc_ticket( $event, 23 );
		$ticket_2 = $this->create_tc_ticket( $event, 89 );
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $ticket_1, 'menu_order' => 1 ] );
		wp_update_post( [ 'ID' => $ticket_2, 'menu_order' => 0 ] );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $event );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $event, $ticket_1, $ticket_2 ],
			[ '{{event_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should replace tickets block on Events in Series
	 *
	 * @test
	 */
	public function should_replace_tickets_block_on_events_in_series(): void {
		$series = static::factory()->post->create( [
			'post_type'  => Series_Post_Type::POSTTYPE,
			'post_title' => 'Test Series block',
		] );
		$pass_1 = $this->create_tc_series_pass( $series, 23 )->ID;
		$pass_2 = $this->create_tc_series_pass( $series, 89 )->ID;
		$pass_3 = $this->create_tc_series_pass( $series, 89 )->ID;
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $pass_1, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $pass_2, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $pass_3, 'menu_order' => 1 ] );
		$event    = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 00:00:00',
			'series'     => $series,
		] )->create()->ID;
		$ticket_1 = $this->create_tc_ticket( $event, 23 );
		$ticket_2 = $this->create_tc_ticket( $event, 89 );
		$ticket_3 = $this->create_tc_ticket( $event, 89 );
		// Sort the tickets "manually".
		wp_update_post( [ 'ID' => $ticket_1, 'menu_order' => 2 ] );
		wp_update_post( [ 'ID' => $ticket_2, 'menu_order' => 0 ] );
		wp_update_post( [ 'ID' => $ticket_3, 'menu_order' => 1 ] );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $event );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[
				$event,
				$ticket_1,
				$ticket_2,
				$ticket_3,
				$series,
				$pass_1,
				$pass_2,
				$pass_3
			],
			[
				'{{event_id}}',
				'{{ticket_1}}',
				'{{ticket_2}}',
				'{{ticket_3}}',
				'{{series_id}}',
				'{{pass_1}}',
				'{{pass_2}}',
				'{{pass_3}}'
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should disable tickets and RSVPs for recurring event
	 *
	 * @test
	 */
	public function should_disable_tickets_and_rsvps_for_recurring_event(): void {
		$recurrence      = ( new Recurrence() )
			->with_start_date( '2020-01-01 00:00:00' )
			->with_end_date( '2020-01-01 10:00:00' )
			->with_weekly_recurrence()
			->with_end_after( 3 )
			->to_event_recurrence();
		$recurring_event = tribe_events()->set_args( [
			'title'      => 'Single Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 10:00:00',
			'recurrence' => $recurrence
		] )->create();

		$controller = $this->make_controller();

		$filtered = $controller->disable_tickets_on_recurring_events( [
			'default' => true,
			'rsvp'    => true,
		], $recurring_event->ID );

		$this->assertEqualSets( [
			'default' => false,
			'rsvp'    => false,
		], $filtered );
	}

	/**
	 * It should not disable tickets and RSVPs for single event
	 *
	 * @test
	 */
	public function should_not_disable_tickets_and_rsvps_for_single_event(): void {
		$single_event = tribe_events()->set_args( [
			'title'      => 'Single Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 10:00:00',
		] )->create();

		$controller = $this->make_controller();

		$filtered = $controller->disable_tickets_on_recurring_events( [
			'default' => true,
			'rsvp'    => true,
		], $single_event->ID );

		$this->assertEqualSets( [
			'default' => true,
			'rsvp'    => true,
		], $filtered );
	}

	public function recurring_events_and_tickets_admin_notices_provider(): Generator {
		yield 'single event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Single Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
				] )->create()->ID;

				return [ $event, null, false ];
			}
		];

		yield 'single event with tickets' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Single Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				return [ $event, $ticket_id, false ];
			}
		];

		yield 'recurring event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;

				return [ $event, null, false ];
			}
		];

		yield 'recurring event with tickets' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				return [ $event, $ticket_id, true ];
			}
		];

		yield 'recurring event occurrence' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;

				// Second occurrence.
				$occurrence = Occurrence::where( 'post_id', $event )->offset( 1 )->first();

				return [ $occurrence->provisional_id, null, false ];
			}
		];

		yield 'recurring event with tickets occurrence' => [
			function () {
				$event     = tribe_events()->set_args( [
					'title'      => 'Recurring Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'end_date'   => '2020-01-01 10:00:00',
					'recurrence' => 'RRULE:FREQ=WEEKLY;COUNT=3',
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event );

				// Second occurrence.
				$occurrence = Occurrence::where( 'post_id', $event )->offset( 1 )->first();

				return [ $occurrence->provisional_id, $ticket_id, true ];
			}
		];
	}

	/**
	 * It should control the notice about recurring events and tickets correctly
	 *
	 * @test
	 * @dataProvider recurring_events_and_tickets_admin_notices_provider
	 */
	public function should_control_the_notice_about_recurring_events_and_tickets_correctly( Closure $fixture ): void {
		[ $event_id, $ticket_id, $expect_notice_when_unregistered ] = array_replace( [ null, null, null ], $fixture() );

		$notices     = Notices::instance();
		$notice_slug = 'tribe_notice_classic_editor_ecp_recurring_tickets-' . $event_id;

		// Simulate a request to edit the event.
		$_GET['post'] = $event_id;

		// Remove other hooked functions to avoid side effects.
		$GLOBALS['wp_filter']['admin_init'] = new WP_Hook();

		// Hook the admin notices.
		tribe( 'tickets.admin.notices' )->hook();

		// Finally dispatch the `admin_init` action.
		do_action( 'admin_init' );

		$notice = $notices->get( $notice_slug );

		if ( $expect_notice_when_unregistered ) {
			$this->assertNotNull( $notice, 'Notice should be present when unregistered.' );
		} else {
			$this->assertNull( $notice, 'Notice should not be present when registered.' );
		}

		// Build and register the controller.
		$this->make_controller()->register();

		// Simulate a request to edit the event.
		$_GET['post'] = $event_id;

		// Remove the previous notice.
		$notice = $notices->remove( $notice_slug );
		$this->assertNull( $notices->get( $notice_slug ) );

		// Dispatch the `admin_init` action again.
		do_action( 'admin_init' );

		$notice = $notices->get( $notice_slug );
		$this->assertNull( $notice, 'When the controller is registered no notice should ever show.' );
	}

	public function attendees_page_top_header_details_provider(): Generator {
		yield 'post' => [
			function () {
				$post_id = static::factory()->post->create();

				return $post_id;
			}
		];

		yield 'event' => [
			function () {
				$post_id = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'status'     => 'publish',
					'start_date' => '2020-01-01 09:00:00',
					'end_date'   => '2020-01-01 11:30:00',
				] )->create()->ID;

				return $post_id;
			}
		];

		yield 'series' => [
			function () {
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE
				] );

				return $post_id;
			}
		];
	}

	/**
	 * It should correctly filter the Attendees page top header details
	 *
	 * @test
	 * @dataProvider attendees_page_top_header_details_provider
	 */
	public function should_correctly_filter_the_attendees_page_top_header_details( Closure $fixture ): void {
		$post_id = $fixture();

		// Build and register the controller.
		$this->make_controller()->register();

		// Run the test again.
		ob_start();
		$attendees = new Attendees();
		$attendees->event_details_top( $post_id );
		$html = ob_get_contents();
		// Replace post IDs with a placeholder to avoid snapshot mismatches.
		$html = str_replace( $post_id, '{{POST_ID}}', $html );
		ob_end_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * It should filter series AJAX data to add ticket provider
	 *
	 * @test
	 */
	public function should_filter_series_ajax_data_to_add_ticket_provider(): void {
		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Create a Series with the TC as provider,
		$series        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$event_post_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 09:00:00',
			'end_date'   => '2020-01-01 11:30:00',
			'series'     => $series,
		] )->create()->ID;
		update_post_meta( $series, '_tribe_default_ticket_provider', Module::class );
		$ct1_ajax = tribe( Ajax::class );
		// Mock the `wp_send_json_success_function` to grab the response.
		$response = null;
		$this->set_fn_return( 'wp_send_json_success', static function ( $send_data ) use ( &$response ) {
			$response = $send_data;

			return null;
		}, true );

		// Execute the AJAX handler a first time, without the controller filtering the response.
		$_REQUEST = [
			Ajax::SERIES_NONCE_NAME => wp_create_nonce( Ajax::SERIES_ACTION ),
			'event_id'              => $event_post_id,
		];
		$ct1_ajax->handle_series_data_ajax();

		// What is actually there is not tested here, but in CT1 coverage.
		$this->assertIsArray( $response );

		// Register the controller and run the same AJAX handler again.
		$this->make_controller()->register();
		$ct1_ajax->handle_series_data_ajax();

		// Check that the response has been filtered.
		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'ticket_provider', $response );
		$this->assertEquals( Module::class, $response['ticket_provider'] );
	}

	/**
	 * @after
	 */
	public function clean_up_request(): void {
		$_REQUEST = [];
	}
}
