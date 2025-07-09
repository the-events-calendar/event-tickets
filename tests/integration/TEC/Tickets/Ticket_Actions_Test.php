<?php

namespace TEC\Tickets;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Global_Stock as Global_Stock;
use ActionScheduler_Store;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Generator;
use Closure;

class Ticket_Actions_Test extends Controller_Test_Case {
	use With_Clock_Mock;
	use RSVP_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	protected $controller_class = Ticket_Actions::class;

	protected static $back_up_actions = [];
	protected static array $back_up_counts = [];

	/**
	 * @before
	 */
	public function take_backup() {
		global $wp_actions;

		self::$back_up_actions = $wp_actions;
		$wp_actions = [];

		self::$back_up_counts['start'] = count( $this->query_action_scheduler_actions_count() );
		self::$back_up_counts['end'] = count( $this->query_action_scheduler_actions_count( null, false ) );
	}

	/**
	 * @after
	 */
	public function restore_backup() {
		global $wp_actions;

		$wp_actions = self::$back_up_actions;
	}

	protected static function set_up_data(): array {
		$capacity_of_all = 5; // Capacity 5 so its easy to create attendees to get them out of stock.

		$pre_sale_overrides = [
			'ticket_start_date' => '2050-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2050-03-01',
			'ticket_end_time'   => '20:00:00',
			'tribe-ticket'            => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => $capacity_of_all,
			],
		];

		$after_sales_overrides = [
			'ticket_start_date' => '2020-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2020-03-01',
			'ticket_end_time'   => '20:00:00',
			'tribe-ticket'            => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => $capacity_of_all,
			],
		];

		$on_sale_overrides = [
			'ticket_start_date' => '2020-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2050-03-01',
			'ticket_end_time'   => '20:00:00',
			'tribe-ticket'            => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => $capacity_of_all,
			],
		];

		$rsvp_overrides_pre_sale = [
			'meta_input' => [
				'_ticket_start_date' => '2050-01-01 08:00:00',
				'_ticket_end_date'   => '2050-03-01 20:00:00',
				'_capacity'   => $capacity_of_all,
			]
		];

		$rsvp_overrides_after_sale = [
			'meta_input' => [
				'_ticket_start_date' => '2020-01-01 08:00:00',
				'_ticket_end_date'   => '2020-03-01 20:00:00',
				'_capacity'          => $capacity_of_all,
			]
		];

		$rsvp_overrides_on_sale = [
			'meta_input' => [
				'_ticket_start_date' => '2020-01-01 08:00:00',
				'_ticket_end_date'   => '2050-03-01 20:00:00',
				'_capacity'          => $capacity_of_all,
			]
		];

		return [
			'tickets' => [
				'pre_sale'   => $pre_sale_overrides,
				'after_sale' => $after_sales_overrides,
				'on_sale' 	 => $on_sale_overrides,
			],
			'rsvp'    => [
				'pre_sale'   => $rsvp_overrides_pre_sale,
				'after_sale' => $rsvp_overrides_after_sale,
				'on_sale' 	 => $rsvp_overrides_on_sale,
			],
		];
	}

	public function provide_one_ticket_and_one_rsvp(): Generator {
		yield 'ticket' => [
			function (): array {
				$post_id = $this->factory()->post->create();

				$data = self::set_up_data();

				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_dates_updated' ) );
				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_changed' ) );
				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_added' ) );

				$ticket_id = $this->create_tc_ticket( $post_id, 10, $data['tickets']['pre_sale'] );

				return [ $post_id, $ticket_id, fn( string $offset ) => $this->update_ticket( $ticket_id, $data['tickets'][ $offset ] ) ];
			}
		];
		yield 'rsvp' => [
			function (): array {
				$post_id = $this->factory()->post->create();

				$data = self::set_up_data();

				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_dates_updated' ) );
				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_changed' ) );
				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_added' ) );

				$rsvp_id = $this->create_rsvp_ticket( $post_id, $data['rsvp']['pre_sale'] );

				do_action( 'tec_shutdown' );

				return [
					$post_id,
					$rsvp_id,
					function( string $offset ) use ( $rsvp_id, $data ) {
						$result = $this->update_rsvp_ticket( $rsvp_id, $data['rsvp'][ $offset ] );
						do_action( 'tec_shutdown' );
						return $result;
			 		},
				];
			}
		];
	}

	/**
	 * @test
	 * @dataProvider provide_one_ticket_and_one_rsvp
	 */
	public function it_should_schedule_ticket_date_and_stock_actions( Closure $fixture ): void {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:00:00' ) );
		$this->make_controller()->register();

		$store = [];
		add_action( 'tec_tickets_ticket_stock_changed', function ( $ticket_id, $new_stock, $old_stock ) use ( &$store ) {
			$store = compact( 'ticket_id', 'new_stock', 'old_stock' );
		}, 10, 3 );
		add_action( 'tec_tickets_ticket_stock_added', function ( $ticket_id, $new_stock ) use ( &$store ) {
			$store = compact( 'ticket_id', 'new_stock' );
		}, 10, 2 );

		[ $post_id, $ticket_id, $updater ] = $fixture();

		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_dates_updated' ) );
		$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_changed' ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_stock_added' ) );

		$this->assertEquals( $store['ticket_id'], $ticket_id );
		$this->assertEquals( $store['new_stock'], 5 );
		$this->assertTrue( ! isset( $store['old_stock'] ) );

		$this->assertCount(
			1,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ]
			)
		);

		$this->assertCount(
			1,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ],
				false
			)
		);

		$this->assertEquals( 0, did_action( $this->controller_class::TICKET_START_SALES_HOOK ) );

		$updater( 'after_sale' );
		// The stock is now 5 after creation. we need to actually change this to trigger the action.
		update_post_meta( $ticket_id, '_stock', 6 );

		$this->assertEquals( 2, did_action( 'tec_tickets_ticket_dates_updated' ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_stock_changed' ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_stock_added' ) );

		$this->assertEquals( $store['ticket_id'], $ticket_id );
		$this->assertEquals( $store['new_stock'], 6 );
		$this->assertEquals( $store['old_stock'], 5 );

		$this->assertCount(
			0,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ]
			)
		);

		$this->assertCount(
			0,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ],
				false
			)
		);

		$this->assertEquals( 0, did_action( $this->controller_class::TICKET_START_SALES_HOOK ) );

		// Now we update back to 5 the capacity to trigger the action.
		// the capacity is part of the $data array.
		$updater( 'on_sale' );

		// The sale start date is in the past, so the action should be fired immediately.
		$this->assertEquals( 1, did_action( $this->controller_class::TICKET_START_SALES_HOOK ) );

		$this->assertEquals( 3, did_action( 'tec_tickets_ticket_dates_updated' ) );
		$this->assertEquals( 2, did_action( 'tec_tickets_ticket_stock_changed' ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_stock_added' ) );

		$this->assertEquals( $store['ticket_id'], $ticket_id );
		$this->assertEquals( $store['new_stock'], 5 );
		$this->assertEquals( $store['old_stock'], 6 );

		$this->assertCount(
			0,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ]
			)
		);

		$this->assertCount(
			1,
			$this->query_action_scheduler_actions_count(
				[ $ticket_id ],
				false
			)
		);
	}

	/**
	 * @test
	 * @dataProvider provide_one_ticket_and_one_rsvp
	 */
	public function it_should_fire_actions_and_reschedule_accordingly( Closure $fixture ): void {
		$this->freeze_time( Dates::immutable( '2050-01-01 07:00:00' ) );

		$this->make_controller()->register();

		[ $post_id, $ticket_id, $updater ] = $fixture();

		$listeners = [
			'ticket_id' => null,
			'its_happening' => null,
			'timestamp' => null,
			'event' => null,
		];

		add_action( 'tec_tickets_ticket_start_date_trigger', function ( $ticket_id, $its_happening, $timestamp, $event ) use ( &$listeners ) {
			$listeners['ticket_id'] = $ticket_id;
			$listeners['its_happening'] = $its_happening;
			$listeners['timestamp'] = $timestamp;
			$listeners['event'] = $event;
		}, 10, 4 );

		$this->assertEquals( 0, did_action( 'tec_tickets_ticket_start_date_trigger' ) );

		$start_actions = $this->query_action_scheduler_actions_count( [ $ticket_id ] );

		$this->assertCount( 1, $start_actions );

		$start_action = array_shift( $start_actions );

		$action_schedule = $start_action->get_schedule();

		// First one is half an hour ago before its go live timestamp.
		$this->assertEquals( '2050-01-01 07:30:00', $action_schedule->get_date()->format( 'Y-m-d H:i:s' ) );

		// Freeze time when we fire the first action.
		$this->freeze_time( Dates::immutable( '2050-01-01 07:30:00' ) );

		do_action( $this->controller_class::TICKET_START_SALES_HOOK, $ticket_id );

		$this->assertEquals( 1, did_action( 'tec_tickets_ticket_start_date_trigger' ) );
		$this->assertEquals( $ticket_id, $listeners['ticket_id'] );
		$this->assertFalse( $listeners['its_happening'] );
		$this->assertEquals( strtotime( '2050-01-01 08:00:00' ), $listeners['timestamp'] );
		$this->assertEquals( $post_id, $listeners['event']->ID );

		$start_actions = $this->query_action_scheduler_actions_count( [ $ticket_id ] );

		$this->assertCount( 1, $start_actions );

		$start_action = array_shift( $start_actions );

		$action_schedule = $start_action->get_schedule();

		// First one is half an hour ago before its go live timestamp.
		$this->assertEquals( '2050-01-01 07:40:00', $action_schedule->get_date()->format( 'Y-m-d H:i:s' ) );

		// Freeze time when we fire the second action.
		$this->freeze_time( Dates::immutable( '2050-01-01 07:40:00' ) );

		do_action( $this->controller_class::TICKET_START_SALES_HOOK, $ticket_id );

		$this->assertEquals( 2, did_action( 'tec_tickets_ticket_start_date_trigger' ) );
		$this->assertEquals( $ticket_id, $listeners['ticket_id'] );
		$this->assertFalse( $listeners['its_happening'] );
		$this->assertEquals( strtotime( '2050-01-01 08:00:00' ), $listeners['timestamp'] );
		$this->assertEquals( $post_id, $listeners['event']->ID );

		$start_actions = $this->query_action_scheduler_actions_count( [ $ticket_id ] );

		$this->assertCount( 1, $start_actions );

		$start_action = array_shift( $start_actions );

		$action_schedule = $start_action->get_schedule();

		// First one is half an hour ago before its go live timestamp.
		$this->assertEquals( '2050-01-01 07:50:00', $action_schedule->get_date()->format( 'Y-m-d H:i:s' ) );

		// Freeze time when we fire the third action.
		$this->freeze_time( Dates::immutable( '2050-01-01 07:50:00' ) );

		do_action( $this->controller_class::TICKET_START_SALES_HOOK, $ticket_id );

		$this->assertEquals( 3, did_action( 'tec_tickets_ticket_start_date_trigger' ) );
		$this->assertEquals( $ticket_id, $listeners['ticket_id'] );
		$this->assertFalse( $listeners['its_happening'] );
		$this->assertEquals( strtotime( '2050-01-01 08:00:00' ), $listeners['timestamp'] );
		$this->assertEquals( $post_id, $listeners['event']->ID );

		$start_actions = $this->query_action_scheduler_actions_count( [ $ticket_id ] );

		$this->assertCount( 1, $start_actions );

		$start_action = array_shift( $start_actions );

		$action_schedule = $start_action->get_schedule();

		// First one is half an hour ago before its go live timestamp.
		$this->assertEquals( '2050-01-01 08:00:00', $action_schedule->get_date()->format( 'Y-m-d H:i:s' ) );

		// Freeze time when we fire the fourth and final action.
		$this->freeze_time( Dates::immutable( '2050-01-01 08:00:00' ) );

		do_action( $this->controller_class::TICKET_START_SALES_HOOK, $ticket_id );

		$this->assertEquals( 4, did_action( 'tec_tickets_ticket_start_date_trigger' ) );
		$this->assertEquals( $ticket_id, $listeners['ticket_id'] );
		$this->assertTrue( $listeners['its_happening'] );
		$this->assertEquals( strtotime( '2050-01-01 08:00:00' ), $listeners['timestamp'] );
		$this->assertEquals( $post_id, $listeners['event']->ID );

		$start_actions = $this->query_action_scheduler_actions_count( [ $ticket_id ] );

		$this->assertCount( 0, $start_actions );
	}

	/**
	 * @test
	 */
	public function it_should_handle_stress() {
		$this->make_controller()->register();

		$this->assertEquals( 0, did_action( 'tec_tickets_ticket_dates_updated' ) );
		$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_changed' ) );
		$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_added' ) );

		$posts            = 10;
		$tickets_per_post = 50;

		$counter = 0;

		$store = [];

		add_action( 'tec_tickets_ticket_stock_changed', function ( $ticket_id, $new_stock, $old_stock ) use ( &$store ) {
			$store = compact( 'ticket_id', 'new_stock', 'old_stock' );
		}, 10, 3 );
		add_action( 'tec_tickets_ticket_stock_added', function ( $ticket_id, $new_stock ) use ( &$store ) {
			$store = compact( 'ticket_id', 'new_stock' );
		}, 10, 2 );

		for ( $i = 0; $i < $posts; $i++ ) {
			$post_id = $this->factory()->post->create();
			for ( $j = 0; $j < $tickets_per_post; $j++ ) {
				$counter++;
				$this->create_tc_ticket( $post_id, 10, self::set_up_data()['tickets']['pre_sale'] );
				$this->assertEquals( $counter, did_action( 'tec_tickets_ticket_dates_updated' ) );
				$this->assertEquals( 0, did_action( 'tec_tickets_ticket_stock_changed' ) );
				$this->assertEquals( $counter, did_action( 'tec_tickets_ticket_stock_added' ) );
				$this->assertEquals( $store['new_stock'], 5 );
				$this->assertTrue( ! isset( $store['old_stock'] ) );
			}
		}

		$this->assertEquals( $posts * $tickets_per_post, $counter );

		// 500 tickets all of them in the future means 1000 actions.
		$start_actions = $this->query_action_scheduler_actions_count( null, true, ( $posts * $tickets_per_post ) );
		$end_actions   = $this->query_action_scheduler_actions_count( null, false, ( $posts * $tickets_per_post ) );

		$this->assertCount( ( $posts * $tickets_per_post ), $start_actions );
		$this->assertCount( ( $posts * $tickets_per_post ), $end_actions );
		$this->assertNotSame( $start_actions, $end_actions );

		/**
		 * In a real world scenario AS would pull like 100 actions at a time and
		 * process them for a maximum of 90 seconds.
		 *
		 * Of course there are filters to change this behavior, but this is default.
		 *
		 * Lets do a time limit of 5 seconds and process all 1000 actions as a stress test.
		 *
		 * That is in total 180 times more performant of what AS can handle.
		 */

		// Assert that no action is executed yet at this point.
		$this->assertEquals( 0, did_action( $this->controller_class::TICKET_START_SALES_HOOK ) );
		$this->assertEquals( 0, did_action( $this->controller_class::TICKET_END_SALES_HOOK ) );

		$start_process_actions_time = time();
		foreach ( $start_actions as $action ) {
			$action->execute();
		}
		foreach ( $end_actions as $action ) {
			$action->execute();
		}
		$end_process_actions_time = time();

		// Assert that all the actions have actually executed.
		$this->assertEquals( ( $posts * $tickets_per_post ), did_action( $this->controller_class::TICKET_START_SALES_HOOK ) );
		$this->assertEquals( ( $posts * $tickets_per_post ), did_action( $this->controller_class::TICKET_END_SALES_HOOK ) );

		$this->assertLessThan( 5, $end_process_actions_time - $start_process_actions_time );
	}

	/**
	 * @after
	 */
	public function check_we_are_clearing_up() {
		$this->assertCount( self::$back_up_counts['start'], $this->query_action_scheduler_actions_count() );
		$this->assertCount( self::$back_up_counts['end'], $this->query_action_scheduler_actions_count( null, false ) );
	}

	protected function query_action_scheduler_actions_count( ?array $args = null, bool $start = true, $limit = 100, $offset = 0 ): array {
		$hook = $start ? $this->controller_class::TICKET_START_SALES_HOOK : $this->controller_class::TICKET_END_SALES_HOOK;

		$params = [
			'hook'     => $hook,
			'status'   => ActionScheduler_Store::STATUS_PENDING,
			'orderby'  => 'date',
			'order'    => 'ASC',
			'group'    => $this->controller_class::AS_TICKET_ACTIONS_GROUP,
			'per_page' => $limit,
			'offset'   => $offset,
		];

		if ( is_array( $args ) ) {
			$params['args'] = $args;
		}

		return as_get_scheduled_actions( $params, OBJECT );
	}
}
