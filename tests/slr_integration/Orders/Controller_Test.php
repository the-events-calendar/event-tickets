<?php

namespace TEC\Tickets\Seating\Orders;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Attendees as Attendees;
use TEC\Tickets\Seating\Meta;

class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;
	
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use With_Tickets_Commerce;
	
	private function get_attendee_data( array $attendees ): array {
		return array_reduce(
			$attendees,
			function ( array $carry, array $attendee ): array {
				foreach (
				[
					'ID',
					'ticket_id',
					'purchaser_name',
					'purchaser_email',
					'ticket_name',
					'holder_name',
					'security_code',
				] as $key
				) {
					if ( ! isset( $attendee[ $key ] ) ) {
						continue;
					}
				
					$value = $attendee[ $key ];
				
					if ( empty( $value ) ) {
						continue;
					}
				
					$carry[ esc_html( $value ) ] = strtoupper( $key );
					$carry[ $value ]             = strtoupper( $key );
				}
			
				return $carry;
			},
			[] 
		);
	}
	
	public function attendee_data_provider(): Generator {
//		yield 'single event with Single Ticket attendees' => [
//			function (): array {
//				$event_id = tribe_events()->set_args(
//					[
//						'title'      => 'Event with single attendee',
//						'status'     => 'publish',
//						'start_date' => '2020-01-01 00:00:00',
//						'duration'   => 2 * HOUR_IN_SECONDS,
//					]
//				)->create()->ID;
//
//				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
//				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
//
//				$ticket_id = $this->create_tc_ticket( $event_id );
//				$this->create_order( [ $ticket_id => 3 ] );
//
//				return [ $event_id, [ $event_id, $ticket_id ] ];
//			},
//		];
		
		yield 'single event with Single seated ticket attendee' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single seated attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				
				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
				
				$ticket_id = $this->create_tc_ticket( $event_id );
				
				$cart = new Cart();

				$tribe_tickets_ar_data = json_encode(
					[
						'tribe_tickets_tickets' => [
							[
								'ticket_id'   => $ticket_id,
								'quantity'    => 2,
								'optout'      => '1',
								'seat_labels' => [ 'B-3', 'B-4' ],
							],
						],
						'tribe_tickets_meta'    => [],
						'tribe_tickets_post_id' => $event_id,
					]
				);

				$data = [
					'provider'                       => 'TEC\\Tickets\\Commerce\\Module',
					'attendee'                       => [
						'optout' => 1,
					],
					'tickets_tickets_ar'             => 1,
					'tribe_tickets_saving_attendees' => 1,
					'tribe_tickets_ar_data'          => $tribe_tickets_ar_data,
					'_wpnonce'                       => '1234567890',
				];

				// Merge new data with existing $_POST data
				$_POST = array_merge( $_POST, $data );
				
				$cart->parse_request();
				// create POST data from the following comment
				
				$purchaser = [
					'purchaser_user_id'    => 0,
					'purchaser_full_name'  => 'Test Purchaser',
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser',
					'purchaser_email'      => 'test-' . uniqid() . '@test.com',
				];
				
				$order_status = Completed::SLUG;
				$orders       = tribe( Order::class );
				$order        = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );
				
				$orders->modify_status( $order->ID, Pending::SLUG );
				$orders->modify_status( $order->ID, $order_status );
				
				clean_post_cache( $order->ID );
				
				$cart->clear_cart();
				
				return [ $event_id, [ $event_id, $ticket_id ] ];
			},
		];
	}
	
	/**
	 * Test the attendee list seat column.
	 *
	 *
	 *
	 * @return void
	 */
	public function test_attendee_list_seat_column(): void {
		$this->make_controller()->register();
		
		$cart = new Cart();
		$this->set_class_fn_return( Cart::class, 'get_mode', 'test' );
		$this->set_class_property( $cart, 'available_modes', [ 'redirect', 'test' ] );
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		
		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
		
		$ticket_id = $this->create_tc_ticket( $event_id );
		
		$tribe_tickets_ar_data = json_encode(
			[
				'tribe_tickets_tickets' => [
					[
						'ticket_id'   => $ticket_id,
						'quantity'    => 2,
						'optout'      => '1',
						'seat_labels' => [ 'B-3', 'B-4' ],
					],
				],
				'tribe_tickets_meta'    => [],
				'tribe_tickets_post_id' => $event_id,
			]
		);
		
		$data = [
			'provider'                       => 'TEC\\Tickets\\Commerce\\Module',
			'attendee'                       => [
				'optout' => 1,
			],
			'tickets_tickets_ar'             => 1,
			'tribe_tickets_saving_attendees' => 1,
			'tribe_tickets_ar_data'          => $tribe_tickets_ar_data,
			'_wpnonce'                       => '1234567890',
			'tec-tc-cart'                    => 'test',
		];
		
		// Merge new data with existing $_POST data
		$_POST = array_merge( $_POST, $data );
		
//		$cart = new Cart();
		$cart->parse_request();
		// create POST data from the following comment
		
		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-' . uniqid() . '@test.com',
		];
		
		$order_status = Completed::SLUG;
		$orders       = tribe( Order::class );
		$order        = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );
		
		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, $order_status );
		
		clean_post_cache( $order->ID );
		
		$cart->clear_cart();
		
		$post_id = $event_id;
		$post_ids = [ $ticket_id ];
		
		// The global hook suffix is used to set the table static cache, randomize it to avoid collisions with other tests.
		$GLOBALS['hook_suffix'] = uniqid( 'tribe_events_page_tickets-attendees', true );
		// Ensure we're using a user that can check-in Attendees and manage the posts.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Filter the insertion of the Attendees post to import an order by `post_title` and stabilize the snapshot.
		add_filter(
			'wp_insert_post_data',
			function ( $data ) {
				static $k = 1;
				if ( str_ends_with( $data['post_type'], '_attendee' ) || str_ends_with( $data['post_type'], '_attendees' ) ) {
					$data['post_title'] = 'Test Attendee ' . str_pad( $k++, 3, '0', STR_PAD_LEFT );
				}
			
				return $data;
			},
			PHP_INT_MAX 
		);
//		[ $post_id, $post_ids ] = $fixture();
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		
		$_GET['event_id'] = $post_id;
		$_GET['search']   = '';
		
		tribe_cache()->reset();
		ob_start();
		/*
		Columns headers are cached in the `get_column_headers` function
		by screen id. To avoid the cache, we need to set the screen id
		to something different from the default one.
		*/
		$attendees = tribe( Attendees::class );
		$attendees->screen_setup();
		$attendees->render();
		$html = ob_get_clean();
		
		// Stabilize snapshots.
		$attendee_data = $this->get_attendee_data( $attendees->attendees_table->items );
		$replace       = array_combine( $post_ids, array_fill( 0, count( $post_ids ), 'POST_ID' ) ) + $attendee_data;
		uksort(
			$replace,
			function ( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			} 
		);
		$html = str_replace( array_keys( $replace ), (array) $replace, $html );
		
		$this->assertMatchesHtmlSnapshot( $html );
	}
}
