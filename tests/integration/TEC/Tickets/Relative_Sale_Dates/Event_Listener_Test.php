<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Ticket_Actions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Events__Editor__Meta as Editor_Meta;
use WP_REST_Request;

class Event_Listener_Test extends Controller_Test_Case {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	protected $controller_class = Event_Listener::class;

	/**
	 * The registered meta keys before the test, restored after it: registering meta is global.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $global_meta_keys_backup = null;

	/**
	 * @before
	 */
	public function register_controller(): void {
		$this->make_controller()->register();
	}

	/**
	 * @before
	 */
	public function backup_global_meta_keys(): void {
		global $wp_meta_keys;
		$this->global_meta_keys_backup = $wp_meta_keys;
	}

	/**
	 * @after
	 */
	public function restore_global_meta_keys(): void {
		global $wp_meta_keys;
		$wp_meta_keys = $this->global_meta_keys_backup;
	}

	/**
	 * @test
	 */
	public function should_move_the_resolved_dates_when_the_classic_editor_moves_the_event(): void {
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
	public function should_move_the_resolved_dates_when_the_block_editor_moves_the_event(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// The block editor registers the event metas for REST, and this suite runs without it.
		( new Editor_Meta() )->register();

		$request = new WP_REST_Request( 'PUT', "/wp/v2/tribe_events/{$event_id}" );
		$request->set_body_params(
			[
				'meta' => [
					'_EventStartDate' => $moved->format( 'Y-m-d H:i:s' ),
					'_EventEndDate'   => $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ),
				],
			]
		);
		$this->assertSame( 200, rest_do_request( $request )->get_status() );

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * @test
	 */
	public function should_reschedule_the_sales_actions_when_only_the_event_timezone_changes(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$in_new_york = new DateTimeImmutable( $event_start->format( 'Y-m-d H:i:s' ), new DateTimeZone( 'America/New_York' ) );

		$this->send_classic_event_save( $event_id, $in_new_york );

		$this->assert_resolved_from( $in_new_york, $ticket_id );
	}

	/**
	 * @test
	 */
	public function should_resolve_each_ticket_once_when_a_save_changes_every_event_date(): void {
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

		$this->send_classic_event_save( $event_id, ( new DateTimeImmutable( $event_start->format( 'Y-m-d H:i:s' ), new DateTimeZone( 'America/New_York' ) ) )->modify( '+3 days' ) );

		$this->assertSame( [ $ticket_id ], $resolved );
	}

	/**
	 * @test
	 */
	public function should_resolve_a_programmatic_change_of_the_event_start_on_shutdown(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );

		update_post_meta( $event_id, '_EventStartDate', $moved->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $event_id, '_EventEndDate', $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ) );
		do_action( 'tec_shutdown' );

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * @test
	 */
	public function should_leave_no_sales_action_scheduled_when_the_move_inverts_the_window(): void {
		$event_start  = $this->get_future_event_start();
		$event_id     = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$sales_start  = $event_start->modify( '-2 weeks' );
		$specific_end = $event_start->modify( '-4 days' );
		$ticket_id    = $this->create_tc_ticket(
			$event_id,
			1,
			[
				'ticket_start_date' => $sales_start->format( 'Y-m-d' ),
				'ticket_start_time' => $sales_start->format( 'H:i:s' ),
				'ticket_end_date'   => $specific_end->format( 'Y-m-d' ),
				'ticket_end_time'   => $specific_end->format( 'H:i:s' ),
			]
		);
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[
				'start' => $this->relative( 2, Rule::UNIT_WEEKS ),
				'end'   => [ 'mode' => 'specific' ],
			]
		);
		tribe( Ticket_Actions::class )->sync_ticket_dates_actions( $ticket_id );
		$this->assertCount( 1, $this->get_scheduled_timestamps( Ticket_Actions::TICKET_START_SALES_HOOK, $ticket_id ) );
		$this->assertCount( 1, $this->get_scheduled_timestamps( Ticket_Actions::TICKET_END_SALES_HOOK, $ticket_id ) );
		// A month later, two weeks before the event is past the specific end.
		$moved = $event_start->modify( '+1 month' );

		$this->send_classic_event_save( $event_id, $moved );

		$moved_sales_start = $moved->modify( '-2 weeks' );
		$this->assertSame( [ $moved_sales_start->format( 'Y-m-d' ), $moved_sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $specific_end->format( 'Y-m-d' ), $specific_end->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
		$this->assertSame( [], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_START_SALES_HOOK, $ticket_id ) );
		$this->assertSame( [], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_END_SALES_HOOK, $ticket_id ) );
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
	 * Asserts a ticket's dates and sales actions follow the rule "2 weeks before the start to 1 day before the start".
	 *
	 * @param DateTimeImmutable $event_start The event start the ticket should be resolved from, in the event timezone.
	 * @param int               $ticket_id   The ticket post ID.
	 *
	 * @return void
	 */
	private function assert_resolved_from( DateTimeImmutable $event_start, int $ticket_id ): void {
		$sales_start = $event_start->modify( '-2 weeks' );
		$sales_end   = $event_start->modify( '-1 day' );
		// Ticket_Actions schedules each action 30 minutes ahead of the date it announces.
		$lead_time = 30 * MINUTE_IN_SECONDS;

		$this->assertSame( [ $sales_start->format( 'Y-m-d' ), $sales_start->format( 'H:i:s' ) ], $this->get_ticket_start( $ticket_id ) );
		$this->assertSame( [ $sales_end->format( 'Y-m-d' ), $sales_end->format( 'H:i:s' ) ], $this->get_ticket_end( $ticket_id ) );
		$this->assertSame( [ $sales_start->getTimestamp() - $lead_time ], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_START_SALES_HOOK, $ticket_id ) );
		$this->assertSame( [ $sales_end->getTimestamp() - $lead_time ], $this->get_scheduled_timestamps( Ticket_Actions::TICKET_END_SALES_HOOK, $ticket_id ) );
	}

	/**
	 * Creates a Tickets Commerce ticket and stores the rule "2 weeks before the start to 1 day before the start" on it.
	 *
	 * @param int                   $event_id  The event post ID.
	 * @param array<string,string>  $overrides The ticket data to override.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_ruled_ticket( int $event_id, array $overrides = [] ): int {
		$ticket_id = $this->create_tc_ticket( $event_id, 1, $overrides );
		tribe( Rule_Store::class )->save(
			$ticket_id,
			[
				'start' => $this->relative( 2, Rule::UNIT_WEEKS ),
				'end'   => $this->relative( 1, Rule::UNIT_DAYS ),
			]
		);

		return $ticket_id;
	}

	/**
	 * Ticket_Actions schedules nothing for a sales window that has already ended, so the events are a year away.
	 *
	 * @return DateTimeImmutable An event start at 19:00 UTC, a year from now.
	 */
	private function get_future_event_start(): DateTimeImmutable {
		return new DateTimeImmutable( ( new DateTimeImmutable( '+1 year' ) )->format( 'Y-m-d 19:00:00' ) );
	}

	/**
	 * Saves the event from the classic editor, moving it to a new start and timezone for three hours.
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
