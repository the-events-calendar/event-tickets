<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;

class Event_Listener_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	protected $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
	}

	/**
	 * @test
	 */
	public function should_keep_the_legacy_end_date_sync_away_from_ruled_tickets_only(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		// An empty end date is the one the legacy sync moves: it does not flag the ticket as edited by hand.
		$without_end_date = [
			'ticket_end_date' => '',
			'ticket_end_time' => '',
		];
		$ruled_ticket_id  = $this->create_ruled_ticket( $event_id, $without_end_date );
		$plain_ticket_id  = $this->create_tc_ticket( $event_id, 1, $without_end_date );
		$ruled_end        = $this->get_ticket_end( $ruled_ticket_id );
		$moved            = $event_start->modify( '+3 days' )->format( 'Y-m-d H:i:s' );

		update_post_meta( $event_id, '_EventStartDate', $moved );

		$this->assertSame( $ruled_end, $this->get_ticket_end( $ruled_ticket_id ) );
		$this->assertSame( $moved, get_post_meta( $plain_ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * @return Generator<string,array{0: array{mode: string, value?: int, unit?: int, anchor?: string}, 1: array{mode: string}, 2: bool}>
	 */
	public function rule_end_mode_provider(): Generator {
		// The classic editor stores this rule for a ticket made before the feature, the first time it saves it.
		yield 'specific start and end' => [ [ 'mode' => 'specific' ], [ 'mode' => 'specific' ], true ];
		yield 'relative start, specific end' => [ $this->relative( 2, WEEK_IN_SECONDS ), [ 'mode' => 'specific' ], true ];
		// The rule resolves that end to the event start itself.
		yield 'default end' => [ [ 'mode' => 'specific' ], [ 'mode' => 'default' ], false ];
		// The start is counted back from the event end, so an end moved to the event start could fall before it.
		yield 'start anchored on the event end, specific end' => [ array_merge( $this->relative( 2, HOUR_IN_SECONDS ), [ 'anchor' => Rule::ANCHOR_END ] ), [ 'mode' => 'specific' ], false ];
	}

	/**
	 * @test
	 * @dataProvider rule_end_mode_provider
	 */
	public function should_let_the_legacy_end_date_sync_move_only_an_end_the_rule_leaves_to_the_ticket( array $start, array $end, bool $follows ): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		// An empty end date is the one the legacy sync moves: it does not flag the ticket as edited by hand.
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'ticket_end_date' => '',
				'ticket_end_time' => '',
			]
		);
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[
				'start' => $start,
				'end'   => $end,
			]
		);
		$stored_end = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$moved      = $event_start->modify( '+3 days' )->format( 'Y-m-d H:i:s' );

		update_post_meta( $event_id, '_EventStartDate', $moved );

		$this->assertSame( $follows ? $moved : $stored_end, get_post_meta( $ticket_id, '_ticket_end_date', true ) );
	}

	/**
	 * The rule wrote the relative end, so the ticket has no manual-update flag, and the switch writes no new end date.
	 *
	 * @test
	 */
	public function should_keep_an_end_switched_from_relative_to_specific_on_the_same_date_when_the_event_moves(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'default' ], 'end' => $this->relative( 1, DAY_IN_SECONDS ) ] ),
				'ticket_end_date'     => '',
				'ticket_end_time'     => '',
			]
		);
		[ $end_date, $end_time ] = $this->get_ticket_end( $ticket_id );

		$this->update_ticket(
			$ticket_id,
			[
				'relative_sale_dates' => wp_json_encode( [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'specific' ] ] ),
				'ticket_end_date'     => $end_date,
				'ticket_end_time'     => $end_time,
			]
		);
		update_post_meta( $event_id, '_EventStartDate', $event_start->modify( '+3 days' )->format( 'Y-m-d H:i:s' ) );

		$this->assertSame( [ $end_date, $end_time ], $this->get_ticket_end( $ticket_id ) );
	}

	/**
	 * @test
	 */
	public function should_carry_the_rule_to_a_ticket_duplicated_to_another_event(): void {
		$event_start     = $this->get_future_event_start();
		$event_id        = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id       = $this->create_ruled_ticket( $event_id );
		$new_event_start = $event_start->modify( '+1 month' );
		$new_event_id    = $this->create_event( $new_event_start->format( 'Y-m-d H:i:s' ) );

		$duplicate_id = tribe( Module::class )->clone_ticket_to_new_post( $event_id, $new_event_id, $ticket_id );
		// What the Events Calendar PRO duplicate integration fires once every ticket of the event is cloned.
		do_action( 'tec_tickets_tickets_duplicated', [ $ticket_id => $duplicate_id ], $new_event_id, $event_id );

		$this->assertSame( tribe( Rule_Store::class )->get( $ticket_id ), tribe( Rule_Store::class )->get( $duplicate_id ) );
		$this->assert_resolved_from( $new_event_start, $duplicate_id );
	}

	/**
	 * @test
	 */
	public function should_skip_a_failed_clone_and_a_ticket_without_a_rule_when_tickets_are_duplicated(): void {
		$event_start      = $this->get_future_event_start();
		$event_id         = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ruled_ticket_id  = $this->create_ruled_ticket( $event_id );
		$plain_ticket_id  = $this->create_tc_ticket( $event_id, 1 );
		$new_event_id     = $this->create_event( $event_start->modify( '+1 month' )->format( 'Y-m-d H:i:s' ) );
		$plain_duplicate  = tribe( Module::class )->clone_ticket_to_new_post( $event_id, $new_event_id, $plain_ticket_id );
		$plain_dates      = [ $this->get_ticket_start( $plain_duplicate ), $this->get_ticket_end( $plain_duplicate ) ];

		// `clone_ticket_to_new_post()` gives `false` for a ticket it could not clone.
		do_action( 'tec_tickets_tickets_duplicated', [ $ruled_ticket_id => false, $plain_ticket_id => $plain_duplicate ], $new_event_id, $event_id );

		$this->assertSame( '', get_post_meta( $plain_duplicate, Rule_Store::META_KEY, true ) );
		$this->assertSame( $plain_dates, [ $this->get_ticket_start( $plain_duplicate ), $this->get_ticket_end( $plain_duplicate ) ] );
	}
}
