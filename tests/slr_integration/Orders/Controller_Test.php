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
		yield 'single event with 3 seated ticket attendee' => [
			function (): array {
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
				
				$ticket_id = $this->create_tc_ticket( $event_id, 10 );
				
				$tribe_tickets_ar_data = wp_json_encode(
					[
						'tribe_tickets_tickets' => [
							[
								'ticket_id'   => $ticket_id,
								'quantity'    => 3,
								'optout'      => '1',
								'seat_labels' => [ 'B-4', 'D-1', 'C-3' ],
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

				$cart->parse_request();
				
				$purchaser = [
					'purchaser_user_id'    => 0,
					'purchaser_full_name'  => 'Test Purchaser',
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser',
					'purchaser_email'      => 'test-' . uniqid() . '@test.com',
				];
				
				$orders = tribe( Order::class );
				$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );
				
				$orders->modify_status( $order->ID, Pending::SLUG );
				
				clean_post_cache( $order->ID );
				$cart->clear_cart();
				
				return [ $event_id, [ $event_id, $ticket_id ] ];
			},
		];
		yield 'single event with 3 regular Ticket attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				
				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );
				
				$ticket_id = $this->create_tc_ticket( $event_id );
				$order     = $this->create_order( [ $ticket_id => 3 ] );
				
				return [ $event_id, [ $event_id, $ticket_id ] ];
			},
		];
	}
	
	/**
	 * Test the attendee list seat column data.
	 *
	 * @dataProvider attendee_data_provider
	 *
	 * @return void
	 */
	public function test_attendee_list_seat_column( Closure $fixture ): void {
		$_GET['search'] = '';
		$_GET['page']   = 'tickets-attendees';
		
		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$this->set_fn_return( 'is_admin', 'true' );
		
		[ $post_id, $post_ids ] = $fixture();
		
		$_GET['event_id'] = $post_id;
		
		tribe_cache()->reset();
		ob_start();
		
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
	
	/**
	 * Test the attendee list seat column data.
	 *
	 * @dataProvider attendee_data_provider
	 *
	 * @return void
	 */
	public function test_attendee_list_seat_column_desc_order( Closure $fixture ): void {
		$_GET['search']  = '';
		$_GET['page']    = 'tickets-attendees';
		$_GET['orderby'] = 'seat';
		$_GET['order']   = 'desc';
		
		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		
		[ $post_id, $post_ids ] = $fixture();
		
		$_GET['event_id'] = $post_id;
		
		tribe_cache()->reset();
		ob_start();
		
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
