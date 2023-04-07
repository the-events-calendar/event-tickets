<?php

namespace TEC\Tickets\Flexible_Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Test\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Log as Log;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets as Tickets;

class Series_PassesTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;

	protected $controller_class = Series_Passes::class;

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

		yield 'post ID is a Series, with ticket providers' => [
			function () {
				$this->set_class_fn_return( Tickets::class, 'modules', [ 'Stripe' => 'Some_Class' ] );

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
	 * @dataProvider post_not_series_provider
	 */
	public function test_render_form_toggle( Closure $fixture ): void {
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

	public function invalid_add_pass_custom_tables_data_provider(): Generator {
		yield 'empty post ID, ticket ID and ticket data' => [
			function () {
				return [ '', false, [] ];
			}
		];

		$ticket_data = [
			'ticket_name'             => '5 days of concert',
			'ticket_description'      => 'Just like the old days',
			'ticket_show_description' => '1',
			'ticket_start_date'       => '4/24/2023',
			'ticket_start_time'       => '',
			'ticket_end_date'         => '4/30/2023',
			'ticket_end_time'         => '',
			'ticket_provider'         => 'TEC\\Tickets\\Commerce\\Module',
			'ticket_price'            => '2389',
			'tribe-ticket'            =>
				[
					'capacity' => '1000',
				],
			'ticket_sku'              => '',
			'ticket_id'               => '',
			'ticket_menu_order'       => 'undefined',
		];

		yield 'post ID is not a number, ticket ID and ticket data' => [
			function () use ( $ticket_data ) {
				// Legit Series.
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				// Legit ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 2389 );

				return [ 'foo', $ticket_id, $ticket_data ];
			}
		];

		yield 'post ID, ticket ID not a number, ticket data' => [
			function () use ( $ticket_data ) {
				// Legit Series.
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );

				return [ $post_id, 'foo', $ticket_data ];
			}
		];

		yield 'post ID, ticket ID, ticket data not an array' => [
			function () {
				// Legit Series.
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				// Legit ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 2389 );

				return [ $post_id, $ticket_id, 'foo' ];
			}
		];

		yield 'post ID not Series' => [
			function () {
				// Legit Series.
				$post_id = static::factory()->post->create();
				// Legit ticket.
				$ticket_id = $this->create_tc_ticket( $post_id, 2389 );

				return [ $post_id, $ticket_id, 'foo' ];
			}
		];
	}

	/**
	 * It should not add custom tables data on invalid filter arguments
	 *
	 * @test
	 * @dataProvider invalid_add_pass_custom_tables_data_provider
	 */
	public function should_not_add_custom_tables_data_on_invalid_filter_arguments( Closure $fixture ): void {
		[ $post_id, $ticket_id, $ticket_data ] = $fixture();

		$controller = $this->make_controller();

		$this->assertFalse( $controller->add_pass_custom_tables_data( $post_id, $ticket_id, $ticket_data ) );

		$this->assert_custom_table_empty(
			Capacities::table_name(),
			Capacities_Relationships::table_name(),
			Posts_And_Posts::table_name(),
			Posts_And_Ticket_Groups::table_name(),
			Posts_And_Users::table_name(),
			Ticket_Groups::table_name()
		);
	}

	/**
	 * It should add pass custom tables data correctly
	 *
	 * @test
	 */
	public function should_add_pass_custom_tables_data_correctly(): void {
		$series_id   = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket_data = [
			'ticket_name'             => '5 days of concert',
			'ticket_description'      => 'Just like the old days',
			'ticket_show_description' => '1',
			'ticket_start_date'       => '4/24/2023',
			'ticket_start_time'       => '',
			'ticket_end_date'         => '4/30/2023',
			'ticket_end_time'         => '',
			'ticket_provider'         => 'TEC\\Tickets\\Commerce\\Module',
			'ticket_price'            => '2389',
			'tribe-ticket'            =>
				[
					'mode'     => Global_Stock::OWN_STOCK_MODE,
					'capacity' => '1000',
				],
			'ticket_sku'              => '5-DAYS-OF-CONCERT',
			'ticket_id'               => '',
			'ticket_menu_order'       => 'undefined',
		];

		// Create the ticket like the AJAX handler would.
		$ticket_id = Commerce\Module::get_instance()->ticket_add( $series_id, $ticket_data );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->add_pass_custom_tables_data( $series_id, $ticket_id, $ticket_data ) );
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket {$ticket_id} and Series {$series_id}" );
	}
}
