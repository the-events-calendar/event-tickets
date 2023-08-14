<?php

namespace TEC\Tickets\Flexible_Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;

class BaseTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;

	protected string $controller_class = Base::class;

	/**
	 * @before
	 */
	public function ensure_ticketables(): void {
		$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable_post_types[] = 'post';
		$ticketable_post_types[] = TEC::POSTTYPE;
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
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
		$series   = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$pass_1   = $this->create_tc_series_pass( $series, 23 )->ID;
		$pass_2   = $this->create_tc_series_pass( $series, 89 )->ID;
		$event    = tribe_events()->set_args( [
			'title'      => 'Event',
			'status'     => 'publish',
			'start_date' => '2020-01-01 00:00:00',
			'end_date'   => '2020-01-01 00:00:00',
			'series'     => $series,
		] )->create()->ID;
		$ticket_1 = $this->create_tc_ticket( $event, 23 );
		$ticket_2 = $this->create_tc_ticket( $event, 89 );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $event );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $event, $ticket_1, $ticket_2, $series, $pass_1, $pass_2 ],
			[ '{{event_id}}', '{{ticket_1}}', '{{ticket_2}}', '{{series_id}}', '{{pass_1}}', '{{pass_2}}' ],
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
}
