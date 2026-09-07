<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use stdClass;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use WP_Post;

/**
 * Ensures TC-RSVP orders never trigger the purchase-oriented Commerce emails
 * (Purchase Receipt, Order Completed, generic ticket email) and instead send
 * the correct RSVP confirmation email, while regular paid orders are unaffected.
 */
class Order_Completion_Emails_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * Builds a TC-RSVP order the same way Order_Endpoint::process_rsvp_step() does: via the
	 * dedicated RSVP_Cart (not the generic Cart, which hardcodes item type to 'ticket' and
	 * would silently drop the RSVP item), then transitions it Pending -> Completed.
	 */
	private function create_tc_rsvp_order( int $ticket_id, string $order_status, array $purchaser_overrides = [] ): WP_Post {
		/** @var RSVP_Cart $cart */
		$cart = tribe( RSVP_Cart::class );
		$cart->clear();
		$cart->upsert_item(
			$ticket_id,
			1,
			[
				'type'         => Constants::TC_RSVP_TYPE,
				'order_status' => $order_status,
			]
		);
		$cart->save();

		$purchaser = wp_parse_args(
			$purchaser_overrides,
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'Test Purchaser',
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'Purchaser',
				'purchaser_email'      => 'attendee@example.com',
			]
		);

		/** @var Order $orders */
		$orders = tribe( Order::class );
		$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser, Constants::TC_RSVP_TYPE );

		$orders->modify_status( $order->ID, Pending::SLUG );
		$orders->modify_status( $order->ID, Completed::SLUG );

		$cart->clear();

		return $order;
	}

	/**
	 * Registers counters for the Purchase Receipt / Order Completed prepare-order filters.
	 * Returns an stdClass whose properties the caller can assert against: since objects are
	 * shared by handle in PHP, the closures below and the caller observe the same counters.
	 */
	private function intercept_prepare_email_filters(): stdClass {
		$counts                   = new stdClass();
		$counts->purchase_receipt = 0;
		$counts->completed_order  = 0;

		add_filter(
			'tec_tickets_commerce_prepare_order_for_email_send_email_purchase_receipt',
			function ( $order ) use ( $counts ) {
				$counts->purchase_receipt++;

				return $order;
			}
		);

		add_filter(
			'tec_tickets_commerce_prepare_order_for_email_send_email_completed_order',
			function ( $order ) use ( $counts ) {
				$counts->completed_order++;

				return $order;
			}
		);

		return $counts;
	}

	/**
	 * Intercepts wp_mail() calls and logs them on the returned stdClass's `emails` property.
	 */
	private function intercept_wp_mail(): stdClass {
		$log         = new stdClass();
		$log->emails = [];

		$this->set_fn_return(
			'wp_mail',
			function ( $to, $subject, $message, $headers = '', $attachments = [] ) use ( $log ) {
				$log->emails[] = [
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
				];

				return true;
			},
			true
		);

		return $log;
	}

	public function test_going_rsvp_order_sends_only_the_rsvp_confirmation_email(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$counts = $this->intercept_prepare_email_filters();
		$mail   = $this->intercept_wp_mail();

		$this->create_tc_rsvp_order( $ticket_id, 'yes', [ 'purchaser_email' => 'attendee@example.com' ] );

		$this->assertEquals( 0, $counts->purchase_receipt, 'Purchase Receipt must not fire for RSVP orders.' );
		$this->assertEquals( 0, $counts->completed_order, 'Order Completed must not fire for RSVP orders.' );
		$this->assertCount( 1, $mail->emails, 'Exactly one email (the RSVP confirmation) should be sent.' );
		$this->assertEquals( 'attendee@example.com', $mail->emails[0]['to'] );
	}

	public function test_not_going_rsvp_order_sends_only_the_rsvp_not_going_email(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$counts = $this->intercept_prepare_email_filters();
		$mail   = $this->intercept_wp_mail();

		$this->create_tc_rsvp_order( $ticket_id, 'no', [ 'purchaser_email' => 'attendee@example.com' ] );

		$this->assertEquals( 0, $counts->purchase_receipt, 'Purchase Receipt must not fire for RSVP orders.' );
		$this->assertEquals( 0, $counts->completed_order, 'Order Completed must not fire for RSVP orders.' );
		$this->assertCount( 1, $mail->emails, 'Exactly one email (the RSVP not-going confirmation) should be sent.' );
		$this->assertEquals( 'attendee@example.com', $mail->emails[0]['to'] );
	}

	public function test_normal_order_still_sends_purchase_receipt_and_completed_order(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$counts = $this->intercept_prepare_email_filters();
		$mail   = $this->intercept_wp_mail();

		$this->create_order( [ $ticket_id => 1 ], [ 'purchaser_email' => 'attendee@example.com' ] );

		$this->assertEquals( 1, $counts->purchase_receipt, 'Purchase Receipt must still fire for real purchases.' );
		$this->assertEquals( 1, $counts->completed_order, 'Order Completed must still fire for real purchases.' );
		// Purchase Receipt/Order Completed/ticket emails are dispatched asynchronously via Shepherd
		// in this flow, so no synchronous wp_mail() call is expected here; this only guards against
		// the RSVP confirmation email being incorrectly sent for a non-RSVP order.
		$this->assertCount( 0, $mail->emails, 'The RSVP confirmation email must never be sent for a non-RSVP order.' );
	}
}
