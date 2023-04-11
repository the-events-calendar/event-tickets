<?php

namespace TEC\Tickets\Flexible_Tickets;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Test\Controller_Test_Case;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Log as Log;
use Tribe__Tickets__Tickets as Tickets;

class Series_PassesTest extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Series_Pass_Factory;

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
		yield 'empty post ID and ticket' => [
			function () {
				return [ '', false ];
			}
		];

		yield 'post ID is not a number, ticket valid' => [
			function () {
				// Legit Series.
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );
				// Legit ticket.
				$ticket = $this->create_tc_series_pass( $post_id, 2389 );

				return [ 'foo', $ticket ];
			}
		];

		yield 'post ID, not Ticket' => [
			function () {
				// Legit Series.
				$post_id = static::factory()->post->create( [
					'post_type' => Series_Post_Type::POSTTYPE,
				] );

				return [ $post_id, [] ];
			}
		];

		yield 'post ID not Series' => [
			function () {
				// Legit Series.
				$post_id = static::factory()->post->create();
				// Legit ticket.
				$ticket = $this->create_tc_series_pass( $post_id, 2389 );

				return [ $post_id, $ticket ];
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
		[ $post_id, $ticket ] = $fixture();

		$controller = $this->make_controller();

		$this->assertFalse( $controller->upsert_pass_custom_tables_data( $post_id, $ticket ) );

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
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id, $ticket ) );
		$this->assert_controller_logged( Log::DEBUG, "Added Series Pass custom tables data for Ticket" );
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

		$controller->upsert_pass_custom_tables_data( $series_id, $ticket );
	}

	/**
	 * It should throw and log if table insert does not affect any rows
	 *
	 * @test
	 * @dataProvider custom_table_names
	 */
	public function should_throw_and_log_if_table_insert_does_not_affect_any_rows( string $table_name ): void {
		// Use legit data.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );
		// The DB::insert for the table will not affect any rows.
		add_filter( 'query', static function ( string $query ) use ( $table_name ) {
			if ( preg_match( '/^INSERT INTO `' . $table_name . '`/i', $query ) ) {
				// Return a query that will not affect any rows.
				return "SELECT id FROM $table_name WHERE 1=0";
			}

			return $query;
		} );

		$controller = $this->make_controller();

		try {
			$controller->upsert_pass_custom_tables_data( $series_id, $ticket );
		} catch ( \Exception $e ) {
			$this->assert_controller_logged( Log::ERROR, "Could not insert into $table_name table for ticket" );
		}
		$this->assertInstanceOf( \RuntimeException::class, $e );
	}

	/**
	 * It should throw and log if cannot get last capacity inserted ID
	 *
	 * @test
	 */
	public function should_throw_and_log_if_cannot_get_last_capacity_inserted_id(): void {
		// Use legit data.
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );
		$this->set_class_fn_return( DB::class, 'last_insert_id', false );
		$capacities = Capacities::table_name();

		$controller = $this->make_controller();

		try {
			$controller->upsert_pass_custom_tables_data( $series_id, $ticket );
		} catch ( \Exception $e ) {
			$this->assert_controller_logged( Log::ERROR, "Could not get last insert id for $capacities table for ticket" );
		}
		$this->assertInstanceOf( \RuntimeException::class, $e );
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

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id, $ticket ) );

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
	 * It should log and throw if capacity ID cannot be found while deleting pass data
	 *
	 * @test
	 */
	public function should_log_and_throw_if_capacity_id_cannot_be_found_while_deleting_pass_data(): void {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id, $ticket ) );

		$capacities_relationships = Capacities_Relationships::table_name();
		// Remove the capacity from the relationships table.
		DB::delete(
			$capacities_relationships,
			[ 'object_id' => $ticket->ID ],
			[ '%d' ]
		);

		try {
			$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id, $ticket->ID ) );
		} catch ( \Exception $e ) {
		}
		$this->assertInstanceOf( \RuntimeException::class, $e );
		$this->assert_controller_logged( Log::ERROR, 'Could not get capacity id for ticket ' );
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

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id, $ticket ) );

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
	 * It should log and throw if posts and posts data cannot be deleted
	 *
	 * @test
	 * @dataProvider custom_table_names
	 */
	public function should_log_and_throw_if_posts_and_posts_data_cannot_be_deleted( string $table_name ): void {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket    = $this->create_tc_series_pass( $series_id, 2389 );
		global $wpdb;
		// Avoid filling the test output.
		$wpdb->suppress_errors = true;

		$controller = $this->make_controller();

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id, $ticket ) );

		// Filter the query to make it so that the wpdb call will return `false.
		add_filter( 'query', static function ( string $query ) use ( $table_name ) {
			if ( preg_match( '/^DELETE FROM `' . $table_name . '`/i', $query ) ) {
				return '';
			}

			return $query;
		} );

		try {
			$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id, $ticket->ID ) );
		} catch ( \Exception $e ) {
		}
		$this->assertInstanceOf( \RuntimeException::class, $e );
		$this->assert_controller_logged( Log::ERROR, "Could not delete from $table_name table" );
	}

	/**
	 * It should not remove capacity when pass deleted if related to other tickets
	 *
	 * @test
	 * @skip
	 */
	public function should_not_remove_capacity_when_pass_deleted_if_related_to_other(): void {
		// @todo handle shared capacity first
		$series_id_1 = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket_1    = $this->create_tc_series_pass( $series_id_1, 2389 );
		$series_id_2 = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$ticket_2    = $this->create_tc_series_pass( $series_id_2, 2389 );

		$controller = $this->make_controller();

		$this->assertTrue( $controller->upsert_pass_custom_tables_data( $series_id_1, $ticket_1 ) );

		$capacities_relationships = Capacities_Relationships::table_name();
		$capacities               = Capacities::table_name();
		$capacity_id              = DB::get_var(
			DB::prepare(
				"SELECT capacity_id FROM $capacities_relationships WHERE object_id = %d",
				$ticket_1->ID
			)
		);

		$this->assertTrue( $controller->delete_pass_custom_tables_data( $series_id_1, $ticket_1->ID ) );
		$this->assertEquals( 0, DB::get_var(
			DB::prepare(
				"SELECT count(id) FROM $capacities_relationships WHERE object_id = %d",
				$ticket_1->ID
			)
		) );
		$this->assertEquals( 0, DB::get_var(
			DB::prepare(
				"SELECT count(id) FROM $capacities WHERE id = %d",
				$capacity_id
			)
		) );
	}
}
