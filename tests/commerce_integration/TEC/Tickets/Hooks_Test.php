<?php

namespace TEC\Tickets;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Hooks_Test extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @before
	 */
	public function ensure_clean_cart() {
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * @after
	 */
	public function cleanup_cart() {
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * Creates a Completed Tickets Commerce order and returns the attendee ID it generated.
	 *
	 * @return array{0: \WP_Post, 1: int} The order and its (first) attendee ID.
	 */
	protected function create_order_and_attendee(): array {
		$post      = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post, 10 );

		$order = $this->create_order_through_stripe( [ $ticket_id => 1 ] );

		$module    = tribe( Module::class );
		// Attendees are looked up by the ticketable post (event), not by the order ID.
		$attendees = $module->get_attendees_by_id( $post );

		$this->assertNotEmpty( $attendees, 'A Completed order should have generated an attendee.' );

		$attendee = reset( $attendees );

		return [ $order, (int) $attendee['attendee_id'] ];
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::prevent_checkin_for_invalid_order_status
	 */
	public function it_should_allow_checkin_when_order_is_completed() {
		[ , $attendee_id ] = $this->create_order_and_attendee();

		$module = tribe( Module::class );

		$this->assertTrue( $module->checkin( $attendee_id ) );
		$this->assertEquals( 1, (int) get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::prevent_checkin_for_invalid_order_status
	 */
	public function it_should_prevent_checkin_when_order_is_refunded() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		$this->assertTrue( tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG ) );
		clean_post_cache( $order->ID );

		$module = tribe( Module::class );

		$this->assertFalse( $module->checkin( $attendee_id ) );
		$this->assertEmpty( get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::prevent_checkin_for_invalid_order_status
	 */
	public function it_should_prevent_undoing_bypass_for_refunded_order_via_qr_flag() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		$this->assertTrue( tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG ) );
		clean_post_cache( $order->ID );

		$module = tribe( Module::class );

		// The guard should block check-in regardless of the $qr flag/entry point.
		$this->assertFalse( $module->checkin( $attendee_id, true ) );
		$this->assertEmpty( get_post_meta( $attendee_id, '_tribe_qr_status', true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::prevent_checkin_for_invalid_order_status
	 */
	public function it_should_prevent_checkin_when_a_refund_is_deferred_by_the_checkout_hold() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		// The order's own status still reads Completed: the refund webhook hasn't been applied yet.
		tribe( Order::class )->set_on_checkout_screen_hold( $order->ID );
		tribe( Webhooks::class )->add_pending_webhook(
			$order->ID,
			tribe( Status_Handler::class )->get_by_slug( Refunded::SLUG )->get_wp_slug(),
			$order->post_status,
			[]
		);

		$refreshed_order = tec_tc_get_order( $order->ID );
		$this->assertEquals( Completed::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		$module = tribe( Module::class );

		$this->assertFalse( $module->checkin( $attendee_id ) );
		$this->assertEmpty( get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::uncheckin_attendee_on_archive
	 */
	public function it_should_uncheckin_attendee_when_its_order_is_refunded_after_an_earlier_checkin() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		$module = tribe( Module::class );

		// The Attendee legitimately checks in while the order still reads Completed - e.g. before a
		// refund webhook the backend hasn't received/applied yet.
		$this->assertTrue( $module->checkin( $attendee_id ) );
		$this->assertEquals( 1, (int) get_post_meta( $attendee_id, $module->checkin_key, true ) );

		// Simulate the held-webhook resolution context by setting the meta flag so the
		// archive-triggered uncheckin recognizes this as a deferred-webhook resolution.
		update_post_meta( $order->ID, '_tec_tickets_commerce_webhook_resolving_archive', 1 );

		// Once the refund is applied, the Attendee is archived - and the stale check-in must be revoked.
		$this->assertTrue( tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG ) );

		$this->assertEmpty( get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::uncheckin_attendee_on_archive
	 */
	public function it_should_not_uncheckin_when_attendee_was_never_checked_in_under_held_webhook() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		$uncheckin_ran = false;
		add_action( 'event_tickets_uncheckin', function () use ( &$uncheckin_ran ) {
			$uncheckin_ran = true;
		} );

		// Simulate the held-webhook resolution context.
		update_post_meta( $order->ID, '_tec_tickets_commerce_webhook_resolving_archive', 1 );

		// The attendee is archived without ever having been checked in.
		$this->assertTrue( tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG ) );

		$this->assertFalse( $uncheckin_ran, 'uncheckin() should not run for an attendee that was never checked in.' );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::uncheckin_attendee_on_archive
	 */
	public function it_should_not_uncheckin_when_archive_is_outside_held_webhook_context() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		$module = tribe( Module::class );

		$this->assertTrue( $module->checkin( $attendee_id ) );
		$this->assertEquals( 1, (int) get_post_meta( $attendee_id, $module->checkin_key, true ) );

		// No meta flag set — archive from a post-event refund or direct status change must NOT uncheck.
		$this->assertTrue( tribe( Order::class )->modify_status( $order->ID, Refunded::SLUG ) );

		// Check-in must be preserved for legitimate archives outside the hold window.
		$this->assertEquals( 1, (int) get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Hooks::prevent_checkin_for_invalid_order_status
	 */
	public function it_should_allow_checkin_when_still_on_checkout_hold_with_no_pending_refund() {
		[ $order, $attendee_id ] = $this->create_order_and_attendee();

		// Being on hold by itself must not block check-in; only an actual pending refund should.
		tribe( Order::class )->set_on_checkout_screen_hold( $order->ID );

		$module = tribe( Module::class );

		$this->assertTrue( $module->checkin( $attendee_id ) );
		$this->assertEquals( 1, (int) get_post_meta( $attendee_id, $module->checkin_key, true ) );
	}
}
