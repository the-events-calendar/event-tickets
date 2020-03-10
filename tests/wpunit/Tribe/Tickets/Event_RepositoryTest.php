<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class Event_RepositoryTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;

	/**
	 * An array of events grouped by their cost.
	 *
	 * @var array
	 */
	protected $events = [];

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
	}

	/**
	 * It should allow filtering events by ticket cost
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_ticket_cost() {
		$all_events = $this->create_test_events( 3, 1.5 );

		$this->assertEqualSets(
			$all_events,
			tribe_events()->get_ids()
		);
		$this->assertEqualSets( [
			$this->events['paypal_eq'],
		], tribe_events()->where( 'cost', 3 )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', 3, '>' )->get_ids() );
		$this->assertEqualSets( [
			$this->events['rsvp'],
			$this->events['paypal_lt'],
		], tribe_events()->where( 'cost', 3, '<' )->get_ids() );
		$this->assertEqualSets( [
			$this->events['rsvp'],
			$this->events['paypal_lt'],
			$this->events['paypal_eq'],
		], tribe_events()->where( 'cost', 3, '<=' )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', 3, '>=' )->get_ids() );
		$this->assertEqualSets( [
			$this->events['rsvp'],
			$this->events['paypal_lt'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', 3, '!=' )->get_ids() );
	}

	/**
	 * Creates an event without tickets and a number of events with different tickets
	 * costs and symbols.
	 *
	 * @param float $cost_pivot The cost pivot.
	 * @param float $cost_delta The cost delta.
	 *
	 * @return array An array of the generated event IDs.
	 */
	protected function create_test_events( $cost_pivot = 3.0, $cost_delta = 1.5 ) {
		$this->events['with_cost_meta']  = [
			$this->factory()->event->create( [
					'meta_input' => [
						'_EventCost'             => $cost_pivot,
						'_EventCurrencySymbol'   => 'USD',
						'_EventCurrencyPosition' => 'prefix',
					],
				]
			),
		];
		$this->events['without_tickets'] = [ $this->factory()->event->create() ];
		$this->events['with_tickets']    = $this->create_events_with_costs( $cost_pivot, $cost_delta );

		return array_merge( $this->events['without_tickets'], $this->events['with_tickets'], $this->events['with_cost_meta'] );
	}

	/**
	 * Returns a map in the shape [ <name> => <ID> ] relating names to event IDs.
	 *
	 * The method will setup 4 events: one with an RSVP ticket, one with a PayPal ticket with
	 * a cost less than the cost pivot, one with a PayPal ticket with a cost equal to the
	 *cost pivot and one with a PayPal ticket with a cost greater than the cost pivot.
	 *
	 * @param float $cost_pivot        The cost that will be used to create the tickets.
	 * @param float $cost_delta        The delta that will be used to create events with costs less than and
	 *                                 greater than the cost pivot.
	 *
	 * @return array An event name to event post ID map ready to be `extract`ed.
	 *
	 * @throws \InvalidArgumentException If the cost pivot is less than the cost delta; negative cost tickets do not
	 *                                   make sense.
	 */
	protected function create_events_with_costs( float $cost_pivot, float $cost_delta = 2 ): array {
		if ( $cost_pivot < $cost_delta ) {
			throw new \InvalidArgumentException( 'The "cost_pivot" should be greater than or equal to 2.' );
		}

		$costs = [
			'lt' => $cost_pivot - $cost_delta,
			'eq' => $cost_pivot,
			'gt' => $cost_pivot + $cost_delta
		];

		// For each cost create a PayPal ticket assigned to an event.
		$events = [];
		foreach ( $costs as $cost_name => $cost ) {
			$this_name                  = sprintf( 'paypal_%s', $cost_name );
			$this_event_id              = $this->factory()->event->create();
			$this->events[ $this_name ] = $this_event_id;
			$events[]                   = $this_event_id;
			$this->create_paypal_ticket_basic( $this_event_id, $cost );
		}

		// Create an RSVP ticket for another event.
		$this->events['rsvp'] = $this->factory()->event->create();
		$events[]             = $this->events['rsvp'];
		$this->create_rsvp_ticket( $this->events['rsvp'] );

		return $events;
	}

	/**
	 * It should return event with RSVP invitation when filtering by cost of zero
	 *
	 * @test
	 */
	public function should_return_event_with_rsvp_invitation_when_filtering_by_cost_of_zero() {
		$this->create_test_events( 3, 1.5 );

		$this->assertEqualSets(
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

		$this->assertEqualSets(
			$all_events,
			tribe_events()->get_ids()
		);
		$this->assertEqualSets( [
			$this->events['paypal_eq'],
		], tribe_events()->where( 'cost', 3 )->get_ids() );
		$where  = tribe_events()->where( 'cost_greater_than', 3 );
		$actual = $where->get_ids();
		$this->assertEqualSets( [
			$this->events['paypal_gt'],
		], $actual );
		$this->assertEqualSets( [
			$this->events['rsvp'],
			$this->events['paypal_lt'],
		], tribe_events()->where( 'cost_less_than', 3 )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost_between', 2, 6 )->get_ids() );
	}

	/**
	 * It should allow filtering events by cost and symbol
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_cost_and_symbol() {
		$this->create_test_events( 3, 1.5 );
		/** @var \Tribe__Tickets__Commerce__Currency $currency */
		$currency            = tribe( 'tickets.commerce.currency' );
		$tpp_currency_code   = $currency->get_currency_code();
		$tpp_currency_symbol = array_map(
			                       'html_entity_decode',
			                       array_values( $currency->get_symbols_for_codes( $tpp_currency_code ) )
		                       )[0];

		$this->assertEqualSets( [
			$this->events['paypal_lt'],
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', [ 1, 6 ], 'BETWEEN', $tpp_currency_code )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_lt'],
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', [ 1, 6 ], 'BETWEEN', $tpp_currency_symbol )->get_ids() );
		$this->assertEmpty( tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', 'ƒ' )->get_ids() );
		$this->assertEmpty( tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', 'CAD' )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_lt'],
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'cost', [ 0, 6 ], 'BETWEEN', [ 'USD', '$' ] )->get_ids() );
	}

	/**
	 * It should allow filtering events by having tickets or not
	 *
	 * @test
	 */
	public function should_allow_filtering_events_by_having_tickets_or_not() {
		$this->create_test_events();

		$this->assertEqualSets( array_merge(
			$this->events['with_cost_meta'],
			$this->events['without_tickets'],
			[ $this->events['rsvp'] ]
		), tribe_events()->where( 'has_tickets', false )->get_ids() );
		$this->assertEqualSets( [
			$this->events['paypal_lt'],
			$this->events['paypal_eq'],
			$this->events['paypal_gt'],
		], tribe_events()->where( 'has_tickets', true )->get_ids() );
	}

	/**
	 * It should allow filtering by events that have RSVP tickets.
	 *
	 * @test
	 */
	public function should_allow_filtering_by_events_that_have_rsvp_tickets_() {
		$this->create_test_events();

		$actual = tribe_events()->where( 'has_rsvp', false )->get_ids();
		$this->assertEqualSets( array_merge(
			$this->events['with_cost_meta'],
			$this->events['without_tickets'],
			[
				$this->events['paypal_lt'],
				$this->events['paypal_eq'],
				$this->events['paypal_gt'],
			]
		), $actual );
		$this->assertEqualSets( [
			$this->events['rsvp']
		], tribe_events()->where( 'has_rsvp', true )->get_ids() );
	}

	/**
	 * It should still allow filtering event by base repository filters
	 *
	 * @test
	 */
	public function should_still_allow_filtering_event_by_base_repository_filters() {
		$three_days = '72';
		$this->factory()->event->create_many( 10, [ 'time_space' => $three_days ] );
		$this->assertCount( 3, tribe_events()->per_page( 10 )->where( 'starts_before', '+10 days' )->get_ids() );
	}

	/**
	 * It should allow itself being decorated and still use the base repository filters
	 *
	 * @test
	 */
	public function should_allow_itself_being_decorated_and_still_use_the_base_repository_filters() {
		$three_days = '72';
		$this->factory()->event->create_many( 10, [ 'time_space' => $three_days ] );

		$decorator = new class( tribe_events() ) extends \Tribe__Repository__Decorator {
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
}
