<?php

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Event;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Series\Relationship;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Ticket_Data_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Admin__Views__Ticketed as Ticketed;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets as Tickets;

class Series_PassesTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Series_Pass_Factory;
	use Ticket_Data_Factory;
	use Attendee_Maker;

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
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_series
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
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_series
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
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_series
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
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_pass_meta
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_event
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes::update_passes_for_series
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

	public function ticket_type_provider(): array {
		return [
			'default'     => [ 'default' ],
			'series pass' => [ Series_Passes::TICKET_TYPE ],
		];
	}

	/**
	 * It should filter panel labels during panel rendering
	 *
	 * @test
	 * @dataProvider ticket_type_provider
	 */
	public function should_filter_panel_labels_correctly( string $ticket_type ): void {
		$controller = $this->make_controller();
		$controller->register();

		$post      = static::factory()->post->create_and_get( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket_id = $this->create_tc_series_pass( $post->ID, 2389, [
			'tribe-ticket'    => $this->capacity_payload( 'unlimited' ),
			'ticket_end_date' => '2022-03-04',
			'ticket_end_time' => '12:00:00',
		] )->ID;

		do_action( 'tec_tickets_panels_before', $post, $ticket_id, $ticket_type );

		$labels = [
			'during_panel_rendering' => [],
			'after_panel_rendering'  => [],
		];

		$labels['during_panel_rendering'] = [
			'ticket_label_plural_lowercase_no_context'         => tribe_get_ticket_label_plural_lowercase(),
			'ticket_label_singular_lowercase_no_context'       => tribe_get_ticket_label_singular_lowercase(),
			'ticket_label_plural_no_context'                   => tribe_get_ticket_label_plural(),
			'ticket_label_singular_no_context'                 => tribe_get_ticket_label_singular(),
			'ticket_label_plural_lowercase_metabox_capacity'   => tribe_get_ticket_label_plural_lowercase( 'metabox_capacity' ),
			'ticket_label_singular_lowercase_metabox_capacity' => tribe_get_ticket_label_singular_lowercase( 'metabox_capacity' ),
			'ticket_label_plural_metabox_capacity'             => tribe_get_ticket_label_plural( 'metabox_capacity' ),
			'ticket_label_singular_metabox_capacity'           => tribe_get_ticket_label_singular( 'metabox_capacity' ),
		];

		// The filtering of the labels should stop here.
		do_action( 'tec_tickets_panels_after', $post, $ticket_id, $ticket_type );

		$labels['after_panel_rendering'] = [
			'ticket_label_plural_lowercase_no_context'         => tribe_get_ticket_label_plural_lowercase(),
			'ticket_label_singular_lowercase_no_context'       => tribe_get_ticket_label_singular_lowercase(),
			'ticket_label_plural_no_context'                   => tribe_get_ticket_label_plural(),
			'ticket_label_singular_no_context'                 => tribe_get_ticket_label_singular(),
			'ticket_label_plural_lowercase_metabox_capacity'   => tribe_get_ticket_label_plural_lowercase( 'metabox_capacity' ),
			'ticket_label_singular_lowercase_metabox_capacity' => tribe_get_ticket_label_singular_lowercase( 'metabox_capacity' ),
			'ticket_label_plural_metabox_capacity'             => tribe_get_ticket_label_plural( 'metabox_capacity' ),
			'ticket_label_singular_metabox_capacity'           => tribe_get_ticket_label_singular( 'metabox_capacity' ),
		];

		$this->assertMatchesStringSnapshot( var_export( $labels, true ) );
	}

	/**
	 * It should correctly set dynamic end meta when ticket save deletes empty meta
	 *
	 * Ticket Commerce is an example of a provider that will delete empty meta when saving a ticket.
	 * The Controller hooks on create/insert post and meta operations that will come **before** the
	 * meta is deleted. This test ensures the Controller jumps correctly into the ticket save flow.
	 *
	 * @test
	 */
	public function should_correctly_set_dynamic_end_meta_when_ticket_save_deletes_empty_meta(): void {
		$controller = $this->make_controller();
		$controller->register();

		// Create a Series.
		$series = static::factory()->post->create_and_get( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Create a Single Event attached to Series.
		$event = tribe_events()->set_args( [
			'title'      => 'Single Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series->ID,
		] )->create();
		// Insert a ticket with no start and end date/time.
		$ticket_id = $this->create_tc_series_pass( $series->ID, 2389, [
			'tribe-ticket'      => $this->capacity_payload( 'unlimited' ),
			'ticket_start_date' => '',
			'ticket_start_time' => '',
			'ticket_end_date'   => '',
			'ticket_end_time'   => '',
		] )->ID;

		$this->assertEquals( '2020-02-11', get_post_meta( $ticket_id, '_ticket_end_date', true ) );
		$this->assertEquals( '17:30:00', get_post_meta( $ticket_id, '_ticket_end_time', true ) );
	}

	/**
	 * It should add and update the Series ticket provider to the Series Events
	 *
	 * @test
	 */
	public function should_add_and_update_the_series_ticket_provider_to_the_series_events(): void {
		$series  = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$event_1 = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series
		] )->create();
		$event_2 = tribe_events()->set_args( [
			'title'      => 'Event 2',
			'status'     => 'publish',
			'start_date' => '2020-02-12 17:30:00',
			'end_date'   => '2020-02-12 18:00:00',
			'series'     => $series
		] )->create();

		$this->assertEquals( '', get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( '', get_post_meta( $event_1->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( '', get_post_meta( $event_2->ID, '_tribe_default_ticket_provider', true ) );

		// Build and register the Controller.
		$controller = $this->make_controller()->register();

		// Add the provider to the Series.
		add_post_meta( $series, '_tribe_default_ticket_provider', Module::class );

		$this->assertEquals( Module::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $event_1->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $event_2->ID, '_tribe_default_ticket_provider', true ) );

		// Update the provider on the Series.
		update_post_meta( $series, '_tribe_default_ticket_provider', PayPal::class );

		$this->assertEquals( PayPal::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( PayPal::class, get_post_meta( $event_1->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( PayPal::class, get_post_meta( $event_2->ID, '_tribe_default_ticket_provider', true ) );

		// Remove the provider from the Series.
		delete_post_meta( $series, '_tribe_default_ticket_provider' );

		$this->assertEquals( '', get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( '', get_post_meta( $event_1->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( '', get_post_meta( $event_2->ID, '_tribe_default_ticket_provider', true ) );
	}

	/**
	 * It should set default ticket provider from Series when Event added to Series
	 *
	 * @test
	 */
	public function should_set_default_ticket_provider_from_series_when_event_added_to_series(): void {
		// Create a single Event and set the ticket provider to PayPal.
		$event = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
		] )->create();
		update_post_meta( $event->ID, '_tribe_default_ticket_provider', PayPal::class );
		// Create a recurring Event and set the ticket provider to Ticket Commerce.
		$recurring_event = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2'
		] )->create();
		update_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', PayPal::class );
		// Create a Series and set the ticket provider to Ticket Commerce.
		$series = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Set the Series
		update_post_meta( $series, '_tribe_default_ticket_provider', Module::class );

		// Build and register the controller to hook.
		$this->make_controller()->register();

		// Relate the single Event to the Series.
		tribe( Relationship::class )->with_series( get_post( $series ), [ $event->ID ] );

		$this->assertEquals( Module::class, get_post_meta( $event->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( PayPal::class, get_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', true ) );

		// Relate the recurring Event to the Series.
		tribe( Relationship::class )->with_series( get_post( $series ), [ $recurring_event->ID ] );

		$this->assertEquals( Module::class, get_post_meta( $event->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', true ) );
	}

	/**
	 * It should set default ticket provider from Series to Events when connected from Event
	 *
	 * @test
	 */
	public function should_set_default_ticket_provider_from_series_to_events_when_connected_from_event(): void {
		// Create a single Event and set the ticket provider to PayPal.
		$event = tribe_events()->set_args( [
			'title'      => 'Event 1',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
		] )->create();
		update_post_meta( $event->ID, '_tribe_default_ticket_provider', PayPal::class );
		// Create a recurring Event and set the ticket provider to Ticket Commerce.
		$recurring_event = tribe_events()->set_args( [
			'title'      => 'Recurring Event',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2'
		] )->create();
		update_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', PayPal::class );
		// Create a Series and set the ticket provider to Ticket Commerce.
		$series = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		// Set the Series
		update_post_meta( $series, '_tribe_default_ticket_provider', Module::class );

		// Build and register the controller to hook.
		$this->make_controller()->register();

		// Relate the single Event to the Series.
		tribe( Relationship::class )->with_event( Event::find( $event->ID, 'post_id' ), [ $series ] );

		$this->assertEquals( Module::class, get_post_meta( $event->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( PayPal::class, get_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', true ) );

		// Relate the recurring Event to the Series.
		tribe( Relationship::class )->with_event( Event::find( $recurring_event->ID, 'post_id' ), [ $series ] );

		$this->assertEquals( Module::class, get_post_meta( $event->ID, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $series, '_tribe_default_ticket_provider', true ) );
		$this->assertEquals( Module::class, get_post_meta( $recurring_event->ID, '_tribe_default_ticket_provider', true ) );
	}

	/**
	 * It should add Series ID to Event IDs when fetching Tickets by Event
	 *
	 * @test
	 */
	public function should_add_series_id_to_event_ids_when_fetching_tickets_by_event(): void {
		$series          = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$series_pass     = $this->create_tc_series_pass( $series )->ID;
		$event_in_series = tribe_events()->set_args( [
			'title'      => 'Event in Series',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series,
		] )->create()->ID;

		// Start with the controller unregistered.
		$this->assertEqualSets(
			[],
			tribe_tickets()->where( 'event', $event_in_series )->get_ids()
		);

		// Build and register the controller.
		$controller = $this->make_controller()->register();

		$this->assertEqualSets(
			[ $series_pass ],
			tribe_tickets()->where( 'event', $event_in_series )->get_ids()
		);

		// Run the same query, but change the context to one the controller should not interfere with.
		$this->assertEqualSets(
			[],
			tribe_tickets()->set_request_context( 'manual-attendees' )
				->where( 'event', $event_in_series, 'metabox_capacity' )->get_ids()
		);
	}

	/**
	 * It should filter Event cost to add cost of Series Passes
	 *
	 * @test
	 */
	public function should_filter_event_cost_to_add_cost_of_series_passes() {
		$series                           = static::factory()->post->create( [ 'post_type' => Series_Post_Type::POSTTYPE ] );
		$pass_1                           = $this->create_tc_series_pass( $series, 66 );
		$pass_2                           = $this->create_tc_series_pass( $series, 99 );
		$event_in_series_with_own_tickets = tribe_events()->set_args( [
			'title'      => 'Event in Series with own Tickets',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series,
		] )->create()->ID;
		$ticket_1                         = $this->create_tc_ticket( $event_in_series_with_own_tickets, 23 );
		add_post_meta( $event_in_series_with_own_tickets, '_EventCost', 23 );
		$ticket_2 = $this->create_tc_ticket( $event_in_series_with_own_tickets, 89 );
		add_post_meta( $event_in_series_with_own_tickets, '_EventCost', 89 );
		$event_in_series_wo_tickets       = tribe_events()->set_args( [
			'title'      => 'Event in Series without own Tickets',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
			'series'     => $series,
		] )->create()->ID;
		$event_not_in_series_with_tickets = tribe_events()->set_args( [
			'title'      => 'Event not in Series',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
		] )->create()->ID;
		$ticket_3                         = $this->create_tc_ticket( $event_not_in_series_with_tickets, 11 );
		add_post_meta( $event_not_in_series_with_tickets, '_EventCost', 11 );
		$ticket_4 = $this->create_tc_ticket( $event_not_in_series_with_tickets, 17 );
		add_post_meta( $event_not_in_series_with_tickets, '_EventCost', 17 );
		$event_not_in_series_wo_tickets = tribe_events()->set_args( [
			'title'      => 'Event not in Series without own Tickets',
			'status'     => 'publish',
			'start_date' => '2020-02-11 17:30:00',
			'end_date'   => '2020-02-11 18:00:00',
		] )->create()->ID;

		// Baseline: the controller is not filtering the costs.
		$this->assertEqualSets(
			[ '23', '89' ],
			tribe_get_event_meta( $event_in_series_with_own_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[ '11', '17' ],
			tribe_get_event_meta( $event_not_in_series_with_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[],
			tribe_get_event_meta( $event_in_series_wo_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[],
			tribe_get_event_meta( $event_not_in_series_wo_tickets, '_EventCost', false )
		);

		// Build and register the controller.
		$this->make_controller()->register();

		// The controller is filtering the costs.
		$this->assertEqualSets(
			[ '23', '89', '66', '99' ],
			tribe_get_event_meta( $event_in_series_with_own_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[ '11', '17' ],
			tribe_get_event_meta( $event_not_in_series_with_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[ '66', '99' ],
			tribe_get_event_meta( $event_in_series_wo_tickets, '_EventCost', false )
		);
		$this->assertEqualSets(
			[],
			tribe_get_event_meta( $event_not_in_series_wo_tickets, '_EventCost', false )
		);

		// Leave single meta values untouched.
		$this->assertEquals(
			'23',
			tribe_get_event_meta( $event_in_series_with_own_tickets, '_EventCost', true )
		);
		$this->assertEquals(
			'11',
			tribe_get_event_meta( $event_not_in_series_with_tickets, '_EventCost', true )
		);
		$this->assertEquals(
			'',
			tribe_get_event_meta( $event_in_series_wo_tickets, '_EventCost', true )
		);
		$this->assertEquals(
			'',
			tribe_get_event_meta( $event_not_in_series_wo_tickets, '_EventCost', true )
		);
	}

	public function ticketed_and_unticketed_counts_provider(): Generator {
		yield 'no posts' => [
			static function (): string {
				return 'post';
			}
		];

		yield '5 unticketed posts, 3 valid and 2 invalid post status' => [
			function (): string {
				$this->factory()->post->create_many( 3 );
				$this->factory()->post->create( [ 'post_status' => 'trash' ] );
				$this->factory()->post->create( [ 'post_status' => 'auto-draft' ] );

				return 'post';
			}

		];

		yield '3 ticketed posts' => [
			function (): string {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}

				return 'post';
			}
		];

		yield '5 ticketed and 6 unticketed posts, 2 invalid post status in each case' => [
			function (): string {
				foreach ( $this->factory()->post->create_many( 3 ) as $post ) {
					$this->create_tc_ticket( $post );
				}

				$post_id_1 = $this->factory()->post->create( [ 'post_status' => 'trash' ] );
				$post_id_2 = $this->factory()->post->create( [ 'post_status' => 'auto-draft' ] );
				$this->create_tc_ticket( $post_id_1 );
				$this->create_tc_ticket( $post_id_2 );

				$this->factory()->post->create_many( 4 );
				$this->factory()->post->create( [ 'post_status' => 'trash' ] );
				$this->factory()->post->create( [ 'post_status' => 'auto-draft' ] );

				return 'post';
			}
		];

		yield '3 ticketed, 4 unticketed single events - 1 of each with invalid post status' => [
			function (): string {
				foreach ( range( 1, 7 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => "Event $k",
						'status'     => $k === 3 ? 'trash' : ( $k === 6 ? 'auto-draft' : 'publish' ),
						'start_date' => '2020-02-11 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					] )->create()->ID;

					if ( $k <= 3 ) {
						$this->create_tc_ticket( $event_id );
					}
				}

				return TEC::POSTTYPE;
			}
		];

		yield '3 series with passes, each with 2 events' => [
			function (): string {
				foreach ( range( 1, 3 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => "Recurring Event $k",
						'status'     => 'publish',
						'start_date' => '2020-02-11 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
						'recurrence' => 'RRULE:FREQ=DAILY;COUNT=4',
					] )->create()->ID;


					$series_id = Series_Relationship::where( 'event_post_id', '=', $event_id )
						->first()->series_post_id;

					$this->create_tc_series_pass( $series_id );

					// Create another event part of the series
					$event_id = tribe_events()->set_args( [
						'title'      => "Recurring Event $k-2",
						'status'     => 'publish',
						'start_date' => '2020-02-12 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
						'series'     => $series_id,
					] )->create()->ID;
				}

				return TEC::POSTTYPE;
			}
		];

		yield '2 series with passes and 2 events each, 3 ticketed, 4 unticketed single events' => [
			function (): string {
				foreach ( range( 1, 2 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => "Recurring Event $k",
						'status'     => 'publish',
						'start_date' => '2020-02-11 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
						'recurrence' => 'RRULE:FREQ=DAILY;COUNT=4',
					] )->create()->ID;


					$series_id = Series_Relationship::where( 'event_post_id', '=', $event_id )
						->first()->series_post_id;

					$this->create_tc_series_pass( $series_id );

					// Create another event part of the series
					$event_id = tribe_events()->set_args( [
						'title'      => "Recurring Event $k-2",
						'status'     => 'publish',
						'start_date' => '2020-02-12 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
						'series'     => $series_id,
					] )->create()->ID;
				}

				foreach ( range( 1, 7 ) as $k ) {
					$event_id = tribe_events()->set_args( [
						'title'      => "Event $k",
						'status'     => 'publish',
						'start_date' => '2020-02-11 17:30:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					] )->create()->ID;

					if ( $k <= 3 ) {
						$this->create_tc_ticket( $event_id );
					}
				}

				return TEC::POSTTYPE;
			}
		];

		yield 'series screen, 2 ticketed, 1 unticketed series with ticketed events' => [
			function (): string {
				foreach ( range( 1, 3 ) as $k ) {
					$series_id = static::factory()->post->create( [ 'post_type' => Series_Post_Type::POSTTYPE ] );

					if ( $k < 3 ) {
						$this->create_tc_series_pass( $series_id );
					}
					if ( $k === 3 ) {
						// Add a ticketed event to the Series.
						$event_id = tribe_events()->set_args( [
							'title'      => "Event $k",
							'status'     => 'publish',
							'start_date' => '2020-02-11 17:30:00',
							'duration'   => 2 * HOUR_IN_SECONDS,
							'series'     => $series_id,
						] )->create()->ID;

						$this->create_tc_ticket( $event_id );
					}
				}

				return Series_Post_Type::POSTTYPE;
			}
		];
	}

	/**
	 * It should correctly filter the Ticketec/Unticketed labels
	 *
	 * @test
	 * @dataProvider ticketed_and_unticketed_counts_provider
	 */
	public function should_correctly_filter_the_ticketed_unticketed_labels( Closure $fixture ): void {
		$post_type = $fixture();

		$this->make_controller()->register();

		$ticketed = new Ticketed( $post_type );
		$filtered = $ticketed->filter_edit_link( [] );

		$this->assertMatchesHtmlSnapshot( implode( "\n", array_values( $filtered ) ) );
	}

	/**
	 * It should correctly filter the editor configuration
	 *
	 * @test
	 */
	public function should_correctly_filter_the_editor_configuration(): void {
		$controller = $this->make_controller();

		$editor_configuration_data = $controller->filter_editor_configuration_data( [] );

		$this->assertMatchesJsonSnapshot( json_encode( $editor_configuration_data, JSON_PRETTY_PRINT ) );
	}

	/**
	 * It should prevent editing a Series Pass outside of a Series post
	 *
	 * @test
	 */
	public function should_prevent_editing_a_series_pass_outside_of_a_series_post(): void {
		$post_id                 = static::factory()->post->create( [] );
		$series_post_id          = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE
		] );
		$event_post_id           = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2022-01-01 09:00:00',
			'end_date'   => '2022-01-01 10:00:00',
		] )->create()->ID;
		$event_in_series_post_id = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2022-01-01 09:00:00',
			'end_date'   => '2022-01-01 10:00:00',
			'series'     => $series_post_id
		] )->create()->ID;

		/** @var Series_Passes $controller */
		$controller = $this->make_controller();

		// Sanity checks.
		$this->assertTrue(
			$controller->is_ticket_editable_from_post(
				true,
				$this->create_tc_ticket( $post_id ),
				$post_id
			)
		);
		$this->assertTrue(
			$controller->is_ticket_editable_from_post(
				true,
				$this->create_tc_ticket( $event_post_id ),
				$event_post_id
			)
		);
		$this->assertTrue(
			$controller->is_ticket_editable_from_post(
				true,
				$this->create_tc_ticket( $event_in_series_post_id ),
				$event_in_series_post_id
			)
		);

		$series_pass_id = $this->create_tc_series_pass( $series_post_id )->ID;

		$this->assertTrue(
			$controller->is_ticket_editable_from_post(
				true,
				$series_pass_id,
				$series_post_id
			),
			'A series pass is editable from the series post'
		);
		$this->assertFalse(
			$controller->is_ticket_editable_from_post(
				true,
				$series_pass_id,
				$post_id
			),
			'A series pass is not editable from a post'
		);
		$this->assertFalse(
			$controller->is_ticket_editable_from_post(
				true,
				$series_pass_id,
				$event_post_id
			),
			'A series pass is not editable from an event post'
		);
		$this->assertFalse(
			$controller->is_ticket_editable_from_post(
				true,
				$series_pass_id,
				$event_in_series_post_id
			),
			'A series pass is not editable from an event post part of that series'
		);
	}

	/**
	 * It should allow tickets on recurring events
	 *
	 * @test
	 */
	public function should_allow_tickets_on_recurring_events(): void {
		$this->set_fn_return( 'is_admin', true );
		$this->assertFalse( apply_filters( 'tec_tickets_allow_tickets_on_recurring_events', false ) );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertTrue( apply_filters( 'tec_tickets_allow_tickets_on_recurring_events', true ) );
		$this->unset_uopz_returns();
	}

	/**
	 * It should not allow tickets on single events with own tickets
	 *
	 * @test
	 */
	public function should_not_allow_tickets_on_single_events_with_own_tickets(): void {
		$single_event   = tribe_events()->set_args( [
			'title'      => 'Test Event',
			'status'     => 'publish',
			'start_date' => '2022-01-01 09:00:00',
			'duration'   => 3 * HOUR_IN_SECONDS,
		] )->create();
		$default_ticket = $this->create_tc_ticket( $single_event->ID );
		// Simulate a request to the edit the Event.
		$this->go_to( get_edit_post_link( $single_event ) );

		$this->assertFalse( apply_filters( 'tec_tickets_allow_tickets_on_single_events_with_own_tickets', false ) );

		$controller = $this->make_controller();
		$controller->register();

		$this->assertFalse( apply_filters( 'tec_tickets_allow_tickets_on_single_events_with_own_tickets', false ) );
	}

	/**
	 * It should filter the FT editor data
	 *
	 * @test
	 */
	public function should_filter_the_ft_editor_data(): void {
		// Apply the filter while the controller is not registered.
		$this->assertEquals( [], apply_filters( 'tec_tickets_flexible_tickets_editor_data', [] ) );

		$this->make_controller()->register();

		// Register the controller and re-apply the filter.
		$this->assertMatchesCodeSnapshot(
			var_export(
				apply_filters( 'tec_tickets_flexible_tickets_editor_data', [] ),
				true
			)
		);
	}
}
