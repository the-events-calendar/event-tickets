<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Save_Ticket_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @var RSVP
	 */
	protected $rsvp;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( RSVP::class );
	}

	/**
	 * It should create new ticket via repository
	 *
	 * @test
	 */
	public function should_create_new_ticket_via_repository(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Test Ticket';
		$ticket->description = 'Test Description';
		$ticket->price = 25;
		$ticket->show_description = 'yes';

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, [] );

		$this->assertGreaterThan( 0, $ticket_id );
		$this->assertEquals( 'Test Ticket', get_post( $ticket_id )->post_title );
		$this->assertEquals( 'Test Description', get_post( $ticket_id )->post_excerpt );
		$this->assertEquals( 25, get_post_meta( $ticket_id, '_price', true ) );
	}

	/**
	 * It should update existing ticket via repository
	 *
	 * @test
	 */
	public function should_update_existing_ticket_via_repository(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title'   => 'Old Name',
			'post_excerpt' => 'Old Description',
			'meta_input'   => [
				'_price' => 10,
			],
		] );

		$ticket = new Ticket_Object();
		$ticket->ID = $ticket_id;
		$ticket->name = 'Updated Name';
		$ticket->description = 'Updated Description';
		$ticket->price = 30;
		$ticket->show_description = 'yes';

		$result = $this->rsvp->save_ticket( $event_id, $ticket, [] );

		$this->assertEquals( $ticket_id, $result );
		$this->assertEquals( 'Updated Name', get_post( $ticket_id )->post_title );
		$this->assertEquals( 'Updated Description', get_post( $ticket_id )->post_excerpt );
		$this->assertEquals( 30, get_post_meta( $ticket_id, '_price', true ) );
	}

	/**
	 * It should set all ticket fields correctly
	 *
	 * @test
	 */
	public function should_set_all_ticket_fields_correctly(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Complete Ticket';
		$ticket->description = 'Full Description';
		$ticket->price = 50;
		$ticket->show_description = 'yes';
		$ticket->menu_order = 5;

		$raw_data = [
			'ticket_start_date' => date( 'Y-m-d', strtotime( '+1 day' ) ),
			'ticket_start_time' => '10:00:00',
			'ticket_end_date'   => date( 'Y-m-d', strtotime( '+7 days' ) ),
			'ticket_end_time'   => '18:00:00',
			'tribe-ticket'      => [
				'not_going' => 'yes',
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, $raw_data );

		$this->assertGreaterThan( 0, $ticket_id );
		$this->assertEquals( 'Complete Ticket', get_post( $ticket_id )->post_title );
		$this->assertEquals( 50, get_post_meta( $ticket_id, '_price', true ) );
		$this->assertEquals( 'yes', get_post_meta( $ticket_id, '_tribe_ticket_show_description', true ) );
		$this->assertNotEmpty( get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertNotEmpty( get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * It should handle dates properly (present, empty, delete)
	 *
	 * @test
	 */
	public function should_handle_dates_properly(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
			],
		] );

		// Update ticket to remove dates
		$ticket = new Ticket_Object();
		$ticket->ID = $ticket_id;
		$ticket->name = 'No Dates';
		$ticket->description = 'Test';
		$ticket->price = 10;
		$ticket->show_description = 'yes';

		$raw_data = [
			'ticket_start_date' => '',
			'ticket_end_date'   => '',
		];

		$result = $this->rsvp->save_ticket( $event_id, $ticket, $raw_data );

		$this->assertEquals( $ticket_id, $result );
		// Dates should be deleted
		$this->assertEmpty( get_post_meta( $ticket_id, '_ticket_start_date', true ) );
		$this->assertEmpty( get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * It should apply action hooks
	 *
	 * @test
	 */
	public function should_apply_action_hooks(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$create_hook_called = false;
		$save_hook_called = false;

		add_action( 'event_tickets_after_create_ticket', function() use ( &$create_hook_called ) {
			$create_hook_called = true;
		} );

		add_action( 'event_tickets_after_save_ticket', function() use ( &$save_hook_called ) {
			$save_hook_called = true;
		} );

		$ticket = new Ticket_Object();
		$ticket->name = 'Hook Test';
		$ticket->description = 'Test';
		$ticket->price = 15;
		$ticket->show_description = 'yes';

		$this->rsvp->save_ticket( $event_id, $ticket, [] );

		$this->assertTrue( $create_hook_called );
		$this->assertTrue( $save_hook_called );
	}

	/**
	 * It should return ticket ID
	 *
	 * @test
	 */
	public function should_return_ticket_id(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Return ID Test';
		$ticket->description = 'Test';
		$ticket->price = 20;
		$ticket->show_description = 'yes';

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, [] );

		$this->assertIsInt( $ticket_id );
		$this->assertGreaterThan( 0, $ticket_id );
	}

	/**
	 * It should handle show_description field
	 *
	 * @test
	 */
	public function should_handle_show_description_field(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Test with show_description = yes
		$ticket1 = new Ticket_Object();
		$ticket1->name = 'Show Desc Yes';
		$ticket1->description = 'Visible';
		$ticket1->price = 10;
		$ticket1->show_description = 'yes';

		$ticket_id1 = $this->rsvp->save_ticket( $event_id, $ticket1, [] );
		$this->assertEquals( 'yes', get_post_meta( $ticket_id1, '_tribe_ticket_show_description', true ) );

		// Test with show_description = no
		$ticket2 = new Ticket_Object();
		$ticket2->name = 'Show Desc No';
		$ticket2->description = 'Hidden';
		$ticket2->price = 10;
		$ticket2->show_description = 'no';

		$ticket_id2 = $this->rsvp->save_ticket( $event_id, $ticket2, [] );
		$this->assertEquals( 'no', get_post_meta( $ticket_id2, '_tribe_ticket_show_description', true ) );
	}

	/**
	 * It should create event relationship for new tickets
	 *
	 * @test
	 */
	public function should_create_event_relationship_for_new_tickets(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Event Relationship';
		$ticket->description = 'Test';
		$ticket->price = 10;
		$ticket->show_description = 'yes';

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, [] );

		$stored_event_id = get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
		$this->assertEquals( $event_id, $stored_event_id );
	}

	/**
	 * It should handle start and end dates with times
	 *
	 * @test
	 */
	public function should_handle_start_and_end_dates_with_times(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Timed Ticket';
		$ticket->description = 'Test';
		$ticket->price = 10;
		$ticket->show_description = 'yes';

		$raw_data = [
			'ticket_start_date' => '2025-01-01',
			'ticket_start_time' => '09:00:00',
			'ticket_end_date'   => '2025-01-10',
			'ticket_end_time'   => '17:00:00',
		];

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, $raw_data );

		$start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$end_date   = get_post_meta( $ticket_id, '_ticket_end_date', true );

		$this->assertStringContainsString( '09:00:00', $start_date );
		$this->assertStringContainsString( '17:00:00', $end_date );
	}

	/**
	 * It should handle show_not_going option when new views enabled
	 *
	 * @test
	 */
	public function should_handle_show_not_going_option_when_new_views_enabled(): void {
		// Skip if new views are not enabled
		if ( ! tribe_tickets_rsvp_new_views_is_enabled() ) {
			$this->markTestSkipped( 'New RSVP views not enabled' );
		}

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$ticket = new Ticket_Object();
		$ticket->name = 'Not Going Test';
		$ticket->description = 'Test';
		$ticket->price = 10;
		$ticket->show_description = 'yes';

		$raw_data = [
			'tribe-ticket' => [
				'not_going' => 'yes',
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $event_id, $ticket, $raw_data );

		$show_not_going = get_post_meta( $ticket_id, '_tribe_rsvp_show_not_going', true );
		$this->assertEquals( 'yes', $show_not_going );
	}
}
