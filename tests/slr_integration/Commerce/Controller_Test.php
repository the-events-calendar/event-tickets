<?php

namespace TEC\Tickets\Seating\Commerce;

use Closure;
use Generator;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Tests\Integration\Truncates_Custom_Tables;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets;
use TEC\Tickets\Seating\Service\Service_Status;
use WP_Post;

class Controller_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;
	use Truncates_Custom_Tables;

	protected string $controller_class = Controller::class;

	/**
	 * @before
	 */
	public function ensure_ticketable_post_types(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter(
			'tribe_tickets_get_modules',
			function ( $modules ) {
				$modules[ Module::class ] = tribe( Module::class )->plugin_name;

				return $modules;
			}
		);

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API() );
	}

	public function filter_timer_token_object_id_entries_data_provider(): Generator {
		yield 'no entries' => [
			function (): array {
				return [
					[],
					[],
				];
			},
		];

		yield 'not on checkout page' => [
			function (): array {
				$post_id   = static::factory()->post->create();
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_false' );

				return [
					[ $post_id => 'test-token' ],
					[ $post_id => 'test-token' ],
				];
			},
		];

		yield 'on checkout page but no ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id = static::factory()->post->create();
				$ticket_id      = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$this->create_tc_ticket( $asc_post_id, 10 );

				return [
					[ $asc_post_id => 'test-token' ],
					[],
				];
			},
		];

		yield 'on checkout page with ASC post in cart' => [
			function (): array {
				add_filter( 'tec_tickets_commerce_checkout_is_current_page', '__return_true' );
				$no_asc_post_id   = static::factory()->post->create();
				$no_asc_ticket_id = $this->create_tc_ticket( $no_asc_post_id, 10 );
				/** @var Cart $cart */
				$cart = tribe( Cart::class );
				$cart->add_ticket( $no_asc_ticket_id, 1 );
				$asc_post_id = static::factory()->post->create();
				update_post_meta( $asc_post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
				$asc_ticket_id = $this->create_tc_ticket( $asc_post_id, 10 );
				$cart->add_ticket( $asc_ticket_id, 1 );

				return [
					[ $asc_post_id => 'test-token' ],
					[ $asc_post_id => 'test-token' ],
				];
			},
		];
	}

	public function test_tc_shared_capacity_purchase(): void {
		$controller = $this->make_controller();
		$controller->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 100.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		$ticket_id1 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		$ticket_id2 = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		$ticket_id3 = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);

		$ticket_id4 = $this->create_tc_ticket(
			$event_id,
			40,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);

		$ticket_id5 = $this->create_tc_ticket(
			$event_id,
			50,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);

		// Create the Seat Types.
		Seat_Types::insert_many(
			[
				[
					'id'     => 'seat-type-uuid-A',
					'name'   => 'A',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-uuid-B',
					'name'   => 'B',
					'seats'  => 50,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-uuid-C',
					'name'   => 'C',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		// Group A.
		update_post_meta( $ticket_id1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-A' );
		update_post_meta( $ticket_id2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-A' );
		// Group B.
		update_post_meta( $ticket_id3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-B' );
		update_post_meta( $ticket_id4, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-B' );
		// Group C.
		update_post_meta( $ticket_id5, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-C' );

		// Get the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		$ticket_3 = tribe( Module::class )->get_ticket( $event_id, $ticket_id3 );
		$ticket_4 = tribe( Module::class )->get_ticket( $event_id, $ticket_id4 );
		$ticket_5 = tribe( Module::class )->get_ticket( $event_id, $ticket_id5 );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_1 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_2 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_3 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_4 );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_5 );

		$this->assertEquals( 30, $ticket_1->capacity() );
		$this->assertEquals( 30, $ticket_1->stock() );
		$this->assertEquals( 30, $ticket_1->available() );
		$this->assertEquals( 30, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30, $ticket_2->stock() );
		$this->assertEquals( 30, $ticket_2->available() );
		$this->assertEquals( 30, $ticket_2->inventory() );

		$this->assertEquals( 50, $ticket_3->capacity() );
		$this->assertEquals( 50, $ticket_3->stock() );
		$this->assertEquals( 50, $ticket_3->available() );
		$this->assertEquals( 50, $ticket_3->inventory() );

		$this->assertEquals( 50, $ticket_4->capacity() );
		$this->assertEquals( 50, $ticket_4->stock() );
		$this->assertEquals( 50, $ticket_4->available() );
		$this->assertEquals( 50, $ticket_4->inventory() );

		$this->assertEquals( 20, $ticket_5->capacity() );
		$this->assertEquals( 20, $ticket_5->stock() );
		$this->assertEquals( 20, $ticket_5->available() );
		$this->assertEquals( 20, $ticket_5->inventory() );


		$global_stock = new Global_Stock( $event_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 100, tribe_get_event_capacity( $event_id ), 'Total Event capacity should be 100' );
		$this->assertEquals( 100, $global_stock->get_stock_level(), 'Global stock should be 100' );

		// Create an Order for 5 on each Ticket.
		$order = $this->create_order(
			[
				$ticket_id1 => 2,
				$ticket_id2 => 3, // Group A total 5!
				$ticket_id3 => 4,
				$ticket_id4 => 3, // Group B total 7!
				$ticket_id5 => 5, // Group C total 5!
			]
		);

		// Refresh the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		$ticket_3 = tribe( Module::class )->get_ticket( $event_id, $ticket_id3 );
		$ticket_4 = tribe( Module::class )->get_ticket( $event_id, $ticket_id4 );
		$ticket_5 = tribe( Module::class )->get_ticket( $event_id, $ticket_id5 );

		$this->assertEquals( 30, $ticket_1->capacity() );
		$this->assertEquals( 30 - 5, $ticket_1->stock() );
		$this->assertEquals( 30 - 5, $ticket_1->available() );
		$this->assertEquals( 30 - 5, $ticket_1->inventory() );

		$this->assertEquals( 30, $ticket_2->capacity() );
		$this->assertEquals( 30 - 5, $ticket_2->stock() );
		$this->assertEquals( 30 - 5, $ticket_2->available() );
		$this->assertEquals( 30 - 5, $ticket_2->inventory() );

		$this->assertEquals( 50, $ticket_3->capacity() );
		$this->assertEquals( 50 - 7, $ticket_3->stock() );
		$this->assertEquals( 50 - 7, $ticket_3->available() );
		$this->assertEquals( 50 - 7, $ticket_3->inventory() );

		$this->assertEquals( 50, $ticket_4->capacity() );
		$this->assertEquals( 50 - 7, $ticket_4->stock() );
		$this->assertEquals( 50 - 7, $ticket_4->available() );
		$this->assertEquals( 50 - 7, $ticket_4->inventory() );

		$this->assertEquals( 20, $ticket_5->capacity() );
		$this->assertEquals( 20 - 5, $ticket_5->stock() );
		$this->assertEquals( 20 - 5, $ticket_5->available() );
		$this->assertEquals( 20 - 5, $ticket_5->inventory() );

		$this->assertEquals( 100 - 17, $global_stock->get_stock_level(), 'Global stock should be 100-17 = 83' );

		update_post_meta( $ticket_id1, Ticket::$stock_meta_key, -1 );

		// Refresh the ticket objects.
		$ticket_1 = tribe( Module::class )->get_ticket( $event_id, $ticket_id1 );
		$ticket_2 = tribe( Module::class )->get_ticket( $event_id, $ticket_id2 );
		// Make sure we are not syncing infinite seats.
		$this->assertEquals( 30 - 5, $ticket_1->stock() );
		$this->assertEquals( 30 - 5, $ticket_2->stock() );
	}

	/**
	 * @dataProvider filter_timer_token_object_id_entries_data_provider
	 * @return void
	 */
	public function test_filter_timer_token_object_id_entries( Closure $fixture ): void {
		[ $input_entries, $expected_entries ] = $fixture();

		$controller = $this->make_controller();
		$controller->register();

		$filtered_entries = apply_filters( 'tec_tickets_seating_timer_token_object_id_entries', $input_entries );

		$this->assertEquals(
			$expected_entries,
			$filtered_entries,
		);
	}

	public function test_stock_count_for_seated_tickets() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Create the Seat Types.
		Seat_Types::insert_many(
			[
				[
					'id'     => 'some-seat-type-uuid',
					'name'   => 'A',
					'seats'  => 5,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'other-seat-type-uuid',
					'name'   => 'B',
					'seats'  => 15,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-vip',
					'name'   => 'C',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-general',
					'name'   => 'D',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 5,
				],
			]
		);

		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'some-seat-type-uuid' );

		// Only`vip` ticket should be available.
		$counts = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 5, $counts['tickets']['stock'] );
		$this->assertEquals( 5, $counts['tickets']['available'] );

		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 15,
				],
			]
		);

		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'other-seat-type-uuid' );

		// Both `vip` and `general` tickets should be available.
		$counts = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 20, $counts['tickets']['stock'] );
		$this->assertEquals( 20, $counts['tickets']['available'] );

		$order = $this->create_order(
			[
				$vip => 5,
			]
		);

		// Stock should be reduced for `vip` ticket.
		$counts = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 15, $counts['tickets']['stock'] );
		$this->assertEquals( 15, $counts['tickets']['available'] );

		$order_2 = $this->create_order(
			[
				$general => 15,
			]
		);

		// Stock should be reduced for `general` ticket and no tickets should be available.
		$counts = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 0, $counts['tickets']['stock'] );
		$this->assertEquals( 0, $counts['tickets']['available'] );
	}

	public function test_stock_count_for_multiple_same_seated_types() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Create the Seat Types.
		Seat_Types::insert_many(
			[
				[
					'id'     => 'seat-type-uuid',
					'name'   => 'A',
					'seats'  => 10,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-vip',
					'name'   => 'B',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-general',
					'name'   => 'C',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);

		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid' );

		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);

		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid' );

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 10, $count['tickets']['stock'] );
		$this->assertEquals( 10, $count['tickets']['available'] );

		$order = $this->create_order(
			[
				$vip => 5,
			]
		);

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 5, $count['tickets']['stock'] );
		$this->assertEquals( 5, $count['tickets']['available'] );

		// test attendee count and stock after deleting an attendee.
		$attendees = tribe( Module::class )->get_event_attendees( $event_id );

		$this->assertEquals( 5, count( $attendees ) );

		$deleted = tribe( Module::class )->delete_ticket( $event_id, $attendees[0]['ID'] );

		$this->assertTrue( $deleted );

		$attendees = tribe( Module::class )->get_event_attendees( $event_id );

		$this->assertEquals( 4, count( $attendees ) );

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEquals( 6, $count['tickets']['stock'] );
	}

	public function test_stock_count_for_multiple_seat_typed_tickets() {
		$controller = $this->make_controller();
		$controller->register();
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Create the Seat Types.
		Seat_Types::insert_many(
			[
				[
					'id'     => 'seat-type-vip',
					'name'   => 'B',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-general',
					'name'   => 'C',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

		// Add a ticket with 5 capacity.
		$vip = $this->create_tc_ticket(
			$event_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);

		update_post_meta( $vip, Meta::META_KEY_SEAT_TYPE, 'seat-type-vip' );

		$general = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		update_post_meta( $general, Meta::META_KEY_SEAT_TYPE, 'seat-type-general' );

		$child = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		update_post_meta( $child, Meta::META_KEY_SEAT_TYPE, 'seat-type-general' );

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Should have full stock.
		$this->assertEquals( 50, $count['tickets']['stock'] );
		$this->assertEquals( 50, $count['tickets']['available'] );

		$order = $this->create_order(
			[
				$vip => 5,
			]
		);

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Should have full stock.
		$this->assertEquals( 45, $count['tickets']['stock'] );
		$this->assertEquals( 45, $count['tickets']['available'] );

		$order_2 = $this->create_order(
			[
				$vip     => 15,
				$general => 10,
				$child   => 10,
			]
		);

		$count = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Should be 45-25.
		$this->assertEquals( 50 - 20 - 20, $count['tickets']['stock'] );
		$this->assertEquals( 50 - 20 - 20, $count['tickets']['available'] );
	}

	public function test_no_capacity_updates_while_service_is_down() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$capacity_meta_key = tribe( 'tickets.handler' )->key_capacity;

		update_post_meta( $post_id, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, '1' );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );
		update_post_meta( $post_id, $capacity_meta_key, 30 );

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, '1' );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'some-seattype-id' );
		update_post_meta( $ticket_id, '_stock', 30 );
		update_post_meta( $ticket_id, $capacity_meta_key, 30 );

		$this->assertEquals( 30, get_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 30, get_post_meta( $post_id, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $ticket_id, $capacity_meta_key, true ) );
		$this->assertEquals( 30, get_post_meta( $ticket_id, '_stock', true ) );

		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 22 );
		update_post_meta( $post_id, $capacity_meta_key, 22 );
		update_post_meta( $ticket_id, '_stock', 22 );
		update_post_meta( $ticket_id, $capacity_meta_key, 22 );

		$this->assertEquals( 22, get_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 22, get_post_meta( $post_id, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $ticket_id, $capacity_meta_key, true ) );
		$this->assertEquals( 22, get_post_meta( $ticket_id, '_stock', true ) );

		$service_statuses = [
			Service_Status::SERVICE_DOWN,
			Service_Status::NOT_CONNECTED,
			Service_Status::INVALID_LICENSE,
			Service_Status::EXPIRED_LICENSE,
		];

		$this->make_controller()->register();

		foreach ( $service_statuses as $service_status ) {
			add_filter( 'tec_tickets_seating_service_status', fn( $_status, $backend_base_url ) => new Service_Status( $backend_base_url, $service_status ), 10, 2 );

			update_post_meta( $post_id, $capacity_meta_key, 26 );
			update_post_meta( $ticket_id, '_stock', 26 );
			update_post_meta( $ticket_id, $capacity_meta_key, 26 );

			// No changes while service is down!
			$this->assertEquals( 22, get_post_meta( $post_id, $capacity_meta_key, true ) );
			$this->assertEquals( 22, get_post_meta( $ticket_id, $capacity_meta_key, true ) );
			$this->assertEquals( 22, get_post_meta( $ticket_id, '_stock', true ) );
		}
	}

	public function test_stock_count_for_seated_tickets_replenished_on_attendee_deletion() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		Seat_Types::insert_many(
			[
				[
					'id'     => 'seat-type-A',
					'name'   => 'A',
					'seats'  => 5,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
				[
					'id'     => 'seat-type-B',
					'name'   => 'B',
					'seats'  => 15,
					'map'    => 'some-map-1',
					'layout' => 'some-layout-1',
				],
			]
		);
		set_transient( \TEC\Tickets\Seating\Service\Seat_Types::update_transient_name(), time() );

		// Ticket 1 and 2 use the same seat type A.
		$ticket_1 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 5,
				],
			]
		);
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-A' );
		update_post_meta( $ticket_1, '_stock', 5 );
		$ticket_2 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 5,
				],
			]
		);
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-A' );
		update_post_meta( $ticket_2, '_stock', 5 );

		// Ticket 3 and 4 use the same seat type B.
		$ticket_3 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 15,
				],
			]
		);
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-B' );
		update_post_meta( $ticket_3, '_stock', 15 );
		$ticket_4 = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 15,
				],
			]
		);
		update_post_meta( $ticket_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-B' );
		update_post_meta( $ticket_4, '_stock', 15 );

		// Check before order creation.
		$this->assertEquals( 5, get_post_meta( $ticket_1, '_stock', true ) );
		$this->assertEquals( 5, get_post_meta( $ticket_2, '_stock', true ) );
		$this->assertEquals( 15, get_post_meta( $ticket_3, '_stock', true ) );
		$this->assertEquals( 15, get_post_meta( $ticket_4, '_stock', true ) );

		// Register the controller.
		$controller = $this->make_controller();
		$controller->register();

		// Place an order for 3 Ticket 1 (Seat Type A) and 2 Ticket 3 (Seat Type B).
		$order_1 = $this->create_order(
			[
				$ticket_1 => 3,
				$ticket_3 => 2,
			]
		);

		// Check Attendees.
		$this->assertEquals( 3, tribe_attendees()->where( 'ticket', $ticket_1 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_2 )->count() );
		$this->assertEquals( 2, tribe_attendees()->where( 'ticket', $ticket_3 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_4 )->count() );

		// Check after order creation.
		$this->assertEquals( 2, (int) get_post_meta( $ticket_1, '_stock', true ) );
		$this->assertEquals( 2, (int) get_post_meta( $ticket_2, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_3, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_4, '_stock', true ) );

		// Delete an Attendee from Ticket 1.
		$ticket_1_attendee_1 = tribe_attendees()->where('ticket', $ticket_1)->first_id();
		$this->assertInstanceOf( WP_Post::class, wp_delete_post( $ticket_1_attendee_1 ) );

		// Check Attendees after Attendee deletion.
		$this->assertEquals( 2, tribe_attendees()->where( 'ticket', $ticket_1 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_2 )->count() );
		$this->assertEquals( 2, tribe_attendees()->where( 'ticket', $ticket_3 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_4 )->count() );

		// Check stock after Attendee deletion.
		$this->assertEquals( 3, (int) get_post_meta( $ticket_1, '_stock', true ) );
		$this->assertEquals( 3, (int) get_post_meta( $ticket_2, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_3, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_4, '_stock', true ) );

		// Trash an Attendee from Ticket 1.
		$ticket_1_attendee_2 = tribe_attendees()->where('ticket', $ticket_1)->first_id();
		wp_trash_post( $ticket_1_attendee_2 );

		// Check Attendees after Attendee deletion.
		$this->assertEquals( 1, tribe_attendees()->where( 'ticket', $ticket_1 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_2 )->count() );
		$this->assertEquals( 2, tribe_attendees()->where( 'ticket', $ticket_3 )->count() );
		$this->assertEquals( 0, tribe_attendees()->where( 'ticket', $ticket_4 )->count() );

		// Check stock after Attendee deletion.
		$this->assertEquals( 4, (int) get_post_meta( $ticket_1, '_stock', true ) );
		$this->assertEquals( 4, (int) get_post_meta( $ticket_2, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_3, '_stock', true ) );
		$this->assertEquals( 13, (int) get_post_meta( $ticket_4, '_stock', true ) );
	}
}
