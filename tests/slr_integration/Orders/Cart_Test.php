<?php

namespace TEC\Tickets\Seating\Orders;

use lucatume\WPBrowser\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Sessions;
use TEC\Tickets\Seating\Tables\Sessions as Sessions_Table;
use Tribe\Shortcode\Manager;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Commerce\Cart as TicketsCommerce_Cart;

class Cart_Test extends WPTestCase {
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
		Sessions_Table::truncate();
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
		$sessions_table->upsert( 'test-token', $post, time() + 100 );
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
		$sessions_table->upsert( 'test-token', $post, time() + 100 );
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
		$sessions_table->upsert( 'test-token', $post, time() + 100 );
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
		$sessions_table->upsert( 'test-token', $post, time() + 100 );
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
		$sessions_table->upsert( 'test-token', $post, time() - 100 );
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
		$sessions_table->upsert( 'test-token', $post, time() + 100 );
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
