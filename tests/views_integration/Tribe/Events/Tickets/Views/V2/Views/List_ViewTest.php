<?php

namespace Tribe\Events\Tickets\Views\V2\Views;

use Tribe\Events\Views\V2\View;
use Tribe\Events\Views\V2\Views\List_View;
use Tribe\Test\Products\WPBrowser\Views\V2\ViewTestCase;

class List_ViewTest extends ViewTestCase {

	/**
	 * Scaffolds some events, recurring and not recurring, to test the List view.
	 *
	 * Events are "past" or "upcoming" in the context of the test case mock date.
	 *
	 * @since TBD
	 *
	 * @return array
	 * @throws \Tribe__Repository__Usage_Error
	 */
	protected function given_some_recurring_events(): array {
		$paris    = new \DateTimeZone( 'Europe/Paris' );
		$one_day  = new \DateInterval( 'P1D' );
		$two_days = new \DateInterval( 'P2D' );
		$now      = new \DateTimeImmutable( $this->mock_date_value, $paris );
		$format   = 'Y-m-d H:i:s';
		$tomorrow = $now->add( $one_day );

		$upcoming_single_1           = tribe_events()->set_args( [
			'start_date' => $tomorrow->setTime( 10, 0 ),
			'timezone'   => 'Europe/Paris',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'title'      => 'Upcoming Single Event 1',
			'status'     => 'publish',
		] )->create();
		$upcoming_single_2           = tribe_events()->set_args( [
			'start_date' => $tomorrow->add( $two_days )->setTime( 13, 0 ),
			'timezone'   => 'Europe/Paris',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'title'      => 'Upcoming Single Event 2',
			'status'     => 'publish',
		] )->create();
		$past_single_1               = tribe_events()->set_args( [
			'start_date' => $now->sub( $two_days )->setTime(14,0),
			'timezone'   => 'Europe/Paris',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'title'      => 'Past Single Event 1',
			'status'     => 'publish',
		] )->create();
		$past_single_2               = tribe_events()->set_args( [
			'start_date' => $now->sub( $two_days )->sub( $two_days )->setTime(15,0),
			'timezone'   => 'Europe/Paris',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'title'      => 'Past Single Event 2',
			'status'     => 'publish',
		] )->create();
		$recurring_starting_tomorrow = tribe_events()->set_args( [
			'start_date' => $tomorrow,
			'timezone'   => 'Europe/Paris',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'title'      => 'Recurring starting tomorrow',
			'status'     => 'publish',
			'recurrence' => [
				'rules'       =>
					[
						0 =>
							[
								'type'           => 'Custom',
								'custom'         =>
									[
										'interval'  => '1',
										'same-time' => 'yes',
										'type'      => 'Daily',
									],
								'end-type'       => 'After',
								'end-count'      => '5',
								'EventStartDate' => $tomorrow->setTime( 9, 0 )->format( $format ),
								'EventEndDate'   => $tomorrow->setTime( 11, 0 )->format( $format ),
							],
					],
				'exclusions'  =>
					[
					],
				'description' => null,
			]
		] )->create();

		$tomorrow_series_ids = tribe_events()->where( 'in_series', $recurring_starting_tomorrow )
		                                     ->order_by( 'event_date', 'ASC' )
		                                     ->get_ids();
		$this->assertCount( 5, $tomorrow_series_ids );
		codecept_debug( 'Tomorrow series start dates: '
		                . json_encode( $this->fetch_event_dates_from_db( $tomorrow_series_ids, '_EventStartDate' ), JSON_PRETTY_PRINT ), JSON_PRETTY_PRINT );

		$two_days_ago = $now->sub( $two_days )->setTime( 11, 30 );
		$recurring_started_2d_ago = tribe_events()->set_args( [
			'start_date' => $two_days_ago,
			'timezone'   => 'Europe/Paris',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'title'      => 'Recurring starting 2 days ago',
			'status'     => 'publish',
			'recurrence' => [
				'rules'       =>
					[
						0 =>
							[
								'type'           => 'Custom',
								'custom'         =>
									[
										'interval'  => '1',
										'same-time' => 'yes',
										'type'      => 'Daily',
									],
								'end-type'       => 'After',
								'end-count'      => '5',
								'EventStartDate' => $two_days_ago->setTime( 9, 0 )->format( $format ),
								'EventEndDate'   => $two_days_ago->setTime( 11, 0 )->format( $format ),
							],
					],
				'exclusions'  =>
					[
					],
				'description' => null,
			]
		] )->create();

		$two_days_ago_series_ids = tribe_events()->where( 'in_series', $recurring_started_2d_ago )
		                                         ->order_by( 'event_date', 'ASC' )
		                                         ->get_ids();
		$this->assertCount( 5, $two_days_ago_series_ids );
		codecept_debug( 'Two days ago series start dates: '
		                . json_encode( $this->fetch_event_dates_from_db( $two_days_ago_series_ids, '_EventStartDate' ), JSON_PRETTY_PRINT ) );

		// Sanity check
		$this->assertEquals( 14, tribe_events()->found() );
		$this->assertEquals( 10, tribe_events()->where( 'ends_after', $this->mock_date_value )->count() );

		$events                          = array_merge(
			[ $upcoming_single_1->ID, $upcoming_single_2->ID ],
			$tomorrow_series_ids,
			$two_days_ago_series_ids
		);

		return $events;
	}

	/**
	 * It should show all recurring event instances by default
	 *
	 * @test
	 */
	public function should_show_all_recurring_event_instances_by_default() {
		$events       = $this->given_some_recurring_events();
		$expected_ids = tribe_events()
			->where( 'ends_after', $this->mock_date_value )
			->order_by( 'event_date', 'ASC' )
			->get_ids();

		codecept_debug( 'Expected: ' . json_encode( $this->fetch_event_dates_from_db( $expected_ids, '_EventStartDate' ), JSON_PRETTY_PRINT ) );

		$this->remap_posts( $events, [
			'events/featured/1.json',
			'events/single/1.json',

			'events/recurring/series1-1.json',
			'events/recurring/series1-2.json',
			'events/recurring/series1-3.json',
			'events/recurring/series1-4.json',
			'events/recurring/series1-5.json',

			'events/recurring/series2-1.json',
			'events/recurring/series2-2.json',
			'events/recurring/series2-3.json',
			'events/recurring/series2-4.json',
			'events/recurring/series2-5.json',
		] );

		$list_view = View::make( List_View::class );
		$list_view->set_context( $this->get_mock_context() );

		// Let's make sure to return the mock date when getting events before of after "now".
		add_filter( 'tribe_events_views_v2_view_repository_args', function ( array $args ) {
			foreach ( $args as $key => &$value ) {
				if ( $value === 'now' ) {
					$value = $this->mock_date_value;
				}
			}

			return $args;
		} );

		$html = $list_view->get_html();

		// Let's make sure the View is displaying what events we expect it to display.
		$found_post_ids = $list_view->found_post_ids();

		$found_dates = $this->fetch_event_dates_from_db( $found_post_ids, '_EventStartDate' );
		codecept_debug( 'Found post IDs dates: ' . json_encode( $found_dates, JSON_PRETTY_PRINT ) );

		$this->assertEquals(
			$expected_ids,
			$found_post_ids
		);

		$this->assertMatchesSnapshot( $html );
	}
	/**
	 * It should hide recurring event instances after first if toggle on
	 *
	 * @test
	 */
	public function should_hide_recurring_event_instances_after_first_if_toggle_on() {
		$events       = $this->given_some_recurring_events();
		$expected_ids = tribe_events()
			->where( 'ends_after', $this->mock_date_value )
			->order_by( 'event_date', 'ASC' )
			->where( 'hide_subsequent_recurrences', true )
			->get_ids();

		codecept_debug( 'Expected: ' . json_encode( $this->fetch_event_dates_from_db( $expected_ids, '_EventStartDate' ), JSON_PRETTY_PRINT ) );

		codecept_debug( 'Event IDs: ' . json_encode( array_map( static function ( $event ) {
				return $event instanceof \WP_Post ? $event->ID : $event;
			}, $events ) ) );
		$this->remap_posts( $events, [
			'events/featured/1.json',
			'events/single/1.json',

			'events/recurring/series1-1.json',
			'events/recurring/series1-2.json',
			'events/recurring/series1-3.json',
			'events/recurring/series1-4.json',
			'events/recurring/series1-5.json',

			'events/recurring/series2-1.json',
			'events/recurring/series2-2.json',
			'events/recurring/series2-3.json',
			'events/recurring/series2-4.json',
			'events/recurring/series2-5.json',
		] );

		$list_view = View::make( List_View::class );
		$list_view->set_context( $this->get_mock_context( [ 'hide_subsequent_recurrences' => true ] ) );

		// Let's make sure to return the mock date when getting events before of after "now".
		add_filter( 'tribe_events_views_v2_view_repository_args', function ( array $args ) {
			foreach ( $args as $key => &$value ) {
				if ( $value === 'now' ) {
					$value = $this->mock_date_value;
				}
			}

			return $args;
		} );

		$html = $list_view->get_html();

		// Let's make sure the View is displaying what events we expect it to display.
		$found_post_ids = $list_view->found_post_ids();

		$found_dates = $this->fetch_event_dates_from_db( $found_post_ids, '_EventStartDate' );
		codecept_debug( 'Found post IDs dates: ' . json_encode( $found_dates, JSON_PRETTY_PRINT ) );
		codecept_debug( 'Remaps: ' . json_encode( array_combine(
				$found_post_ids,
				array_map( static function ( $id ) {
					return get_post( $id )->ID;
				}, $found_post_ids )
			), JSON_PRETTY_PRINT ) );

		$this->assertEquals(
			$expected_ids,
			$found_post_ids
		);

		$this->assertMatchesSnapshot( $html );
	}
}
