<?php

namespace Tribe\Tickets;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Reports\Orders as Order_Report;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Admin_Tables\Orders as Orders_Table;

class OrderReportTest extends WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use Series_Pass_Factory;

	public function placehold_post_ids( string $snapshot, array $ids ): string {
		return str_replace(
			$ids,
			array_fill( 0, count( $ids ), '{{ID}}' ),
			$snapshot
		);
	}

	public function tc_order_report_data_provider(): Generator {
		yield 'event with no orders' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			}
		];

		yield 'event with one order' => [
			function (): array {
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Event with no attendees',
					'status'     => 'publish',
					'start_date' => '2020-01-01 00:00:00',
					'duration'   => 2 * HOUR_IN_SECONDS,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order     = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $event_id, $ticket_id, $order->ID ] ];
			}
		];

		yield 'event in a series with no orders' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test series',
				] );
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $series_id, $event_id, $ticket_id ] ];
			}
		];

		yield 'event in a series with one order' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test series',
				] );
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order     = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $series_id, $event_id, $ticket_id, $order->ID ] ];
			}
		];

		yield 'recurring event with no order' => [
			function (): array {
				$event_id   = tribe_events()->set_args( [
					'title'      => 'Test event',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2',
				] )->create()->ID;

				$ticket_id = $this->create_tc_ticket( $event_id );
				$series_id = tec_series()->where( 'event_post_id', $event_id )->first_id();

				return [ $event_id, [ $event_id, $ticket_id, $series_id ] ];
			}
		];

		yield 'recurring event with one order' => [
			function (): array {
				$event_id   = tribe_events()->set_args( [
					'title'      => 'Test recurring event with one order',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'recurrence' => 'RRULE:FREQ=DAILY;COUNT=2',
				] )->create()->ID;

				$ticket_id = $this->create_tc_ticket( $event_id );
				$series_id = tec_series()->where( 'event_post_id', $event_id )->first_id();
				$order     = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $event_id, $ticket_id, $series_id, $order->ID ] ];
			}
		];

		yield 'event with a series pass and single ticket orders' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test event with a series pass and single ticket orders',
				] );
				$event_id  = tribe_events()->set_args( [
					'title'      => 'Test event with a series pass and single ticket orders',
					'status'     => 'publish',
					'start_date' => '2021-01-01 10:00:00',
					'end_date'   => '2021-01-01 12:00:00',
					'series'     => $series_id,
				] )->create()->ID;

				$ticket_id      = $this->create_tc_ticket( $event_id, 10 );
				$series_pass_id = $this->create_tc_series_pass( $series_id, 55 )->ID;

				$order_a = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order( [ $series_pass_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $series_id, $event_id, $ticket_id, $order_a->ID, $order_b->ID ] ];
			}
		];

		yield 'order report page for a series with multiple pass and orders' => [
			function (): array {
				$series_id = static::factory()->post->create( [
					'post_type'  => Series_Post_Type::POSTTYPE,
					'post_title' => 'Test Series order report page for a series with multiple pass and orders',
				] );

				$series_pass_id_a = $this->create_tc_series_pass( $series_id, 10 )->ID;
				$series_pass_id_b = $this->create_tc_series_pass( $series_id, 20 )->ID;

				// Force ticket sorting order for display.
				wp_update_post( [ 'ID' => $series_pass_id_a, 'menu_order' => 0 ] );
				wp_update_post( [ 'ID' => $series_pass_id_b, 'menu_order' => 1 ] );

				$order_a = $this->create_order( [ $series_pass_id_a => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order( [ $series_pass_id_b => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $series_id, [ $series_id, $series_pass_id_a, $order_a->ID, $order_b->ID ] ];
			}
		];
	}

	/**
	 * @dataProvider tc_order_report_data_provider
	 */
	public function test_tc_order_report_display( Closure $fixture ) {
		// The global hook suffix is used to set the table static cache, randomize it to avoid collisions with other tests.
		$GLOBALS['hook_suffix'] = uniqid( 'tec-tc-reports-orders', true );
		// Ensure we're using a user that can manage posts.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		[ $post_id, $post_ids ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';

		$order_report = tribe( Order_Report::class );
		$order_report->attendees_page_screen_setup();

		ob_start();
		$order_report->render_page();
		$html = ob_get_clean();

		$html = $this->placehold_post_ids( $html, $post_ids );

		/**
		 * Stabilize order dates column.
		 * @see Orders_Table::column_date()
		 */
		$order_date = esc_html( \Tribe__Date_Utils::reformat( current_time( 'mysql' ), \Tribe__Date_Utils::DATEONLYFORMAT ) );

		$html = str_replace( $order_date, '{{order_date}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}