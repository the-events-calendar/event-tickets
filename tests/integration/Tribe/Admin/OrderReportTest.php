<?php

namespace Tribe\Admin;

use Closure;
use Generator;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Reports\Orders as Order_Report;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;
use TEC\Tickets\Commerce\Admin_Tables\Orders as Orders_Table;

/**
 * Class OrderReportTest tests the order report.
 *
 * @package Tribe\Admin
 */
class OrderReportTest extends WPTestCase {

	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;

	public function tc_order_report_data_provider(): Generator {
		yield 'event with no orders' => [
			function (): array {
				$event_id  = tribe_events()->set_args(
					[
						'title'      => 'Event with no orders',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );

				return [ $event_id, [ $event_id, $ticket_id ] ];
			},
		];

		yield 'event with one order' => [
			function (): array {
				$event_id  = tribe_events()->set_args(
					[
						'title'      => 'Event with one order',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order     = $this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );

				return [ $event_id, [ $event_id, $ticket_id, $order->ID ] ];
			},
		];

		yield 'event with 1 pending and 1 completed order' => [
			function (): array {
				$event_id  = tribe_events()->set_args(
					[
						'title'      => 'Event with 1 pending and 1 completed order',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				$order_a = $this->create_order( [ $ticket_id => 2 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order(
					[ $ticket_id => 3 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);

				// Manually set the `post_date` of each order in sequence to ensure the order is consistent in the snapshot.
				global $wpdb;
				foreach (
					[
						$order_a->ID => '2022-01-01 00:00:00',
						$order_b->ID => '2022-01-02 00:00:00',
					] as $order_id => $post_date
				) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts} SET post_date = %s WHERE ID = %d",
							$post_date,
							$order_id
						)
					);
				}

				return [ $event_id, [ $event_id, $ticket_id, $order_a->ID, $order_b->ID ] ];
			},
		];

		yield 'event with multiple tickets and orders' => [
			function (): array {
				$event_id    = tribe_events()->set_args(
					[
						'title'      => 'Event multiple tickets and orders',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				$ticket_id_a = $this->create_tc_ticket( $event_id, 10 );
				$ticket_id_b = $this->create_tc_ticket( $event_id, 20.50 );

				// Force ticket sorting order for display.
				wp_update_post(
					[
						'ID'         => $ticket_id_a,
						'menu_order' => 0,
					]
				);
				wp_update_post(
					[
						'ID'         => $ticket_id_b,
						'menu_order' => 1,
					]
				);

				$order_a = $this->create_order( [ $ticket_id_a => 2 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order(
					[ $ticket_id_a => 3 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);
				$order_c = $this->create_order( [ $ticket_id_b => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_d = $this->create_order(
					[ $ticket_id_b => 4 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);

				// Manually set the `post_date` of each order in sequence to ensure the order is consistent in the snapshot.
				global $wpdb;
				foreach (
					[
						$order_a->ID => '2022-01-01 00:00:00',
						$order_b->ID => '2022-01-02 00:00:00',
						$order_c->ID => '2022-01-03 00:00:00',
						$order_d->ID => '2022-01-04 00:00:00',
					] as $order_id => $post_date
				) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts} SET post_date = %s WHERE ID = %d",
							$post_date,
							$order_id
						)
					);
				}

				return [ $event_id, [ $event_id, $ticket_id_a, $ticket_id_b, $order_a->ID, $order_b->ID, $order_c->ID, $order_d->ID ] ];
			},
		];

		yield 'event with multiple tickets in same order' => [
			function (): array {
				$event_id    = tribe_events()->set_args(
					[
						'title'      => 'Event with multiple tickets in same order',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				$ticket_id_a = $this->create_tc_ticket( $event_id, 10 );
				$ticket_id_b = $this->create_tc_ticket( $event_id, 20.50 );
				$ticket_id_c = $this->create_tc_ticket(
					$event_id,
					0,
					[
						'tribe-ticket' => [
							'mode'     => Global_Stock::OWN_STOCK_MODE,
							'capacity' => -1,
						],
					]
				);

				// Force ticket sorting order for display.
				wp_update_post(
					[
						'ID'         => $ticket_id_a,
						'menu_order' => 0,
					]
				);
				wp_update_post(
					[
						'ID'         => $ticket_id_b,
						'menu_order' => 1,
					]
				);
				wp_update_post(
					[
						'ID'         => $ticket_id_c,
						'menu_order' => 2,
					]
				);

				$order_a = $this->create_order( [ $ticket_id_a => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order(
					[ $ticket_id_a => 1 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);
				$order_c = $this->create_order( [ $ticket_id_b => 1 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_d = $this->create_order(
					[ $ticket_id_b => 1 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);
				$order_e = $this->create_order(
					[
						$ticket_id_a => 1,
						$ticket_id_b => 1,
					],
					[ 'purchaser_email' => 'purchaser@test.com' ]
				);

				// Manually set the `post_date` of each order in sequence to ensure the order is consistent in the snapshot.
				global $wpdb;
				foreach (
					[
						$order_a->ID => '2022-01-01 00:00:00',
						$order_b->ID => '2022-01-02 00:00:00',
						$order_c->ID => '2022-01-03 00:00:00',
						$order_d->ID => '2022-01-04 00:00:00',
						$order_e->ID => '2022-01-05 00:00:00',
					] as $order_id => $post_date
				) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts} SET post_date = %s WHERE ID = %d",
							$post_date,
							$order_id
						)
					);
				}

				return [ $event_id, [ $event_id, $ticket_id_a, $ticket_id_b, $order_a->ID, $order_b->ID, $order_c->ID, $order_d->ID, $order_e->ID ] ];
			},
		];

		yield 'event with sale price enabled tickets and orders' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with sale price enabled tickets and orders',
						'status'     => 'publish',
						'start_date' => '2023-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id_a = $this->create_tc_ticket(
					$event_id,
					20,
					[
						'ticket_add_sale_price'  => 'on',
						'ticket_sale_price'      => 10,
						'ticket_sale_start_date' => '2010-03-01',
						'ticket_sale_end_date'   => '2040-03-01',
					]
				);

				$ticket_id_b = $this->create_tc_ticket( $event_id, 30 );

				$order_a = $this->create_order( [ $ticket_id_a => 3 ], [ 'purchaser_email' => 'purchaser@test.com' ] );
				$order_b = $this->create_order(
					[ $ticket_id_a => 2 ],
					[
						'purchaser_email' => 'purchaser@test.com',
						'order_status'    => Pending::SLUG,
					]
				);
				$order_c = $this->create_order(
					[
						$ticket_id_a => 2,
						$ticket_id_b => 3,
					],
					[ 'purchaser_email' => 'purchaser@test.com' ]
				);

				// Manually set the `post_date` of each order in sequence to ensure the order is consistent in the snapshot.
				global $wpdb;
				foreach (
					[
						$order_a->ID => '2022-01-01 00:00:00',
						$order_b->ID => '2022-01-02 00:00:00',
						$order_c->ID => '2022-01-03 00:00:00',
					] as $order_id => $post_date
				) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts} SET post_date = %s WHERE ID = %d",
							$post_date,
							$order_id
						)
					);
				}

				return [
					$event_id,
					[
						$event_id,
						$ticket_id_a,
						$ticket_id_b,
						$order_a->ID,
						$order_b->ID,
						$order_c->ID,
					],
				];
			},
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
		$this->set_fn_return( 'wp_create_nonce', '0987654321' );

		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';
		$_GET['orderby']  = 'ID';
		$_GET['order']    = 'desc';

		// Clear cache to make sure proper orders appear.
		wp_cache_flush();

		$order_report = tribe( Order_Report::class );
		$order_report->attendees_page_screen_setup();
		$gateway_order_ids = array_map(
			fn( int $order_id ): string => get_post_meta( $order_id, Order::$gateway_order_id_meta_key, true ),
			array_filter(
				$post_ids,
				fn( int $post_id ): bool => get_post_type( $post_id ) === Order::POSTTYPE
			)
		);

		ob_start();
		$order_report->render_page();
		$html = ob_get_clean();

		// Replace the post IDs with placeholders to avoid snapshot mismatches.
		$html = str_replace( $post_ids, '{{ID}}', $html );

		/**
		 * Stabilize order dates column.
		 * @see Orders_Table::column_date()
		 */
		$order_date = esc_html( \Tribe__Date_Utils::reformat( current_time( 'mysql' ), \Tribe__Date_Utils::DATEONLYFORMAT ) );

		// Replace the order date with a placeholder.
		$html = str_replace( $order_date, '{{order_date}}', $html );

		// Replace the order gateway ID, a random hash, with a placeholder.
		$html = str_replace( $gateway_order_ids, '{{gateway_order_id}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
