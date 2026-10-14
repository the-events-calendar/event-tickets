<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Main as Tickets_Main;
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
	 * This suite runs with The Events Calendar's custom tables off, so nothing saves the event occurrences.
	 *
	 * @test
	 */
	public function should_move_the_resolved_dates_when_the_classic_editor_moves_the_event_without_custom_tables(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );

		$this->send_classic_event_save( $event_id, $moved );

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * @test
	 */
	public function should_resolve_each_ticket_once_when_a_save_without_custom_tables_changes_every_event_date(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$resolved    = [];
		add_action(
			'tec_tickets_ticket_dates_updated',
			static function ( int $id ) use ( &$resolved ): void {
				$resolved[] = $id;
			}
		);

		$this->send_classic_event_save( $event_id, $event_start->modify( '+3 days' ) );
		do_action( 'tec_shutdown' );

		$this->assertSame( [ $ticket_id ], $resolved );
	}

	/**
	 * `tribe_update_event()` writes the event dates after the post is saved, so only the shutdown sees them.
	 *
	 * @test
	 */
	public function should_resolve_a_programmatic_move_of_the_event_on_shutdown_without_custom_tables(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );

		tribe_update_event(
			$event_id,
			[
				'EventStartDate' => $moved->format( 'Y-m-d' ),
				'EventStartTime' => $moved->format( 'H:i:s' ),
				'EventEndDate'   => $moved->modify( '+3 hours' )->format( 'Y-m-d' ),
				'EventEndTime'   => $moved->modify( '+3 hours' )->format( 'H:i:s' ),
				'EventTimezone'  => 'UTC',
			]
		);
		do_action( 'tec_shutdown' );

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * With the custom tables on, The Events Calendar saves the occurrences and the tickets are resolved then.
	 *
	 * @test
	 */
	public function should_leave_the_tickets_to_the_occurrences_save_while_the_custom_tables_are_active(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$dates       = [ $this->get_ticket_start( $ticket_id ), $this->get_ticket_end( $ticket_id ) ];
		$was_active  = tribe()->getVar( 'ct1_fully_activated' );
		tribe()->setVar( 'ct1_fully_activated', true );

		update_post_meta( $event_id, '_EventStartDate', $event_start->modify( '+3 days' )->format( 'Y-m-d H:i:s' ) );
		do_action( 'tec_shutdown' );
		tribe()->setVar( 'ct1_fully_activated', $was_active );

		$this->assertSame( $dates, [ $this->get_ticket_start( $ticket_id ), $this->get_ticket_end( $ticket_id ) ] );
	}

	/**
	 * @test
	 */
	public function should_resolve_a_ticket_moved_to_another_event_against_that_event(): void {
		$event_start     = $this->get_future_event_start();
		$event_id        = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id       = $this->create_ruled_ticket( $event_id );
		$new_event_start = $event_start->modify( '+1 month' );
		$new_event_id    = $this->create_event( $new_event_start->format( 'Y-m-d H:i:s' ) );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertTrue( Tickets_Main::instance()->move_ticket_types()->move_ticket_type( $ticket_id, $new_event_id ) );

		$this->assert_resolved_from( $new_event_start, $ticket_id );
	}

	/**
	 * Off an event the rule has no event dates to count from, so the dates it resolved stay as the ticket's own.
	 *
	 * @test
	 */
	public function should_drop_the_rule_of_a_ticket_moved_to_a_post_that_is_not_an_event(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, Rule::from_stored( tribe( Rule_Store::class )->get( $ticket_id ), Window_Kind::sales() ) );
		$dates   = [ $this->get_ticket_start( $ticket_id ), $this->get_ticket_end( $ticket_id ) ];
		$page_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertTrue( Tickets_Main::instance()->move_ticket_types()->move_ticket_type( $ticket_id, $page_id ) );

		$this->assertSame( [], tribe( Rule_Store::class )->get( $ticket_id ) );
		$this->assertSame( $dates, [ $this->get_ticket_start( $ticket_id ), $this->get_ticket_end( $ticket_id ) ] );
		$this->assertFalse( metadata_exists( 'post', $ticket_id, Ticket_Dates::TIMEZONE_META_KEY ) );
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

	/**
	 * Saves the event from the classic editor, moving it to a new start for three hours.
	 *
	 * @param int               $event_id The event post ID.
	 * @param DateTimeImmutable $start    The new event start, in the new event timezone.
	 *
	 * @return void
	 */
	private function send_classic_event_save( int $event_id, DateTimeImmutable $start ): void {
		$end = $start->modify( '+3 hours' );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$_POST = [
			'ecp_nonce'      => wp_create_nonce( 'tribe_events' ),
			'post_ID'        => $event_id,
			'EventStartDate' => $start->format( 'Y-m-d' ),
			'EventStartTime' => $start->format( 'H:i:s' ),
			'EventEndDate'   => $end->format( 'Y-m-d' ),
			'EventEndTime'   => $end->format( 'H:i:s' ),
			'EventTimezone'  => $start->getTimezone()->getName(),
		];

		wp_update_post( [ 'ID' => $event_id ] );

		$_POST = [];
	}
}
