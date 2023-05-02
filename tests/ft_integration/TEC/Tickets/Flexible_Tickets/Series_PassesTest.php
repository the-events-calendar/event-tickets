<?php

namespace TEC\Tickets\Flexible_Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Custom_Tables_Assertions;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Ticket_Data_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Log as Log;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets as Tickets;

class Series_PassesTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Series_Pass_Factory;
	use Custom_Tables_Assertions;
	use Ticket_Data_Factory;

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

	public function invalid_add_pass_custom_tables_data_provider(): Generator {
		yield 'empty post ID, ticket and date' => [
			function () {
				return [ '', false, [] ];
			}
		];

//		yield 'post ID is not a number, ticket valid, data valid' => [
//			function () {
//				// Legit Series.
//				$post_id = static::factory()->post->create( [
//					'post_type' => Series_Post_Type::POSTTYPE,
//				] );
//				// Legit ticket.
//				$ticket = $this->create_tc_series_pass( $post_id, 2389 );
//				$data   = $this->data_for_ticket( $ticket );
//
//				return [ 'foo', $ticket, $data ];
//			}
//		];
//
//		yield 'post ID, not Ticket' => [
//			function () {
//				// Legit Series.
//				$post_id = static::factory()->post->create( [
//					'post_type' => Series_Post_Type::POSTTYPE,
//				] );
//
//				return [ $post_id, [], [] ];
//			}
//		];
//
//		yield 'post ID not Series' => [
//			function () {
//				// Legit Series.
//				$post_id = static::factory()->post->create();
//				// Legit ticket.
//				$ticket = $this->create_tc_series_pass( $post_id, 2389 );
//				$data   = $this->data_for_ticket( $ticket );
//
//				return [ $post_id, $ticket, $data ];
//			}
//		];
	}

	/**
	 * It should not add custom tables data on invalid filter arguments
	 *
	 * @test
	 * @dataProvider invalid_add_pass_custom_tables_data_provider
	 */
	public function should_not_add_custom_tables_data_on_invalid_filter_arguments( Closure $fixture ): void {
		[ $post_id, $ticket, $data ] = $fixture();

		$controller = $this->make_controller();

		$this->assertFalse( $controller->insert_pass_custom_tables_data( $post_id, $ticket, $data ) );

		$this->assert_tables_empty(
			Capacities::table_name(),
			Capacities_Relationships::table_name(),
			Posts_And_Posts::table_name(),
			Posts_And_Ticket_Groups::table_name(),
			Posts_And_Users::table_name(),
			Ticket_Groups::table_name()
		);
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
	 * It should insert unlimited pass data correctly
	 *
	 * @test
	 */
	public function should_insert_unlimited_pass_data_correctly(): void {
		$series_id        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$capacity_payload = $this->capacity_payload( 'unlimited' );
		$ticket           = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $capacity_payload,
		] );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->insert_pass_custom_tables_data(
			$series_id,
			$ticket,
			$this->data_for_ticket( $ticket, $capacity_payload ) )
		);
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket" );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[ 'parent_capacity_id' => 0, 'object_id' => $ticket->ID ],
			[
				'max_value'     => Capacities::VALUE_UNLIMITED,
				'current_value' => Capacities::VALUE_UNLIMITED,
				'mode'          => Capacities::MODE_UNLIMITED
			]
		);
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * It should insert_own_pass_data_correctly
	 *
	 * @test
	 */
	public function should_insert_own_pass_data_correctly(): void {
		// Create the controller first to make sure it will not be registered.
		$controller = $this->make_controller();

		$series_id        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$capacity_payload = $this->capacity_payload( 'own_89' );
		$ticket           = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $capacity_payload,
		] );

		$this->assertTrue( $controller->insert_pass_custom_tables_data(
			$series_id,
			$ticket,
			$this->data_for_ticket( $ticket, $capacity_payload ) )
		);
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket" );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[ 'parent_capacity_id' => 0, 'object_id' => $ticket->ID ],
			[
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::OWN_STOCK_MODE
			]
		);
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * It should insert global pass data correctly
	 *
	 * @test
	 */
	public function should_insert_global_pass_data_correctly(): void {
		// Create the controller first to make sure it will not be registered.
		$controller = $this->make_controller();

		$series_id        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$capacity_payload = $this->capacity_payload( 'global_100' );
		$ticket           = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $capacity_payload,
		] );

		$this->assertTrue( $controller->insert_pass_custom_tables_data(
			$series_id,
			$ticket,
			$this->data_for_ticket( $ticket, $capacity_payload ) )
		);
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket" );
		$this->assert_object_capacity_in_db(
			$series_id,
			[ 'parent_capacity_id' => 0, 'object_id' => $series_id ],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE
			]
		);
		$event_capacity_id = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                         ->find_by_object_id( $series_id )->capacity_id;
		$this->assertNotEmpty( $event_capacity_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[ 'parent_capacity_id' => 0, 'object_id' => $ticket->ID, 'capacity_id' => $event_capacity_id ],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE
			]
		);
	}

	/**
	 * It should insert capped pass data correctly
	 *
	 * @test
	 */
	public function should_insert_capped_pass_data_correctly(): void {
		// Create the controller first to make sure it will not be registered.
		$controller = $this->make_controller();

		$series_id        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$capacity_payload = $this->capacity_payload( 'capped_23' );
		$ticket           = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $capacity_payload,
		] );

		$this->assertTrue( $controller->insert_pass_custom_tables_data(
			$series_id,
			$ticket,
			$this->data_for_ticket( $ticket, $capacity_payload ) )
		);
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket" );
		$this->assert_object_capacity_in_db(
			$series_id,
			[ 'parent_capacity_id' => 0, 'object_id' => $series_id ],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE
			]
		);
		$capacities_relationships = $this->test_services->get( Repositories\Capacities_Relationships::class );
		$event_capacity_id        = $capacities_relationships->find_by_object_id( $series_id )->capacity_id;
		$this->assertNotEmpty( $event_capacity_id );
		$ticket_capacity_id = $capacities_relationships->find_by_object_id( $ticket->ID )->capacity_id;
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'parent_capacity_id' => $event_capacity_id,
				'object_id'          => $ticket->ID,
				'capacity_id'        => $ticket_capacity_id
			],
			[
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::CAPPED_STOCK_MODE
			]
		);
	}

	public function custom_table_names(): array {
		return [
			'posts_an_posts'           => [ Posts_And_Posts::table_name() ],
			'capacities'               => [ Capacities::table_name() ],
			'capacities_relationships' => [ Capacities_Relationships::table_name() ],
		];
	}

	/**
	 * It should throw if table insertion throws
	 *
	 * @test
	 * @dataProvider custom_table_names
	 */
	public function should_throw_if_table_insert_throws( string $table_name ): void {
		global $wpdb;
		// Avoid filling the test output.
		$wpdb->suppress_errors = true;
		// Use legit data.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );
		// The DB::insert for the table will fail; this will cause output in the tests.
		add_filter( 'query', static function ( string $query ) use ( $table_name ) {
			if ( preg_match( '/^INSERT INTO `' . $table_name . '`/i', $query ) ) {
				return 'SELECT foo FROM bar';
			}

			return $query;
		} );

		$controller = $this->make_controller();

		$this->expectException( DatabaseQueryException::class );

		$controller->insert_pass_custom_tables_data( $series_id, $ticket, $this->data_for_ticket( $ticket ) );
	}

	public function delete_incorrect_input_data_provider(): \Generator {
		yield 'post ID empty, ticket ID empty' => [
			static function () {
				return [ '', '' ];
			},
		];

		yield 'post ID is a string' => [
			function () {
				$series_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$ticket_id = $this->create_tc_series_pass( $series_id, 2389 );

				return [ 'foo', $ticket_id ];
			},
		];

		yield 'post ID is not a Series' => [
			function () {
				$post_id   = static::factory()->post->create( [
					'post_type' => 'post',
				] );
				$ticket_id = $this->create_tc_ticket( $post_id, 2389 );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post ID is Series, Ticket ID empty' => [
			function () {
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );

				return [ $post_id, '' ];
			},
		];

		yield 'post ID is Series, ticket ID is not a ticket' => [
			function () {
				$post_id   = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$ticket_id = static::factory()->post->create( [
					'post_type' => 'post',
				] );

				return [ $post_id, $ticket_id ];
			},
		];

		yield 'post ID is Series, ticket ID is not series pass' => [
			function () {
				$post_id   = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				$ticket_id = $this->create_tc_ticket( $post_id, 2389 );

				return [ $post_id, $ticket_id ];
			},
		];
	}

	/**
	 * It should not delete from custom tables when input data is not correct
	 *
	 * @test
	 * @dataProvider delete_incorrect_input_data_provider
	 */
	public function should_not_delete_from_custom_tables_when_input_data_is_not_correct( Closure $fixture ): void {
		[ $post_id, $ticket_id ] = $fixture();

		$controller = $this->make_controller();

		$this->assertFalse( $controller->delete_pass_custom_tables_data( $post_id, $ticket_id ) );
	}

	/**
	 * It should remove custom tables information for pass when pass deleted
	 *
	 * @test
	 */
	public function should_remove_custom_tables_information_for_pass_when_pass_deleted(): void {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->insert_pass_custom_tables_data( $series_id, $ticket, $this->data_for_ticket( $ticket ) ) );

		$capacities_relationships = Capacities_Relationships::table_name();
		$capacities               = Capacities::table_name();
		$capacity_id              = DB::get_var(
			DB::prepare(
				"SELECT capacity_id FROM $capacities_relationships WHERE object_id = %d",
				$ticket->ID
			)
		);

		$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id, $ticket->ID ) );
		$this->assertEquals( 0, DB::get_var(
			DB::prepare(
				"SELECT count(id) FROM $capacities_relationships WHERE object_id = %d",
				$ticket->ID
			)
		) );
		$this->assertEquals( 0, DB::get_var(
			DB::prepare(
				"SELECT count(id) FROM $capacities WHERE id = %d",
				$capacity_id
			)
		) );
	}

	/**
	 * It should bail if capacity ID cannot be found while deleting pass data
	 *
	 * @test
	 */
	public function should_bail_if_capacity_id_cannot_be_found_while_deleting_pass_data(): void {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->insert_pass_custom_tables_data( $series_id, $ticket, $this->data_for_ticket( $ticket ) ) );

		$capacities_relationships = Capacities_Relationships::table_name();
		// Remove the capacity from the relationships table.
		DB::delete(
			$capacities_relationships,
			[ 'object_id' => $ticket->ID ],
			[ '%d' ]
		);

		$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id, $ticket->ID ) );
		$this->assert_controller_logged( Log::DEBUG, 'No Series Pass custom tables data found to delete for Ticket ' . $ticket->ID );
	}

	/**
	 * It should throw if table deletion throws
	 *
	 * @test
	 * @dataProvider custom_table_names
	 */
	public function should_throw_if_table_deletion_throws( string $table_name ): void {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );
		global $wpdb;
		// Avoid filling the test output.
		$wpdb->suppress_errors = true;

		$controller = $this->make_controller();

		$this->assertTrue( $controller->insert_pass_custom_tables_data( $series_id, $ticket, $this->data_for_ticket( $ticket ) ) );

		// Filter the query to trigger an error during the posts and posts deletion.
		add_filter( 'query', static function ( string $query ) use ( $table_name ) {
			if ( preg_match( '/^DELETE FROM `' . $table_name . '`/i', $query ) ) {
				return 'SELECT foo FROM bar';
			}

			return $query;
		} );

		$this->expectException( DatabaseQueryException::class );

		$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id, $ticket->ID ) );
	}

	/**
	 * It should not update pass data on invalid input
	 *
	 * @test
	 * @dataProvider invalid_add_pass_custom_tables_data_provider
	 */
	public function should_not_update_pass_data_on_invalid_input( Closure $fixture ): void {
		[ $post_id, $ticket, $data ] = $fixture();

		$controller = $this->make_controller();

		$this->assertFalse( $controller->update_pass_custom_tables_data( $post_id, $ticket, $data ) );
	}

	/**
	 * It should correctly update pass data from global to global
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_global_to_global(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'global_100' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'global_89' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $series_capacity_relationship->capacity_id,
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from global to capped
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_global_to_capped(): void {
		[
			$series_id,
			$ticket,
			,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'global_100' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'capped_23' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$ticket_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $ticket->ID );
		$this->assertNotSame( $ticket_capacity_relationship->capacity_id, $series_capacity_relationship->capacity_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => $series_capacity_relationship->capacity_id,
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from global to unlimited
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_global_to_unlimited(): void {
		[
			$series_id,
			$ticket,
			,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'global_100' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'unlimited' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$ticket_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $ticket->ID );
		$this->assertNotSame( $ticket_capacity_relationship->capacity_id, $series_capacity_relationship->capacity_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0,
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => Capacities::VALUE_UNLIMITED,
				'current_value' => Capacities::VALUE_UNLIMITED,
				'mode'          => Capacities::MODE_UNLIMITED,
			] );
	}

	/**
	 * It should correctly update pass data from global to own
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_global_to_own(): void {
		[
			$series_id,
			$ticket,
			,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'global_100' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'own_89' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$ticket_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $ticket->ID );
		$this->assertNotSame( $ticket_capacity_relationship->capacity_id, $series_capacity_relationship->capacity_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0,
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::OWN_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from capped to global
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_capped_to_global(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'capped_23' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'global_100' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0,
			],
			[
				'id'            => $series_capacity_relationship->capacity_id,
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from capped to capped
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_capped_to_capped(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship,
			$series_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'capped_23' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'capped_89' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'id'                 => $series_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => $series_capacity_relationship->capacity_id,
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from capped to unlimited
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_capped_to_unlimited(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'capped_23' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'unlimited' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => Capacities::VALUE_UNLIMITED,
				'current_value' => Capacities::VALUE_UNLIMITED,
				'mode'          => Capacities::MODE_UNLIMITED,
			] );
	}

	/**
	 * It should correctly update pass data from capped to own
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_capped_to_own(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'capped_23' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'own_89' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 89,
				'current_value' => 89,
				'mode'          => Global_Stock::OWN_STOCK_MODE
			] );
	}

	/**
	 * It should correctly update pass data from unlimited to global
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_unlimited_to_global(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'unlimited' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'global_100' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$series_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $series_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $series_capacity_relationship->capacity_id,
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE
			] );
	}

	/**
	 * It should correctly update pass data from unlimited to capped
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_unlimited_to_capped(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'unlimited' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'capped_23' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0,
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$series_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $series_id );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => $series_capacity_relationship->capacity_id
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::CAPPED_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from unlimited to unlimited
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_unlimited_to_unlimited(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'unlimited' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'unlimited' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => Capacities::VALUE_UNLIMITED,
				'current_value' => Capacities::VALUE_UNLIMITED,
				'mode'          => Capacities::MODE_UNLIMITED,
			] );
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * It should correctly update pass data from unlimited to own
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_unlimited_to_own(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'unlimited' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'own_23' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::OWN_STOCK_MODE,
			] );
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * It should correctly update pass data from own to global
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_own_to_global(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'own_89' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'global_100' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );
		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$series_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $series_id );
		$this->assertInstanceOf( Capacity_Relationship::class, $series_capacity_relationship );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $series_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $series_capacity_relationship->capacity_id,
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
	}

	/**
	 * It should correctly update pass data from own to capped
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_own_to_capped(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'own_89' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'capped_23' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );

		$this->assert_object_capacity_in_db(
			$series_id,
			[
				'object_id'          => $series_id,
				'parent_capacity_id' => 0
			],
			[
				'max_value'     => 100,
				'current_value' => 100,
				'mode'          => Global_Stock::GLOBAL_STOCK_MODE,
			] );
		$series_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $series_id );
		$this->assertInstanceOf( Capacity_Relationship::class, $series_capacity_relationship );
		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => $series_capacity_relationship->capacity_id
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::CAPPED_STOCK_MODE
			] );
	}

	/**
	 * It should correctly update pass data from own to unlimited
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_own_to_unlimited(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'own_89' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'unlimited' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );

		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => Capacities::VALUE_UNLIMITED,
				'current_value' => Capacities::VALUE_UNLIMITED,
				'mode'          => Capacities::MODE_UNLIMITED,
			] );
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * It should correctly update pass data from own to own
	 *
	 * @test
	 */
	public function should_correctly_update_pass_data_from_own_to_own(): void {
		[
			$series_id,
			$ticket,
			$ticket_capacity_relationship
		] = $this->given_a_pass_with_capacity( 'own_89' );

		$controller = $this->make_controller();

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'own_99' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );

		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 99,
				'current_value' => 99,
				'mode'          => Global_Stock::OWN_STOCK_MODE
			] );
		$this->assert_object_capacity_not_in_db( $series_id );

		$data = $this->data_for_ticket( $ticket, $this->capacity_payload( 'own_23' ) );
		$this->assertTrue( $controller->update_pass_custom_tables_data( $series_id, $ticket, $data ) );

		$this->assert_object_capacity_in_db(
			$ticket->ID,
			[
				'id'                 => $ticket_capacity_relationship->id,
				'capacity_id'        => $ticket_capacity_relationship->capacity_id,
				'object_id'          => $ticket->ID,
				'parent_capacity_id' => 0
			],
			[
				'id'            => $ticket_capacity_relationship->capacity_id,
				'max_value'     => 23,
				'current_value' => 23,
				'mode'          => Global_Stock::OWN_STOCK_MODE
			] );
		$this->assert_object_capacity_not_in_db( $series_id );
	}

	/**
	 * @return array{int,int,Repositories\Capacities_Relationships,Repositories\Capacities_Relationships}
	 */
	protected function given_a_pass_with_capacity( string $payload ): array {
		$controller = $this->make_controller();

		$series_id        = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$capacity_payload = $this->capacity_payload( $payload );
		$ticket           = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $capacity_payload,
		] );
		$data             = $this->data_for_ticket( $ticket, $capacity_payload );

		$this->assertTrue( $controller->insert_pass_custom_tables_data( $series_id, $ticket, $data ) );

		$ticket_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $ticket->ID );
		$series_capacity_relationship = $this->test_services->get( Repositories\Capacities_Relationships::class )
		                                                    ->find_by_object_id( $series_id );

		return [ $series_id, $ticket, $ticket_capacity_relationship, $series_capacity_relationship ];
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

		$ticket_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] );

		$controller->register();

		$this->assertSame( 0, has_filter( 'the_content', [ $controller, 'reorder_series_content' ] ) );
		$this->assertSame( 'test content', $controller->reorder_series_content( 'test content' ) );
		$this->assertSame( false, has_filter( 'the_content', [ $controller, 'reorder_series_content' ] ) );
	}

	/**
	 * It should get ticket metadata correctly when not set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Meta_Redirection::redirect_metadata()
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::get_ticket_metadata()
	 *
	 * @test
	 */
	public function should_get_ticket_metadata_correctly_when_not_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		$series_id      = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] )->ID;
		// Ensure the ticket has no end date and time set.
		delete_post_meta( $series_pass_id, '_ticket_end_date' );
		delete_post_meta( $series_pass_id, '_ticket_end_time' );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		$recurring_event_1 = tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		/** @var Occurrence $last */
		$last            = Occurrence::where( 'post_id', $recurring_event_1 )->order_by( 'start_date', 'DESC' )->first();
		$last_start_date = wp_date( 'Y-m-d', strtotime( $last->start_date ) );
		$last_start_time = wp_date( 'H:i:s', strtotime( $last->start_date ) );

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( $last_start_date, $pass_end_date );
		$this->assertEquals( $last_start_time, $pass_end_time );

		// Create and attach a second Recurring Event ending later to the same series.
		$recurring_event_2 = tribe_events()->set_args( [
			'title'      => 'Recurring Event 2',
			'status'     => 'publish',
			'start_date' => '2020-02-01 12:00:00',
			'end_date'   => '2020-02-01 14:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		/** @var Occurrence $last */
		$new_last            = Occurrence::where( 'post_id', $recurring_event_2 )->order_by( 'start_date', 'DESC' )->first();
		$new_last_start_date = wp_date( 'Y-m-d', strtotime( $new_last->start_date ) );
		$new_last_start_time = wp_date( 'H:i:s', strtotime( $new_last->start_date ) );

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( $new_last_start_date, $pass_end_date );
		$this->assertEquals( $new_last_start_time, $pass_end_time );

		// Add a Single Event at a later date attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Single Event',
			'status'     => 'publish',
			'start_date' => '2020-03-01 15:00:00',
			'end_date'   => '2020-03-01 17:00:00',
			'series'     => $series_id,
		] )->create()->ID;

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( '2020-03-01', $pass_end_date );
		$this->assertEquals( '15:00:00', $pass_end_time );
	}

	/**
	 * It should get ticket metadata correctly when explicitly set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Meta_Redirection::redirect_metadata()
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::get_ticket_metadata()
	 *
	 * @test
	 */
	public function should_get_ticket_metadata_correctly_when_explicitly_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		$series_id      = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] )->ID;
		// Ensure the ticket has no end date and time set.
		update_post_meta( $series_pass_id, '_ticket_end_date', '2022-03-04' );
		update_post_meta( $series_pass_id, '_ticket_end_time', '13:30:00' );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( '2022-03-04', $pass_end_date );
		$this->assertEquals( '13:30:00', $pass_end_time );
	}

	/**
	 * It should get ticket metadata correctly when only date set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Meta_Redirection::redirect_metadata()
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::get_ticket_metadata()
	 *
	 * @test
	 */
	public function should_get_ticket_metadata_correctly_when_only_date_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		$series_id      = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] )->ID;
		// Ensure the ticket has no end date and time set.
		update_post_meta( $series_pass_id, '_ticket_end_date', '2022-03-04' );
		delete_post_meta( $series_pass_id, '_ticket_end_time' );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( '2022-03-04', $pass_end_date );
		$this->assertEquals( '12:00:00', $pass_end_time );
	}

	/**
	 * It should get ticket metadata correctly when only time set
	 *
	 * @covers \TEC\Tickets\Flexible_Tickets\Meta_Redirection::redirect_metadata()
	 * @covers \TEC\Tickets\Flexible_Tickets\Series_Passes::get_ticket_metadata()
	 *
	 * @test
	 */
	public function should_get_ticket_metadata_correctly_when_only_time_set(): void {
		// Immediately build and register the test controller to filter insert/update operations on the ticket.
		$controller = $this->make_controller();
		$controller->register();

		$series_id      = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$series_pass_id = $this->create_tc_series_pass( $series_id, 2389, [
			'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
		] )->ID;
		// Ensure the ticket has no end date and time set.
		delete_post_meta( $series_pass_id, '_ticket_end_date' );
		update_post_meta( $series_pass_id, '_ticket_end_time', '14:30:00' );

		// Create a Recurring Event happening daily for 5 days and attached to the Series.
		tribe_events()->set_args( [
			'title'      => 'Recurring Event 1',
			'status'     => 'publish',
			'start_date' => '2020-01-01 12:00:00',
			'end_date'   => '2020-01-01 13:00:00',
			'series'     => $series_id,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=5',
		] )->create()->ID;

		$pass_end_date = get_post_meta( $series_pass_id, '_ticket_end_date', true );
		$pass_end_time = get_post_meta( $series_pass_id, '_ticket_end_time', true );

		$this->assertEquals( '2020-01-05', $pass_end_date );
		$this->assertEquals( '14:30:00', $pass_end_time );
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
					'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
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
					'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
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
					'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
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
					'tribe-ticket' => $this->capacity_payload( 'unlimited' ),
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
