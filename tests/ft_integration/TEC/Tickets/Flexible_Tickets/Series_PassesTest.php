<?php

namespace TEC\Tickets\Flexible_Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Ticket_Data_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Tickets as Tickets;

class Series_PassesTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Series_Pass_Factory;
	use Ticket_Data_Factory;

	protected $controller_class = Series_Passes::class;

	/**
	 * @before
	 */
	public function ensure_series_ticketables(): void {
		$ticketable_post_types   = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
	}

	public function post_not_series_provider(): Generator {
		yield 'empty post ID' => [
			function () {
				return [ '', false ];
			}
		];

		yield 'post ID is not a number' => [
			function () {
				return [ 'foo', false ];
			}
		];

		yield 'post ID is 0' => [
			function () {
				return [ 0, false ];
			}
		];

		yield 'post ID is negative' => [
			function () {
				return [ - 1, false ];
			}
		];

		yield 'post ID is not a series' => [
			function () {
				return [ static::factory()->post->create(), false ];
			}
		];

		yield 'post ID is a Single Event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '2020-01-01 10:00:00',
					'end_date'   => '2020-01-01 11:00:00',
					'timezone'   => 'America/New_York',
					'duration'   => 1,
					'status'     => 'publish',
				] )->create();

				return [ $event->ID, false ];
			}
		];

		yield 'post ID is a Recurring Event' => [
			function () {
				$event = tribe_events()->set_args( [
					'title'      => 'Test Event',
					'start_date' => '2020-01-01 10:00:00',
					'end_date'   => '2020-01-01 11:00:00',
					'timezone'   => 'America/New_York',
					'duration'   => 1,
					'status'     => 'publish',
					'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3'
				] )->create();

				return [ $event->ID, false ];
			}
		];

		yield 'post ID is a Series, no ticket providers' => [
			function () {
				$this->set_class_fn_return( Tickets::class, 'modules', [] );

				return [
					static::factory()->post->create( [
						'post_type' => Series_Post_Type::POSTTYPE,
					] ),
					true
				];
			}
		];

		yield 'post ID is a Series' => [
			function () {
				return [
					static::factory()->post->create( [
						'post_type' => Series_Post_Type::POSTTYPE,
					] ),
					true
				];
			}
		];
	}

	/**
	 * It should correctly render the form toggle
	 *
	 * @test
	 * @dataProvider post_not_series_provider
	 */
	public function should_correctly_render_the_form_toggle( Closure $fixture ): void {
		[ $post_id, $expect_output ] = $fixture();

		$controller = $this->make_controller();

		ob_start();
		$controller->render_form_toggle( $post_id );
		$html = ob_get_clean();
		$html = str_replace( $post_id, '{{post_id}}', $html );

		if ( $expect_output ) {
			$this->assertMatchesHtmlSnapshot( $html );
		} else {
			$this->assertEmpty( $html );
		}
	}

	private function capacity_payload( string $payload ): array {
		// Examples of the possible payloads sent over to represent the capacity.
		$map = [
			'unlimited'  => [
				'mode' => '',
			],
			'global_100' => [
				'mode'           => 'global',
				'event_capacity' => '100',
				'capacity'       => '',
			],
			'global_89'  => [
				'mode'           => 'global',
				'event_capacity' => '89',
				'capacity'       => '',
			],
			'capped_23'  => [
				'mode'           => 'capped',
				'event_capacity' => '100',
				'capacity'       => '23',
			],
			'capped_89'  => [
				'mode'           => 'capped',
				'event_capacity' => '100',
				'capacity'       => '89',
			],
			'own_23'     => [
				'mode'     => 'own',
				'capacity' => '23',
			],
			'own_89'     => [
				'mode'     => 'own',
				'capacity' => '89',
			],
			'own_99'     => [
				'mode'     => 'own',
				'capacity' => '99',
			],
		];

		return $map[ $payload ];
	}

	/**
	 * It should reorder series content once
	 *
	 * @test
	 */
	public function should_reorder_series_content_once(): void {
		$controller = $this->make_controller();

		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );

		$this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] );

		$controller->register();

		$this->assertSame( 0, has_filter( 'the_content', [ $controller, 'reorder_series_content' ] ) );
		$this->assertSame( 'test content', $controller->reorder_series_content( 'test content' ) );
		$this->assertSame( false, has_filter( 'the_content', [ $controller, 'reorder_series_content' ] ) );
	}

	/**
	 * It should set pass end date time dynamically
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_series
	 *
	 * @test
	 */
	public function should_set_pass_end_date_time_dynamically(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );

		// Create a Series Pass attached to the Series with no end date and time set.
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
			'ticket_end_date' => '',
			'ticket_end_time' => '',
		] )->ID;

		// End date and time should not be set since there are no Events attached to the Series to pull those from.
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should now be set since an Event is attached to the Series.
		$this->assertEquals( '2020-01-05', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '12:00:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Add a new Single Event to the Series that happens after the last Occurrence of the Recurring Event.
		tribe_events()->set_args( [
			'title'      => 'Single Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series_id,
		] )->create()->ID;

		// End date and time should have been updated to the start date and time of the new Single Event.
		$this->assertEquals( '2020-02-11', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '17:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Adding another recurring Event whose last Occurrence is before the current last end date will not change the end date.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 2',
			'status'     => 'publish',
			'start_date' => '2020-01-10 21:00:00',
			'end_date'   => '2020-01-10 22:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should not have been updated.
		$this->assertEquals( '2020-02-11', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '17:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );
	}

	/**
	 * It should not set pass end date and time dynamically when explicitly set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_series
	 *
	 * @test
	 */
	public function should_not_set_pass_end_date_and_time_dynamically_when_explicitly_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );

		// Create a Series Pass attached to the Series with no end date and time set.
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
			'ticket_end_date' => '2022-10-13',
			'ticket_end_time' => '18:30:00',
		] )->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '18:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '18:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Add a new Single Event to the Series that happens after the last Occurrence of the Recurring Event.
		tribe_events()->set_args( [
			'title'      => 'Single Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series_id,
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '18:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Adding another recurring Event whose last Occurrence is before the current last end date.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 2',
			'status'     => 'publish',
			'start_date' => '2020-01-10 21:00:00',
			'end_date'   => '2020-01-10 22:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '18:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );
	}

	/**
	 * It should not set end time dynamically if end date is manually set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_series
	 *
	 * @test
	 */
	public function should_not_set_end_time_dynamically_if_end_date_is_manually_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );

		// Create a Series Pass attached to the Series with no end date and time set.
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
			'ticket_end_date' => '2022-10-13',
			'ticket_end_time' => '',
		] )->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Add a new Single Event to the Series that happens after the last Occurrence of the Recurring Event.
		tribe_events()->set_args( [
			'title'      => 'Single Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series_id,
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Adding another recurring Event whose last Occurrence is before the current last end date.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 2',
			'status'     => 'publish',
			'start_date' => '2020-01-10 21:00:00',
			'end_date'   => '2020-01-10 22:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date and time should be the ones set when the ticket has been created.
		$this->assertEquals( '2022-10-13', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '0', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );
	}

	/**
	 * It should set the end date and time dynamically even when end time manually set.
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::update_passes_for_series
	 *
	 * @test
	 */
	public function should_set_end_date_and_time_dynamically_even_when_end_time_manually_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		// Create a Series.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );

		// Create a Series Pass attached to the Series with no end date and time set.
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
			'ticket_end_date' => '',
			'ticket_end_time' => '18:30:00',
		] )->ID;

		// End date should be empty, end time should be the manually set one.
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date should be set from the current last, end time should be the manually set one.
		$this->assertEquals( '2020-01-05', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '12:00:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Add a new Single Event to the Series that happens after the last Occurrence of the Recurring Event.
		tribe_events()->set_args( [
			'title'      => 'Single Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series_id,
		] )->create()->ID;

		// End date should be set from the current last, end time should be the manually set one.
		$this->assertEquals( '2020-02-11', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '17:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );

		// Adding another recurring Event whose last Occurrence is before the current last end date.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 2',
			'status'     => 'publish',
			'start_date' => '2020-01-10 21:00:00',
			'end_date'   => '2020-01-10 22:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		// End date should be set from the current last, end time should be the manually set one.
		$this->assertEquals( '2020-02-11', get_post_meta( $series_pass_id, '_ticket_end_date', true ) );
		$this->assertEquals( '17:30:00', get_post_meta( $series_pass_id, '_ticket_end_time', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_date', true ) );
		$this->assertEquals( '1', get_post_meta( $series_pass_id, '_dynamic_end_time', true ) );
	}

	public function panel_data_provider(): \Generator {
		yield 'no ticket' => [
			function (): array {
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );

				return [ $post_id, null ];
			},
		];

		yield 'series with series pass, set dates' => [
			function (): array {
				$post_id        = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$series_pass_id = $this->create_tc_series_pass( $post_id, 2389, [
					'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
					'ticket_end_date' => '2022-03-04',
					'ticket_end_time' => '12:00:00',
				] )->ID;

				return [ $post_id, $series_pass_id ];
			},
		];

		yield 'series with series pass, end date not set' => [
			function (): array {
				$post_id        = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$series_pass_id = $this->create_tc_series_pass( $post_id, 2389, [
					'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
					'ticket_end_date' => '2022-03-04',
					'ticket_end_time' => '12:00:00',
				] )->ID;
				delete_post_meta( $series_pass_id, '_ticket_end_date' );

				return [ $post_id, $series_pass_id ];
			},
		];

		yield 'series with series pass, end time not set' => [
			function (): array {
				$post_id        = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$series_pass_id = $this->create_tc_series_pass( $post_id, 2389, [
					'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
					'ticket_end_date' => '2022-03-04',
					'ticket_end_time' => '12:00:00',
				] )->ID;
				delete_post_meta( $series_pass_id, '_ticket_end_time' );

				return [ $post_id, $series_pass_id ];
			},
		];

		yield 'series with series pass, end date and time not set' => [
			function (): array {
				$post_id        = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$series_pass_id = $this->create_tc_series_pass( $post_id, 2389, [
					'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
					'ticket_end_date' => '2022-03-04',
					'ticket_end_time' => '12:00:00',
				] )->ID;
				delete_post_meta( $series_pass_id, '_ticket_end_date' );
				delete_post_meta( $series_pass_id, '_ticket_end_time' );

				return [ $post_id, $series_pass_id ];
			},
		];
	}

	/**
	 * It should update panel data correctly
	 *
	 * @test
	 * @dataProvider panel_data_provider
	 */
	public function should_update_panel_data_correctly( Closure $fixture ): void {
		[ $post_id, $ticket_id ] = $fixture();

		$controller = $this->make_controller();
		$data       = $controller->update_panel_data( [], $post_id, $ticket_id );

		$this->assertMatchesCodeSnapshot( $data );
	}
}
