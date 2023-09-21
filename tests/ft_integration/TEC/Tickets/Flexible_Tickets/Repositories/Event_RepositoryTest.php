<?php

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events\Custom_Tables\V1\Tables\Events;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as Tickets_Commerce_Ticket_Maker;
use Tribe__Repository__Decorator;
use Tribe__Tickets__Commerce__Currency;
use Tribe__Tickets__Data_API as Data_API;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;

class Event_RepositoryTest extends WPTestCase {
	use Tickets_Commerce_Ticket_Maker;
	use Attendee_Maker;
	use RSVP_Ticket_Maker;
	use Series_Pass_Factory;

	private $events = [];

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @before
	 * @after
	 */
	public function empty_custom_tables(): void {
		global $wpdb;
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach (
			[
				Occurrences::table_name(),
				Series_Relationships::table_name(),
				Events::table_name()
			] as $table
		) {
			$wpdb->query( "TRUNCATE TABLE {$table}" );
		}
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );
	}

	private function given_an_event_without_attendees(): int {
		static $hash = 1;

		return tribe_events()->set_args( [
			'title'      => 'Event without attendees ' . $hash ++,
			'status'     => 'publish',
			'start_date' => '2202-02-02 02:02:02',
			'duration'   => 2 * HOUR_IN_SECONDS,
		] )->create()->ID;
	}

	/**
	 * @return array{0: int, 1: int[]}
	 */
	private function given_an_event_with_attendees( int $count, int $user_id = 0 ): array {
		$id = $this->given_an_event_without_attendees();

		$ticket_id = $this->create_tc_ticket( $id );
		foreach ( range( 1, $count ) as $k ) {
			$attendee_ids[] = $this->create_attendee_for_ticket( $ticket_id, $id, [ 'user_id' => $user_id ?: 0 ] );
		}

		return [ $id, $attendee_ids ];
	}

	private function assert_equal_set_of_provisional_ids( array $expected_ids, array $actual ) {
		$provisionals = tribe( Provisional_Post::class );
		$provisional_ids = array_merge( ...array_map( function ( int $id ) use ( $provisionals ): array {
			return $provisionals->is_provisional_post_id( $id ) ?
				[ $id ]
				: Occurrence::where( 'post_id', '=', $id )->map( fn( Occurrence $o ) => $o->provisional_id );
		}, $expected_ids ) );
		$this->assertEqualSets( $provisional_ids, $actual );
	}

	public function setUp(): void {
		parent::setUp();

		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tickets Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function test_filter_events_by_has_attendees(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// Baseline, no filters.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->get_ids()
		);

		// Get Events with Attendees.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->where( 'has_attendees', true )->get_ids()
		);

		// Get Events without Attendees.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2
			],
			tribe_events()->where( 'has_attendees', false )->get_ids()
		);
	}

	public function test_filter_events_by_attendee(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// By the only attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[ $event_with_attendees_1 ],
			tribe_events()->where( 'attendee', $event_1_attendees[0] )->get_ids()
		);

		// By the first attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees[0] )->get_ids()
		);
		// By the second attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees[1] )->get_ids()
		);
		// By array of Attendee IDs.
		$this->assert_equal_set_of_provisional_ids
		(
			[ $event_with_attendees_2 ],
			tribe_events()->where( 'attendee', $event_2_attendees )->get_ids()
		);

		// A post ID that will not map to an Attendee.
		$this->assert_equal_set_of_provisional_ids
		(
			[],
			tribe_events()->where( 'attendee', PHP_INT_MAX )->get_ids()
		);
	}

	public function test_filter_events_by_attendee_not_in(): void {
		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1 );
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 2 );

		// By the only attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_2
			],
			tribe_events()->where( 'attendee__not_in', $event_1_attendees[0] )->get_ids()
		);

		// By the first attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees[0] )->get_ids()
		);
		// By the second attendee ID.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees[1] )->get_ids()
		);
		// By array of Attendee IDs.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1
			],
			tribe_events()->where( 'attendee__not_in', $event_2_attendees )->get_ids()
		);

		// A post ID that will not map to an Attendee.
		$this->assert_equal_set_of_provisional_ids
		(
			[
				$event_without_attendees_1,
				$event_without_attendees_2,
				$event_with_attendees_1,
				$event_with_attendees_2
			],
			tribe_events()->where( 'attendee__not_in', PHP_INT_MAX )->get_ids()
		);
	}

	public function test_filter_events_by_attendee_user(): void {
		$user_id_1 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_2 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_3 = static::factory()->user->create( [ 'role' => 'administrator' ] );

		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_1 );

		// Create the following attendees as another user.
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_2 );

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_with_attendees_1
		],
			tribe_events()->where( 'attendee_user', $user_id_1 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user', $user_id_2 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_with_attendees_1,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user', [ $user_id_1, $user_id_2 ] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_with_attendees_1,
		],
			tribe_events()->where( 'attendee_user', [ $user_id_1, $user_id_3 ] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [], tribe_events()->where( 'attendee_user', $user_id_3 )->get_ids() );
	}

	public function test_filter_events_by_attendee_user_not_in(): void {
		$this->markTestSkipped( 'attendee_user__not_in support not yet implemented' );

		$user_id_1 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_2 = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$user_id_3 = static::factory()->user->create( [ 'role' => 'administrator' ] );

		$event_without_attendees_1 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_1, $event_1_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_1 );

		// Create the following attendees as another user.
		$event_without_attendees_2 = $this->given_an_event_without_attendees();
		[ $event_with_attendees_2, $event_2_attendees ] = $this->given_an_event_with_attendees( 1, $user_id_2 );

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', $user_id_1 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_1
		],
			tribe_events()->where( 'attendee_user__not_in', $user_id_2 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_without_attendees_1,
			$event_without_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', [ $user_id_1, $user_id_2 ] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids
		( [
			$event_without_attendees_1,
			$event_without_attendees_2,
			$event_with_attendees_2
		],
			tribe_events()->where( 'attendee_user__not_in', [ $user_id_1, $user_id_3 ] )->get_ids()
		);
	}


	private function given_an_event( array $overrides = [] ): int {
		return tribe_events()->set_args( array_merge( [
			'title'      => 'Event with cost meta',
			'status'     => 'publish',
			'start_date' => '2202-02-02 02:02:02',
			'duration'   => 2 * HOUR_IN_SECONDS,
		], $overrides ) )->create()->ID;
	}


	private function given_a_recurring_event( array $overrides = [] ): int {
		return tribe_events()->set_args( array_merge( [
			'title'      => 'Event with cost meta',
			'status'     => 'publish',
			'start_date' => '2202-02-02 02:02:02',
			'duration'   => 2 * HOUR_IN_SECONDS,
			'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
		], $overrides ) )->create()->ID;
	}

	private function create_events_with_costs( float $cost_pivot, float $cost_delta = 2 ): array {
		if ( $cost_pivot < $cost_delta ) {
			throw new InvalidArgumentException( 'The "cost_pivot" should be greater than or equal to 2.' );
		}

		$costs = [
			'lt' => $cost_pivot - $cost_delta,
			'eq' => $cost_pivot,
			'gt' => $cost_pivot + $cost_delta
		];

		// For each cost create a Tickets Commerce ticket assigned to an event.
		$events = [];
		foreach ( $costs as $cost_name => $cost ) {
			$this_name = sprintf( 'commerce_%s', $cost_name );
			$this_event_id = $this->given_an_event( [ 'title' => $this_name ] );
			$this->events[ $this_name ] = $this_event_id;
			$events[] = $this_event_id;
			$this->create_tc_ticket( $this_event_id, $cost );
		}

		// Create an RSVP ticket for another event.
		$this->events['rsvp'] = $this->given_an_event( [ 'title' => 'RSVP' ] );
		$events[] = $this->events['rsvp'];
		$this->create_rsvp_ticket( $this->events['rsvp'] );

		return $events;
	}

	private function create_test_events( $cost_pivot = 3.0, $cost_delta = 1.5 ) {
		$this->events['with_cost_meta'] = [
			tribe_events()->set_args( [
				'title'                  => 'Event with cost meta',
				'status'                 => 'publish',
				'start_date'             => '2202-02-02 02:02:02',
				'duration'               => 2 * HOUR_IN_SECONDS,
				'_EventCost'             => $cost_pivot,
				'_EventCurrencySymbol'   => 'USD',
				'_EventCurrencyPosition' => 'prefix',
			] )->create()->ID
		];
		$this->events['without_tickets'] = [
			tribe_events()->set_args( [
				'title'      => 'Event without tickets',
				'status'     => 'publish',
				'start_date' => '2202-02-02 02:02:02',
				'duration'   => 2 * HOUR_IN_SECONDS,
			] )->create()->ID
		];
		$this->events['with_tickets'] = $this->create_events_with_costs( $cost_pivot, $cost_delta );

		return array_merge( $this->events['without_tickets'], $this->events['with_tickets'], $this->events['with_cost_meta'] );
	}

	/**
	 * It should allow filtering events by ticket cost.
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_ticket_cost() {
		$all_events = $this->create_test_events( 3, 1.5 );

		$this->assert_equal_set_of_provisional_ids(
			$all_events,
			tribe_events()->get_ids()
		);
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_eq'],
		], tribe_events()->where( 'cost', 3 )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', 3, '>' )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['rsvp'],
			$this->events['commerce_lt'],
		], tribe_events()->where( 'cost', 3, '<' )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['rsvp'],
			$this->events['commerce_lt'],
			$this->events['commerce_eq'],
		], tribe_events()->where( 'cost', 3, '<=' )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', 3, '>=' )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['rsvp'],
			$this->events['commerce_lt'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', 3, '!=' )->get_ids() );
	}

	/**
	 * It should return event with RSVP invitation when filtering by cost of zero
	 *
	 * @test
	 */
	public function should_return_event_with_rsvp_invitation_when_filtering_by_cost_of_zero() {
		$this->create_test_events( 3, 1.5 );

		$this->assert_equal_set_of_provisional_ids(
			[ $this->events['rsvp'] ],
			tribe_events()->where( 'cost', 0 )->get_ids()
		);
	}

	/**
	 * It should allow filtering tickets by gt, lt and between filters.
	 *
	 * @test
	 */
	public function should_allow_filtering_tickets_by_gt_lt_and_between_filters() {
		$all_events = $this->create_test_events( 3, 1.5 );

		$this->assert_equal_set_of_provisional_ids(
			$all_events,
			tribe_events()->get_ids()
		);
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_eq'],
		], tribe_events()->where( 'cost', 3 )->get_ids() );
		$where = tribe_events()->where( 'cost_greater_than', 3 );
		$actual = $where->get_ids();
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_gt'],
		], $actual );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['rsvp'],
			$this->events['commerce_lt'],
		], tribe_events()->where( 'cost_less_than', 3 )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost_between', 2, 6 )->get_ids() );
	}

	/**
	 * It should allow filtering events by cost and symbol
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_cost_and_symbol() {
		$this->create_test_events( 3, 1.5 );
		/** @var Tribe__Tickets__Commerce__Currency $currency */
		$currency = tribe( 'tickets.commerce.currency' );
		$tpp_currency_code = $currency->get_currency_code();
		$tpp_currency_symbol = array_map(
			'html_entity_decode',
			array_values( $currency->get_symbols_for_codes( $tpp_currency_code ) )
		)[0];

		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_lt'],
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', [ 1, 6 ], 'BETWEEN', $tpp_currency_code )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_lt'],
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', [ 1, 6 ], 'BETWEEN', $tpp_currency_symbol )->get_ids() );
		$this->assertEmpty( tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', 'ƒ' )->get_ids() );
		$this->assertEmpty( tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', 'CAD' )->get_ids() );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_lt'],
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', [ 'USD', '$' ] )->get_ids() );
	}

	/**
	 * It should allow filtering events by having tickets or not
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_having_tickets_or_not() {
		$this->create_test_events();

		$repo = tribe_events();
		$actual = $repo->where( 'has_tickets', false )->get_ids();
		$this->assert_equal_set_of_provisional_ids( array_merge(
			$this->events['with_cost_meta'],
			$this->events['without_tickets'],
			[ $this->events['rsvp'] ]
		), $actual );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['commerce_lt'],
			$this->events['commerce_eq'],
			$this->events['commerce_gt'],
		], tribe_events()->where( 'has_tickets', true )->get_ids() );
	}

	/**
	 * It should allow filtering by events that have RSVP tickets.
	 *
	 * @test
	 */
	public function should_allow_filtering_by_events_that_have_rsvp_tickets() {
		$this->create_test_events();

		$actual = tribe_events()->where( 'has_rsvp', false )->get_ids();
		$this->assert_equal_set_of_provisional_ids( array_merge(
			$this->events['with_cost_meta'],
			$this->events['without_tickets'],
			[
				$this->events['commerce_lt'],
				$this->events['commerce_eq'],
				$this->events['commerce_gt'],
			]
		), $actual );
		$this->assert_equal_set_of_provisional_ids( [
			$this->events['rsvp']
		], tribe_events()->where( 'has_rsvp', true )->get_ids() );
	}

	/**
	 * It should allow filtering by events that have rsvp and/or tickets.
	 *
	 * @test
	 */
	public function should_allow_filtering_by_events_that_have_rsvp_and_or_tickets() {
		$this->create_test_events();

		$actual = tribe_events()->where( 'has_rsvp_or_tickets', true )->get_ids();
		// Retrieve all events with either tickets or rsvp
		$this->assert_equal_set_of_provisional_ids( array_unique( array_merge( $this->events['with_tickets'], [ $this->events['rsvp'] ] ) ), $actual );
		// Retrieve no events without tickets or rsvp
		$this->assertFalse( in_array( $actual, $this->events['without_tickets'], true ) );

		$actual = tribe_events()->where( 'has_rsvp_or_tickets', false )->get_ids();
		// Retrieve only events without either tickets or rsvp
		$this->assert_equal_set_of_provisional_ids( array_unique( array_merge( $this->events['without_tickets'], $this->events['with_cost_meta'] ) ), $actual );
		// Retrieve no events with tickets or rsvp
		$this->assertFalse( in_array( $actual, array_merge( $this->events['with_tickets'], [ $this->events['rsvp'] ] ), true ) );
	}

	/**
	 * @return int[]
	 */
	public function given_many_events_spaced( int $count, int $distance_in_hours ): array {
		$start = new \DateTime( "+$distance_in_hours hours" );
		$interval = new \DateInterval( 'PT' . $distance_in_hours . 'H' );

		$ids = [];
		foreach ( range( 1, $count ) as $k ) {
			$ids[] = tribe_events()->set_args( [
				'title'      => 'Event ' . $k,
				'status'     => 'publish',
				'start_date' => $start->format( 'Y-m-d H:i:s' ),
				'duration'   => 3 * HOUR_IN_SECONDS,
			] )->create();
			$start->add( $interval );
		}

		return $ids;
	}

	/**
	 * It should still allow filtering event by base repository filters
	 *
	 * @test
	 */
	public function should_still_allow_filtering_event_by_base_repository_filters() {
		$three_days = '72';
		$this->given_many_events_spaced( 10, $three_days );
		$this->assertCount( 3, tribe_events()->per_page( 10 )->where( 'starts_before', '+10 days' )->get_ids() );
	}

	/**
	 * It should allow itself being decorated and still use the base repository filters
	 *
	 * @test
	 */
	public function should_allow_itself_being_decorated_and_still_use_the_base_repository_filters() {
		$three_days = '72';
		$this->given_many_events_spaced( 10, $three_days );

		$decorator = new class( tribe_events() ) extends Tribe__Repository__Decorator {
			public function __construct( $decorated ) {
				$this->decorated = $decorated;
				$this->decorated->add_schema_entry( 'starts_after_today', [ $this, 'filter_by_starts_after_today' ] );
			}

			public function filter_by_starts_after_today() {
				return $this->decorated->where( 'starts_after', 'today' );
			}
		};

		$this->assertCount( 3, $decorator->per_page( 10 )->where( 'starts_before', '+10 days' )->get_ids() );
		$this->assertCount( 3, $decorator->per_page( 10 )->where( 'starts_after_today' )->get_ids() );
	}

	/**
	 * It should correctly filter events by ticket when belonging to Series with Series Passes
	 *
	 * @test
	 */
	public function should_correctly_filter_events_by_ticket_when_belonging_to_series_with_series_passes(): void {
		$single_event = $this->given_an_event();
		$single_event_with_tickets = $this->given_an_event();
		$this->create_tc_ticket( $single_event_with_tickets, 23 );
		$single_event_with_rsvp = $this->given_an_event();
		$this->create_rsvp_ticket( $single_event_with_rsvp );
		$not_ticketed_series = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		$single_event_in_not_ticketed_series = $this->given_an_event( [ 'series' => $not_ticketed_series ] );
		$recurring_event_in_not_ticketed_series = $this->given_a_recurring_event( [ 'series' => $not_ticketed_series ] );
		$ticketed_series = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		$this->create_tc_series_pass( $ticketed_series, 89 );
		$single_event_in_ticketed_series = $this->given_an_event( [ 'series' => $ticketed_series ] );
		$recurring_event_in_ticketed_series = $this->given_a_recurring_event( [ 'series' => $ticketed_series ] );

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_tickets
		],
			tribe_events()->where( 'cost', 23 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_rsvp
		],
			tribe_events()->where( 'has_rsvp', true )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_with_tickets,
			$single_event_in_not_ticketed_series,
			$recurring_event_in_not_ticketed_series,
			$single_event_in_ticketed_series,
			$recurring_event_in_ticketed_series,
		],
			tribe_events()->where( 'has_rsvp', false )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_tickets,
			$single_event_in_ticketed_series,
			$recurring_event_in_ticketed_series,
		],
			tribe_events()->where( 'has_tickets', true )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_with_rsvp,
			$single_event_in_not_ticketed_series,
			$recurring_event_in_not_ticketed_series,
		],
			tribe_events()->where( 'has_tickets', false )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_tickets,
			$single_event_with_rsvp,
			$single_event_in_ticketed_series,
			$recurring_event_in_ticketed_series
		],
			tribe_events()->where( 'has_rsvp_or_tickets', true )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_in_not_ticketed_series,
			$recurring_event_in_not_ticketed_series,
		],
			tribe_events()->where( 'has_rsvp_or_tickets', false )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_tickets,
			$single_event_in_ticketed_series,
			$recurring_event_in_ticketed_series,
		],
			tribe_events()->where( 'cost_currency_symbol', 'USD' )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [], tribe_events()->where( 'cost_currency_symbol', 'CAD' )->get_ids() );
	}

	/**
	 * It should correctly filter events by attendee when belonging to Series with Series Passes
	 *
	 * @test
	 */
	public function should_correctly_filter_events_by_attendee_when_belonging_to_series_with_series_passes(): void {
		$user_id_1 = static::factory()->user->create();
		$user_id_2 = static::factory()->user->create();
		$user_id_3 = static::factory()->user->create();
		$single_event = $this->given_an_event();
		$single_event_with_attendees = $this->given_an_event();
		$single_event_ticket = $this->create_tc_ticket( $single_event_with_attendees, 23 );
		$single_event_attendee_1 = $this->create_attendee_for_ticket( $single_event_ticket, $single_event_with_attendees );
		$single_event_attendee_2 = $this->create_attendee_for_ticket( $single_event_ticket, $single_event_with_attendees, [ 'user_id' => $user_id_1 ] );
		$single_event_attendee_3 = $this->create_attendee_for_ticket( $single_event_ticket, $single_event_with_attendees, [ 'user_id' => $user_id_2 ] );
		$single_event_with_rsvp = $this->given_an_event();
		$rsvp_ticket = $this->create_rsvp_ticket( $single_event_with_rsvp );
		$rsvp_attendee_1 = $this->create_attendee_for_ticket( $rsvp_ticket, $single_event_with_rsvp );
		$rsvp_attendee_2 = $this->create_attendee_for_ticket( $rsvp_ticket, $single_event_with_rsvp, [ 'user_id' => $user_id_1 ] );
		$rsvp_attendee_3 = $this->create_attendee_for_ticket( $rsvp_ticket, $single_event_with_rsvp, [ 'user_id' => $user_id_2 ] );
		$series_with_no_attendees = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		$single_event_in_series_with_no_attendees = $this->given_an_event( [ 'series' => $series_with_no_attendees ] );
		$recurring_event_in_series_with_no_attendees = $this->given_a_recurring_event( [ 'series' => $series_with_no_attendees ] );
		$series_with_attendees = static::factory()->post->create( [ 'post_type' => Series::POSTTYPE ] );
		$series_pass = $this->create_tc_series_pass( $series_with_attendees, 2 );
		$series_attendee_1 = $this->create_attendee_for_ticket( $series_pass->ID, $series_with_attendees );
		$series_attendee_2 = $this->create_attendee_for_ticket( $series_pass->ID, $series_with_attendees, [ 'user_id' => $user_id_1 ] );
		$series_attendee_3 = $this->create_attendee_for_ticket( $series_pass->ID, $series_with_attendees, [ 'user_id' => $user_id_2 ] );
		$single_event_in_series_with_attendees = $this->given_an_event( [ 'series' => $series_with_attendees ] );
		$recurring_event_in_series_with_attendees = $this->given_a_recurring_event( [ 'series' => $series_with_attendees ] );

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_attendees,
			$single_event_with_rsvp,
			$single_event_in_series_with_attendees,
			$recurring_event_in_series_with_attendees
		],
			tribe_events()->where( 'has_attendees', true )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_in_series_with_no_attendees,
			$recurring_event_in_series_with_no_attendees
		],
			tribe_events()->where( 'has_attendees', false )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_in_series_with_no_attendees,
			$recurring_event_in_series_with_no_attendees
		],
			tribe_events()->where( 'has_attendees', false )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_attendees,
		],
			tribe_events()->where( 'attendee', $single_event_attendee_1 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_attendees,
			$single_event_with_rsvp,
			$single_event_in_series_with_attendees,
			$recurring_event_in_series_with_attendees
		],
			tribe_events()->where( 'attendee', [
				$single_event_attendee_1,
				$rsvp_attendee_2,
				$series_attendee_2
			] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_with_rsvp,
			$single_event_in_series_with_no_attendees,
			$recurring_event_in_series_with_no_attendees,
			$single_event_in_series_with_attendees,
			$recurring_event_in_series_with_attendees
		],
			tribe_events()->where( 'attendee__not_in', $single_event_attendee_1 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event,
			$single_event_with_rsvp,
			$single_event_in_series_with_no_attendees,
			$recurring_event_in_series_with_no_attendees
		],
			tribe_events()->where( 'attendee__not_in', [
				$single_event_attendee_1,
				$series_attendee_2
			] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_attendees,
			$single_event_with_rsvp,
			$single_event_in_series_with_attendees,
			$recurring_event_in_series_with_attendees
		],
			tribe_events()->where( 'attendee_user', $user_id_1 )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [
			$single_event_with_attendees,
			$single_event_with_rsvp,
			$single_event_in_series_with_attendees,
			$recurring_event_in_series_with_attendees
		],
			tribe_events()->where( 'attendee_user', [ $user_id_1, $user_id_2 ] )->get_ids()
		);

		$this->assert_equal_set_of_provisional_ids( [], tribe_events()->where( 'attendee_user', [ $user_id_3 ] )->get_ids() );
	}
}
