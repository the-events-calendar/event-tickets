<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Codeception\TestCase\WPTestCase;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Admin__Move_Tickets as Move_Attendees;
use Tribe__Tickets__Tickets as Tickets;

/**
 * An event in a Series lists the Series Passes among its tickets, so attendees can be moved to one through the event.
 */
class Move_Attendees_Test extends WPTestCase {
	use Series_Pass_Factory;
	use Order_Maker;

	/**
	 * @test
	 */
	public function it_should_move_attendees_to_a_series_pass_listed_on_an_event_of_the_series(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$series_id      = static::factory()->post->create( [ 'post_type' => Series_Post_Type::POSTTYPE ] );
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_event   = tribe_events()->set_args(
			[
				'title'      => 'Series event',
				'status'     => 'publish',
				'start_date' => '+1 day',
				'end_date'   => '+1 day 2 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$single_event   = tribe_events()->set_args(
			[
				'title'      => 'Single event',
				'status'     => 'publish',
				'start_date' => '+2 days',
				'end_date'   => '+2 days 2 hours',
			]
		)->create()->ID;
		$single_ticket  = $this->create_tc_ticket( $single_event, 1 );
		$this->create_order( [ $single_ticket => 1 ] );
		$attendee_id = tribe_attendees()->where( 'event', $single_event )->first_id();

		$this->assertContains(
			$series_pass_id,
			wp_list_pluck( Tickets::get_event_tickets( $series_event ), 'ID' ),
			'The move dialog lists the Series Pass among the tickets of an event of the Series.'
		);

		$moved = tribe( Move_Attendees::class )->move_tickets( [ $attendee_id ], $series_pass_id, $single_event, $series_event );

		$this->assertSame( 1, $moved );
	}

	/**
	 * @test
	 */
	public function it_should_keep_series_pass_clones_when_the_target_ticket_is_not_on_the_target_post(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$series_id      = static::factory()->post->create( [ 'post_type' => Series_Post_Type::POSTTYPE ] );
		$series_pass_id = $this->create_tc_series_pass( $series_id )->ID;
		$series_event   = tribe_events()->set_args(
			[
				'title'      => 'Series event',
				'status'     => 'publish',
				'start_date' => '+1 day',
				'end_date'   => '+1 day 2 hours',
				'series'     => $series_id,
			]
		)->create()->ID;
		$single_event   = tribe_events()->set_args(
			[
				'title'      => 'Single event',
				'status'     => 'publish',
				'start_date' => '+2 days',
				'end_date'   => '+2 days 2 hours',
			]
		)->create()->ID;
		$single_ticket  = $this->create_tc_ticket( $single_event, 1 );
		$this->create_order( [ $series_pass_id => 1 ] );
		$attendee_id = tribe_attendees()->where( 'event', $series_id )->first_id();
		$clone_id    = tribe( Attendees::class )->clone_attendee_to_event( $attendee_id, $series_event );

		$moved = tribe( Move_Attendees::class )->move_tickets( [ $attendee_id ], $single_ticket, $series_id, $series_event );

		$this->assertSame( 0, $moved );
		$this->assertInstanceOf( \WP_Post::class, get_post( $clone_id ), 'A refused move must not delete the Series Pass clones.' );
	}
}
