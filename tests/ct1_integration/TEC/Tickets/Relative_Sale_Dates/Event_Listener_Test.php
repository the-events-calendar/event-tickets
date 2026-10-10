<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use DateTimeImmutable;
use DateTimeZone;
use TEC\Events\Custom_Tables\V1\Updates\Controller as Updates_Controller;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Ticket_Actions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Relative_Sale_Dates_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Events__Editor__Meta as Editor_Meta;
use WP_REST_Request;

/**
 * The tickets follow an event move once The Events Calendar saves the event's occurrences, which needs its custom tables.
 *
 * The controller the plugin registers is used, not a copy: The Events Calendar watches the event meta and commits the
 * occurrences from its own container, so a test container would miss those writes.
 */
class Event_Listener_Test extends WPTestCase {
	use Relative_Sale_Dates_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * The registered meta keys before the test, restored after it: registering meta is global.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $global_meta_keys_backup = null;

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

		// The block editor sends JSON, which is what The Events Calendar reads to update the UTC dates.
		$request = new WP_REST_Request( 'PUT', "/wp/v2/tribe_events/{$event_id}" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'meta' => [
						'_EventStartDate' => $moved->format( 'Y-m-d H:i:s' ),
						'_EventEndDate'   => $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ),
					],
				]
			)
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
	 * The ticket dates keep their wall-clock times, but they now fall at other instants.
	 *
	 * @test
	 */
	public function should_reschedule_resolved_tickets_when_only_the_event_timezone_changes(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$this->send_classic_event_save( $event_id, $event_start );
		$in_new_york = new DateTimeImmutable( $event_start->format( 'Y-m-d H:i:s' ), new DateTimeZone( 'America/New_York' ) );
		$resynced    = [];
		add_action(
			'tec_tickets_ticket_dates_updated',
			static function ( int $id, int $start ) use ( &$resynced ): void {
				$resynced[ $id ] = $start;
			},
			10,
			2
		);

		$this->send_classic_event_save( $event_id, $in_new_york );

		$this->assertSame( [ $ticket_id => $in_new_york->modify( '-2 weeks' )->getTimestamp() ], $resynced );
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
	public function should_move_the_resolved_dates_when_the_event_is_moved_through_its_repository(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );

		tribe_events()
			->where( 'id', $event_id )
			->set( 'start_date', $moved->format( 'Y-m-d H:i:s' ) )
			->set( 'end_date', $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ) )
			->save();
		// What The Events Calendar runs on shutdown to save the occurrences of the events changed outside an editor.
		tribe( Updates_Controller::class )->commit_updates();

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * @test
	 */
	public function should_move_the_resolved_dates_when_the_event_start_meta_is_updated_directly(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_ruled_ticket( $event_id );
		$moved       = $event_start->modify( '+3 days' );

		// The Events Calendar builds the occurrences from the UTC dates, so a third party moving an event writes both.
		update_post_meta( $event_id, '_EventStartDate', $moved->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $event_id, '_EventEndDate', $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $event_id, '_EventStartDateUTC', $moved->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $event_id, '_EventEndDateUTC', $moved->modify( '+3 hours' )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
		tribe( Updates_Controller::class )->commit_updates();

		$this->assert_resolved_from( $moved, $ticket_id );
	}

	/**
	 * Rescheduling cancels and fires sales actions other plugins listen to, such as the waitlist and Square syncs.
	 *
	 * @test
	 */
	public function should_not_reschedule_the_tickets_when_an_event_update_changes_no_date(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$this->create_ruled_ticket( $event_id );
		$moved = $event_start->modify( '+3 days' );
		$this->send_classic_event_save( $event_id, $moved );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$resynced = [];
		add_action(
			'tec_tickets_ticket_dates_updated',
			static function ( int $id ) use ( &$resynced ): void {
				$resynced[] = $id;
			}
		);

		$this->send_classic_event_save( $event_id, $moved );
		$request = new WP_REST_Request( 'PUT', "/wp/v2/tribe_events/{$event_id}" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'title' => 'Renamed event' ] ) );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		tribe_events()->where( 'id', $event_id )->set( 'title', 'Renamed again' )->save();
		tribe( Updates_Controller::class )->commit_updates();

		$this->assertSame( [], $resynced );
	}

	/**
	 * A rule counted from the event start resolves to the same dates when only the event end moves, but The Events
	 * Calendar still saves the occurrences.
	 *
	 * @test
	 */
	public function should_not_reschedule_the_tickets_when_an_event_move_leaves_their_dates_as_they_are(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$this->create_ruled_ticket( $event_id );
		$this->send_classic_event_save( $event_id, $event_start );
		$occurrences_saved = did_action( 'tec_events_custom_tables_v1_after_save_occurrences' );
		$resynced          = [];
		add_action(
			'tec_tickets_ticket_dates_updated',
			static function ( int $id ) use ( &$resynced ): void {
				$resynced[] = $id;
			}
		);

		$this->send_classic_event_save( $event_id, $event_start, '+5 hours' );

		$this->assertGreaterThan( $occurrences_saved, did_action( 'tec_events_custom_tables_v1_after_save_occurrences' ) );
		$this->assertSame( [], $resynced );
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
				'start' => $this->relative( 2, WEEK_IN_SECONDS ),
				'end'   => [ 'mode' => 'specific' ],
			]
		);
		tribe( Ticket_Actions::class )->sync_ticket_dates_actions( $ticket_id );
		// Two overlapping saves can leave a second pending action on each hook.
		foreach ( [ Ticket_Actions::TICKET_START_SALES_HOOK, Ticket_Actions::TICKET_END_SALES_HOOK ] as $hook ) {
			as_schedule_single_action( $specific_end->getTimestamp() - HOUR_IN_SECONDS, $hook, [ $ticket_id ], Ticket_Actions::AS_TICKET_ACTIONS_GROUP );
			$this->assertCount( 2, $this->get_scheduled_timestamps( $hook, $ticket_id ) );
		}
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
	public function should_move_the_sale_price_dates_of_a_ticket_with_only_a_sale_price_rule(): void {
		$event_start = $this->get_future_event_start();
		$event_id    = $this->create_event( $event_start->format( 'Y-m-d H:i:s' ) );
		$ticket_id   = $this->create_sale_price_ruled_ticket( $event_id, 14, 7 );
		$moved       = $event_start->modify( '+3 days' );
		$resynced    = [];
		add_action(
			'tec_tickets_ticket_dates_updated',
			static function ( int $id ) use ( &$resynced ): void {
				$resynced[] = $id;
			}
		);

		$this->send_classic_event_save( $event_id, $moved );

		$this->assertSame(
			[ $moved->modify( '-14 days' )->format( 'Y-m-d' ), $moved->modify( '-7 days' )->format( 'Y-m-d' ) ],
			[ get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ), get_post_meta( $ticket_id, Ticket::$sale_price_end_date_key, true ) ]
		);
		// The sales actions announce the sales window only, and the ticket has no rule for it.
		$this->assertSame( [], $resynced );
	}

	/**
	 * @test
	 */
	public function should_not_leave_a_cached_on_sale_behind_when_a_programmatic_move_ends_the_sale(): void {
		$today     = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
		$event_id  = $this->create_event( $today->modify( '+10 days' )->format( 'Y-m-d 19:00:00' ) );
		// From 10 days before the event, today, to 2 days before it.
		$ticket_id = $this->create_sale_price_ruled_ticket( $event_id, 10, 2 );
		$this->assertTrue( tribe( Module::class )->get_ticket( $event_id, $ticket_id )->on_sale );
		$moved = new DateTimeImmutable( $today->modify( '+17 days' )->format( 'Y-m-d 19:00:00' ), new DateTimeZone( 'UTC' ) );

		// Only the ticket's own meta writes clear its cache here: nothing saves the ticket.
		tribe_events()
			->where( 'id', $event_id )
			->set( 'start_date', $moved->format( 'Y-m-d H:i:s' ) )
			->set( 'end_date', $moved->modify( '+3 hours' )->format( 'Y-m-d H:i:s' ) )
			->save();
		tribe( Updates_Controller::class )->commit_updates();

		$this->assertSame( $moved->modify( '-10 days' )->format( 'Y-m-d' ), get_post_meta( $ticket_id, Ticket::$sale_price_start_date_key, true ) );
		$this->assertFalse( tribe( Module::class )->get_ticket( $event_id, $ticket_id )->on_sale );
	}

	/**
	 * Creates a Tickets Commerce ticket priced 20 with a sale price of 10, and stores a sale price rule on it.
	 *
	 * @param int $event_id   The event post ID.
	 * @param int $start_days How many days before the event start the sale price starts.
	 * @param int $end_days   How many days before the event start the sale price ends.
	 *
	 * @return int The ticket post ID.
	 */
	private function create_sale_price_ruled_ticket( int $event_id, int $start_days, int $end_days ): int {
		$ticket_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'ticket_add_sale_price' => 'on',
				'ticket_sale_price'     => 10,
			]
		);
		$rule      = Rule::from_array(
			[
				'start' => [ 'mode' => Rule::MODE_RELATIVE, 'value' => $start_days, 'unit' => DAY_IN_SECONDS ],
				'end'   => [ 'mode' => Rule::MODE_RELATIVE, 'value' => $end_days, 'unit' => DAY_IN_SECONDS ],
			],
			Window_Kind::sale_price()
		);
		tribe( Rule_Store::class )->save_rule( $ticket_id, $rule );
		tribe( Ticket_Dates::class )->write( $ticket_id, $event_id, $rule );

		return $ticket_id;
	}

	/**
	 * Saves the event from the classic editor, moving it to a new start and timezone.
	 *
	 * @param int               $event_id The event post ID.
	 * @param DateTimeImmutable $start    The new event start, in the new event timezone.
	 * @param string            $length   How long the event lasts, as a `modify()` string.
	 *
	 * @return void
	 */
	private function send_classic_event_save( int $event_id, DateTimeImmutable $start, string $length = '+3 hours' ): void {
		$end = $start->modify( $length );
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
		// The Events Calendar saves the occurrences of an event saved in the classic editor before redirecting.
		apply_filters( 'redirect_post_location', get_edit_post_link( $event_id, 'url' ), $event_id );

		$_POST = [];
	}
}
