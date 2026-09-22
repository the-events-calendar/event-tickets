<?php

namespace TEC\Tickets\Seating\Orders;

use Generator;
use lucatume\WPBrowser\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Tables\Sessions as Sessions_Table;
use Tribe\Shortcode\Manager;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use TEC\Tickets\Commerce\Cart as TicketsCommerce_Cart;
use Tribe\Tickets\Test\Traits\Seating_Sessions;

class Cart_Test extends WPTestCase {
	use Seating_Sessions;
	use Ticket_Maker;
	use Attendee_Maker;
	use With_Tickets_Commerce;
	use Reservations_Maker;
	use SnapshotAssertions;

	/**
	 * @before
	 * @after
	 */
	public function truncate_tables(): void {
		tribe( Sessions_Table::class )->empty_table();
	}

	/**
	 * The quantity is posted by the browser alongside the seats. Without this, a quantity larger than
	 * the seats actually held mints attendees with no reservation and no seat.
	 *
	 * @test
	 * @covers Cart::handle_seat_selection
	 */
	public function test_handle_seat_selection_caps_quantity_to_the_seats_held(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );

		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$this->given_a_started_session( 'test-token', $post );

		$held = 2;
		tribe( Sessions_Table::class )->update_reservations(
			'test-token',
			$this->create_mock_reservations_data( [ $ticket ], $held )
		);

		$cart = tribe( Cart::class );
		$data = $cart->handle_seat_selection(
			[
				'tickets' => [
					[
						'ticket_id'   => $ticket,
						'quantity'    => $held + 3,
						'seat_labels' => [ 'seat-label-0-1', 'seat-label-0-2' ],
					],
				],
			]
		);

		$this->assertEquals( $held, $data['tickets'][0]['quantity'] );
	}

	/**
	 * @test
	 * @covers Cart::handle_seat_selection
	 */
	public function test_handle_seat_selection_leaves_a_matching_quantity_alone(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );

		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$this->given_a_started_session( 'test-token', $post );

		$held = 2;
		tribe( Sessions_Table::class )->update_reservations(
			'test-token',
			$this->create_mock_reservations_data( [ $ticket ], $held )
		);

		$cart = tribe( Cart::class );
		$data = $cart->handle_seat_selection(
			[
				'tickets' => [
					[
						'ticket_id'   => $ticket,
						'quantity'    => $held,
						'seat_labels' => [ 'seat-label-0-1', 'seat-label-0-2' ],
					],
				],
			]
		);

		$this->assertEquals( $held, $data['tickets'][0]['quantity'] );
		$this->assertEquals( [ 'seat-label-0-1', 'seat-label-0-2' ], $data['tickets'][0]['extra']['seats'] );
	}

	/**
	 * Each case posts the same cart, three seats and a quantity of three, so the session alone decides
	 * what comes back. The expected values sit side by side: nothing held means nothing measured, and
	 * the cart is left as posted; a session holding fewer seats cuts quantity and labels together.
	 */
	public function seat_selection_cap_provider(): Generator {
		yield 'no session leaves the cart as posted' => [
			'seats_held'        => null,
			'expected_quantity' => 3,
			'expected_labels'   => [ 'seat-label-0-1', 'seat-label-0-2', 'seat-label-0-3' ],
		];

		yield 'session holding one seat cuts quantity and labels to one' => [
			'seats_held'        => 1,
			'expected_quantity' => 1,
			'expected_labels'   => [ 'seat-label-0-1' ],
		];

		yield 'session holding all three seats leaves the cart as posted' => [
			'seats_held'        => 3,
			'expected_quantity' => 3,
			'expected_labels'   => [ 'seat-label-0-1', 'seat-label-0-2', 'seat-label-0-3' ],
		];
	}

	/**
	 * @test
	 * @dataProvider seat_selection_cap_provider
	 * @covers Cart::handle_seat_selection
	 */
	public function test_handle_seat_selection_caps_only_against_a_session( ?int $seats_held, int $expected_quantity, array $expected_labels ): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );

		if ( null !== $seats_held ) {
			tribe( Session::class )->add_entry( $post, 'test-token' );
			$this->given_a_started_session( 'test-token', $post );
			tribe( Sessions_Table::class )->update_reservations(
				'test-token',
				$this->create_mock_reservations_data( [ $ticket ], $seats_held )
			);
		}

		// The session is the yardstick, so state what it holds before measuring the cart against it.
		$held = tribe( Sessions_Table::class )->get_reservations_for_token( 'test-token' );
		if ( null === $seats_held ) {
			$this->assertNull( tribe( Session::class )->get_session_token_object_id(), 'No session should resolve.' );
			$this->assertEmpty( $held, 'No seats should be held.' );
		} else {
			$this->assertEquals( [ 'test-token', $post ], tribe( Session::class )->get_session_token_object_id() );
			$this->assertCount( $seats_held, $held[ $ticket ], 'The session should hold the given seats for the ticket.' );
		}

		$data = tribe( Cart::class )->handle_seat_selection(
			[
				'tickets' => [
					[
						'ticket_id'   => $ticket,
						'quantity'    => 3,
						'seat_labels' => [ 'seat-label-0-1', 'seat-label-0-2', 'seat-label-0-3' ],
					],
				],
			]
		);

		$this->assertEquals( $expected_quantity, $data['tickets'][0]['quantity'] );
		$this->assertEquals( $expected_labels, $data['tickets'][0]['extra']['seats'] );
	}

	public function test_save_seat_data_for_attendee():void{
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		// Create the session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$this->given_a_started_session( 'test-token', $post );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket ], 1 ) );
		$attendee        = $this->create_attendee_for_ticket( $ticket, $post );
		$attendee_object = get_post( $attendee );
		$attendee_object->event_id = $post;
		$attendee_object->product_id = $ticket;
		$ticket_object   = tribe(Module::class)->get_ticket( $post, $ticket );

		$cart = tribe( Cart::class );
		$cart->save_seat_data_for_attendee( $attendee_object, $ticket_object );

		$this->assertEquals( 'reservation-id-1', get_post_meta( $attendee, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-1', get_post_meta( $attendee, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	public function test_save_seat_data_for_many_attendees():void{
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		// Create the session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$this->given_a_started_session( 'test-token', $post );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket ], 2 ) );
		$attendee_1        = $this->create_attendee_for_ticket( $ticket, $post );
		$attendee_1_object = get_post( $attendee_1 );
		$attendee_1_object->event_id = $post;
		$attendee_1_object->product_id = $ticket;
		$attendee_2        = $this->create_attendee_for_ticket( $ticket, $post );
		$attendee_2_object = get_post( $attendee_2 );
		$attendee_2_object->event_id = $post;
		$attendee_2_object->product_id = $ticket;
		$ticket_object   = tribe(Module::class)->get_ticket( $post, $ticket );

		$cart = tribe( Cart::class );
		$cart->save_seat_data_for_attendee( $attendee_1_object, $ticket_object );
		// Delete the session after the first save to test memoization.
		$sessions_table->delete_token_session('test-token');
		$cart->save_seat_data_for_attendee( $attendee_2_object, $ticket_object );

		$this->assertEquals( 'reservation-id-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-1', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( 'reservation-id-2', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-2', get_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_2, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	public function test_save_seat_data_for_attendees_for_different_tickets():void{
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_1 = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_2 = $this->create_tc_ticket( $post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		// Create the session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$this->given_a_started_session( 'test-token', $post );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket_1, $ticket_2 ], 2 ) );
		// Create the Attendees for the first ticket.
		$attendee_1        = $this->create_attendee_for_ticket( $ticket_1, $post );
		$attendee_1_object = get_post( $attendee_1 );
		$attendee_1_object->event_id = $post;
		$attendee_1_object->product_id = $ticket_1;
		$attendee_2        = $this->create_attendee_for_ticket( $ticket_1, $post );
		$attendee_2_object = get_post( $attendee_2 );
		$attendee_2_object->event_id = $post;
		$attendee_2_object->product_id = $ticket_1;
		$ticket_1_object   = tribe(Module::class)->get_ticket( $post, $ticket_1 );
		// Create the 2 Attendees for the second ticket.
		$attendee_3        = $this->create_attendee_for_ticket( $ticket_2, $post );
		$attendee_3_object = get_post( $attendee_3 );
		$attendee_3_object->event_id = $post;
		$attendee_3_object->product_id = $ticket_2;
		$attendee_4        = $this->create_attendee_for_ticket( $ticket_2, $post );
		$attendee_4_object = get_post( $attendee_4 );
		$attendee_4_object->event_id = $post;
		$attendee_4_object->product_id = $ticket_2;
		$ticket_2_object   = tribe(Module::class)->get_ticket( $post, $ticket_2 );

		$cart = tribe( Cart::class );
		$cart->save_seat_data_for_attendee( $attendee_1_object, $ticket_1_object );
		// Delete the session after the first save to test memoization.
		$sessions_table->delete_token_session('test-token');
		$cart->save_seat_data_for_attendee( $attendee_2_object, $ticket_1_object );
		$cart->save_seat_data_for_attendee( $attendee_3_object, $ticket_2_object );
		$cart->save_seat_data_for_attendee( $attendee_4_object, $ticket_2_object );

		$this->assertEquals( 'reservation-id-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-1', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( 'reservation-id-2', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-2', get_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_2, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( 'reservation-id-3', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-1-1', get_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-1', get_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_3, Meta::META_KEY_LAYOUT_ID, true ) );
		$this->assertEquals( 'reservation-id-4', get_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-1-2', get_post_meta( $attendee_4, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-1', get_post_meta( $attendee_4, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee_4, Meta::META_KEY_LAYOUT_ID, true ) );
	}

	/**
	 * @test
	 *
	 * @covers Cart::maybe_clear_cart_for_empty_session
	 */
	public function test_clearing_tc_cart_when_session_and_cart_is_valid() {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_1 = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_1, Meta::META_KEY_ENABLED, 1 );
		$ticket_2 = $this->create_tc_ticket( $post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, 1 );

		// Create the expired session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$this->given_a_started_session( 'test-token', $post );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket_1, $ticket_2 ], 2 ) );

		$tc_cart = tribe( TicketsCommerce_Cart::class );

		$tc_cart->add_ticket( $ticket_1, 2 );
		$tc_cart->add_ticket( $ticket_2, 2 );

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		// The cart HTML should be showing session timer, items and not be empty.
		$this->assertContains( 'tribe-tickets__commerce-checkout-cart-items', $html );
		$this->assertNotContains( 'tribe-tickets__commerce-checkout-cart-empty', $html );
	}

	/**
	 * @test
	 *
	 * @covers Cart::maybe_clear_cart_for_empty_session
	 */
	public function test_clearing_tc_cart_when_token_is_expired() {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_1 = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_1, Meta::META_KEY_ENABLED, 1 );
		$ticket_2 = $this->create_tc_ticket( $post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, 1 );

		// Create the expired session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$sessions_table->insert_or_update( 'test-token', $post, time() - 100 );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket_1, $ticket_2 ], 2 ) );

		$tc_cart = tribe( TicketsCommerce_Cart::class );

		$tc_cart->add_ticket( $ticket_1, 2 );
		$tc_cart->add_ticket( $ticket_2, 2 );

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		// As the session info is expired the cart should be empty.
		$this->assertContains( 'tribe-tickets__commerce-checkout-cart-empty', $html );
	}

	public function test_cart_has_seating_tickets(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$asc_ticket = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $asc_ticket, Meta::META_KEY_ENABLED, true );
		$gac_ticket = $this->create_tc_ticket( $post, 10 );

		$tc_cart = tribe( TicketsCommerce_Cart::class );
		$cart = tribe( Cart::class );

		// Empty cart.
		$this->assertFalse( $cart->cart_has_seating_tickets() );

		$tc_cart->add_ticket( $gac_ticket, 3 );

		// 3 GAC tickets.
		$this->assertFalse( $cart->cart_has_seating_tickets() );

		$tc_cart->add_ticket( $asc_ticket, 2 );

		// 3 GAC tickets and 2 ASC tickets.
		$this->assertTrue( $cart->cart_has_seating_tickets() );

		$tc_cart->remove_ticket( $gac_ticket, 3 );

		// 2 ASC tickets.
		$this->assertTrue( $cart->cart_has_seating_tickets() );

		$tc_cart->remove_ticket( $asc_ticket, 1 );

		// 1 ASC ticket.
		$this->assertTrue( $cart->cart_has_seating_tickets() );

		$tc_cart->remove_ticket( $asc_ticket, 1 );

		// Empty Cart.
		$this->assertFalse( $cart->cart_has_seating_tickets() );
	}

	/**
	 * @test
	 *
	 * @covers Cart::maybe_clear_cart_for_empty_session
	 */
	public function test_clearing_tc_cart_when_session_is_not_found() {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket_1 = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket_1, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_1, Meta::META_KEY_ENABLED, 1 );
		$ticket_2 = $this->create_tc_ticket( $post, 20 );
		update_post_meta( $ticket_2, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, 1 );

		$tc_cart = tribe( TicketsCommerce_Cart::class );

		$tc_cart->add_ticket( $ticket_1, 2 );
		$tc_cart->add_ticket( $ticket_2, 2 );

		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$html = do_shortcode( '[tec_tickets_checkout]' );

		// As we only have added seated tickets but no session data was added, the cart should be empty.
		$this->assertContains( 'tribe-tickets__commerce-checkout-cart-empty', $html );
	}

	public function test_cache_warmup(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		$ticket = $this->create_tc_ticket( $post, 10 );
		update_post_meta( $ticket, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );
		// Create the session information.
		$session = tribe( Session::class );
		$session->add_entry( $post, 'test-token' );
		$sessions_table = tribe( Sessions_Table::class );
		$this->given_a_started_session( 'test-token', $post );
		$sessions_table->update_reservations( 'test-token', $this->create_mock_reservations_data( [ $ticket ], 1 ) );
		$attendee                    = $this->create_attendee_for_ticket( $ticket, $post );
		$attendee_object             = get_post( $attendee );
		$attendee_object->event_id   = $post;
		$attendee_object->product_id = $ticket;
		$ticket_object               = tribe( Module::class )->get_ticket( $post, $ticket );

		$cart = tribe( Cart::class );

		// Warm-up the caches.
		$cart->warmup_caches();

		// Now clear the sessions.
		$sessions = tribe( Sessions::class );
		$sessions->delete_token_session( 'test-token' );
		$this->assertEmpty( $sessions->get_reservations_for_token( 'test-token' ) );

		$cart->save_seat_data_for_attendee( $attendee_object, $ticket_object );

		$this->assertEquals( 'reservation-id-1', get_post_meta( $attendee, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-label-0-1', get_post_meta( $attendee, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->assertEquals( 'seat-type-id-0', get_post_meta( $attendee, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'layout-uuid-1', get_post_meta( $attendee, Meta::META_KEY_LAYOUT_ID, true ) );
	}
}
