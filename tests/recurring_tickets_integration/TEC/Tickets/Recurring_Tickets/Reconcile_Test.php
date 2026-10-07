<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Events\Custom_Tables\V1\Updates\Controller as Updates_Controller;
use TEC\Events_Pro\Custom_Tables\V1\Event_Factory;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A date that gets a new ID on the same day keeps its rows and attendees.
 */
class Reconcile_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * The first day of the events.
	 *
	 * @var string
	 */
	private string $day = '';

	/**
	 * @before
	 */
	public function set_up_event_day(): void {
		( new Tickets() )->empty_table();
		$this->day = gmdate( 'Y-m-d', strtotime( '+10 days' ) );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_keep_each_dates_rows_and_attendees_when_the_start_time_changes(): void {
		$event     = $this->create_event( $this->daily( '10:00:00' ) );
		$template  = $this->create_tc_ticket( $event, 10 );
		$before    = $this->rows_by_day( $template );
		$sold      = $before[ $this->day_after( 1 ) ];
		$order     = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $sold->id ) => 2 ] );
		$pending   = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $sold->id ) => 1 ], Pending::SLUG );
		$attendees = $this->attendees_of( $order->ID );
		$this->assertCount( 2, $attendees );

		$this->change_for_all_events( $event, $this->daily( '11:00:00' ) );

		$after = $this->rows_by_day( $template );
		$this->assertSame( array_keys( $before ), array_keys( $after ), 'Every day keeps a row.' );
		foreach ( $after as $day => $row ) {
			$this->assertSame( (int) $before[ $day ]->id, (int) $row->id, "The row of {$day} keeps its ID." );
			$this->assertNotSame( (int) $before[ $day ]->occurrence_id, (int) $row->occurrence_id, "The row of {$day} points to the new date." );
			$this->assertStringContainsString( '11:00:00', $this->datetime( $row->occurrence_start ) );
		}
		// Two completed and one pending.
		$this->assertSame( 3, (int) $after[ $this->day_after( 1 ) ]->sales );

		$new_date = (int) tribe( \TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator::class )->provide_id( (int) $after[ $this->day_after( 1 ) ]->occurrence_id );
		foreach ( $attendees as $attendee ) {
			$this->assertSame( (string) $new_date, get_post_meta( $attendee, '_tec_tickets_commerce_event', true ) );
		}
		foreach ( [ $order->ID, $pending->ID ] as $order_id ) {
			$this->assertSame( [ (string) $new_date ], get_post_meta( $order_id, '_tec_tc_order_events_in_order' ), 'An order, pending or not, follows its date.' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_touch_only_the_attendees_of_the_repointed_rows(): void {
		$other     = tribe_events()->set_args( [ 'title' => 'Other', 'status' => 'publish', 'start_date' => '+1 week 10:00:00', 'end_date' => '+1 week 12:00:00' ] )->create()->ID;
		$order     = $this->create_row_order( [ $this->create_tc_ticket( $other, 5 ) => 1 ] );
		$bystander = $this->attendees_of( $order->ID )[0];
		$event     = $this->create_event( $this->daily( '10:00:00' ) );
		$this->create_tc_ticket( $event, 10 );
		$touched = [];
		add_filter(
			'update_post_metadata',
			static function ( $check, $object_id, $meta_key ) use ( &$touched ) {
				if ( in_array( $meta_key, [ '_tec_tickets_commerce_event', '_tec_tc_order_events_in_order' ], true ) ) {
					$touched[] = (int) $object_id;
				}

				return $check;
			},
			10,
			3
		);

		$this->change_for_all_events( $event, $this->daily( '11:00:00' ) );

		$this->assertNotContains( $bystander, $touched );
		$this->assertNotContains( $order->ID, $touched );
	}

	/**
	 * @test
	 */
	public function it_should_not_guess_on_a_day_with_two_dates(): void {
		$event    = $this->create_event( $this->daily( '10:00:00' )->with_date_recurrence( $this->day, false, '15:00:00', '16:00:00' ) );
		$template = $this->create_tc_ticket( $event, 10 );
		$on_day   = $this->rows_on( $template, $this->day );
		$this->assertCount( 2, $on_day );
		$next_day  = (int) $this->rows_on( $template, $this->day_after( 1 ) )[0]->id;
		$order     = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $on_day[0]->id ) => 1 ] );
		$attendees = $this->attendees_of( $order->ID );
		$old_date  = get_post_meta( $attendees[0], '_tec_tickets_commerce_event', true );

		// Both of the first day's dates move.
		$this->change_for_all_events( $event, $this->daily( '11:00:00' )->with_date_recurrence( $this->day, false, '16:00:00', '17:00:00' ) );

		$ids = array_map( static fn( $row ) => (int) $row->id, $this->rows_on( $template, $this->day ) );
		$this->assertNotContains( (int) $on_day[0]->id, $ids, 'The sold row is not re-pointed.' );
		$this->assertNotContains( (int) $on_day[1]->id, $ids );
		$this->assertSame( $old_date, get_post_meta( $attendees[0], '_tec_tickets_commerce_event', true ), 'Its attendee is stranded.' );
		$this->assertSame( [ $next_day ], array_map( static fn( $row ) => (int) $row->id, $this->rows_on( $template, $this->day_after( 1 ) ) ), 'Other days are re-pointed.' );
	}

	/**
	 * @param string $time The start time of every date.
	 *
	 * @return Recurrence A daily recurrence of three dates from the first day, two hours long.
	 */
	private function daily( string $time ): Recurrence {
		$end = gmdate( 'H:i:s', strtotime( "{$this->day} {$time} +2 hours" ) );

		return ( new Recurrence() )
			->with_start_date( "{$this->day} {$time}" )
			->with_end_date( "{$this->day} {$end}" )
			->with_daily_recurrence()
			->with_end_after( 3 );
	}

	/**
	 * @param Recurrence $recurrence The recurrence.
	 *
	 * @return int The event.
	 */
	private function create_event( Recurrence $recurrence ): int {
		return ( new Event_Factory() )->create_with_recurrence(
			[
				'post_status' => 'publish',
				'meta_input'  => $this->date_meta( $recurrence ),
			],
			$recurrence->to_event_recurrence()
		);
	}

	/**
	 * Saves new dates and a new rule for all events, as the editors do, then what the end of the request does.
	 *
	 * @param int        $event      The event.
	 * @param Recurrence $recurrence The new recurrence.
	 *
	 * @return void
	 */
	private function change_for_all_events( int $event, Recurrence $recurrence ): void {
		foreach ( $this->date_meta( $recurrence ) as $key => $value ) {
			update_post_meta( $event, $key, $value );
		}
		update_post_meta( $event, '_EventRecurrence', $recurrence->to_event_recurrence() );
		tribe( Updates_Controller::class )->commit_updates();
	}

	/**
	 * @param Recurrence $recurrence The recurrence.
	 *
	 * @return array<string,string> The event's date meta for it, in UTC as the tests' site is.
	 */
	private function date_meta( Recurrence $recurrence ): array {
		$start = $recurrence->get_dtstart()->format( 'Y-m-d H:i:s' );
		$end   = $recurrence->get_dtend()->format( 'Y-m-d H:i:s' );

		return [
			'_EventStartDate'    => $start,
			'_EventEndDate'      => $end,
			'_EventStartDateUTC' => $start,
			'_EventEndDateUTC'   => $end,
			'_EventTimezone'     => 'UTC',
			'_EventDuration'     => (string) ( strtotime( $end ) - strtotime( $start ) ),
		];
	}

	/**
	 * @param int $template The template.
	 *
	 * @return array<string,object> The template's rows, by local day; one per day.
	 */
	private function rows_by_day( int $template ): array {
		$rows = [];
		foreach ( tribe( Rows::class )->get_by_template( $template ) as $row ) {
			$rows[ substr( $this->datetime( $row->occurrence_start ), 0, 10 ) ] = $row;
		}
		ksort( $rows );

		return $rows;
	}

	/**
	 * @param int    $template The template.
	 * @param string $day      The day, `Y-m-d`.
	 *
	 * @return object[] The template's rows on that day, by start.
	 */
	private function rows_on( int $template, string $day ): array {
		$rows = array_values(
			array_filter(
				tribe( Rows::class )->get_by_template( $template ),
				fn( $row ) => 0 === strpos( $this->datetime( $row->occurrence_start ), $day )
			)
		);
		usort( $rows, fn( $a, $b ) => strcmp( $this->datetime( $a->occurrence_start ), $this->datetime( $b->occurrence_start ) ) );

		return $rows;
	}

	/**
	 * @param int $days How many days after the first.
	 *
	 * @return string The day, `Y-m-d`.
	 */
	private function day_after( int $days ): string {
		return gmdate( 'Y-m-d', strtotime( "{$this->day} +{$days} days" ) );
	}

	/**
	 * @param int $order_id The order.
	 *
	 * @return int[] The order's attendees.
	 */
	private function attendees_of( int $order_id ): array {
		return array_map( 'intval', get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order_id, 'post_status' => 'any', 'fields' => 'ids' ] ) );
	}

	/**
	 * @param mixed $date A date as the model holds it.
	 *
	 * @return string The date as `Y-m-d H:i:s`.
	 */
	private function datetime( $date ): string {
		return $date instanceof \DateTimeInterface ? $date->format( 'Y-m-d H:i:s' ) : (string) $date;
	}
}
