<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Flag_Actions\Order_Context;
use TEC\Tickets\Commerce\Flag_Actions\Send_Email_Completed_Order;
use TEC\Tickets\Commerce\Flag_Actions\Send_Email_Purchase_Receipt;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use WP_Post;

/**
 * Asserts which flag actions actually handle orders based on order type (TC-RSVP vs ticket).
 *
 * Uses post meta markers written by Flag_Action_Abstract::mark() after a successful handle.
 */
class Flag_Actions_Order_Type_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Email-related flags on the Completed status.
	 *
	 * @var string[]
	 */
	private const COMPLETED_EMAIL_FLAGS = [
		'send_email',
		'send_email_completed_order',
		'send_email_purchase_receipt',
	];

	/**
	 * Builds a TC-RSVP order and transitions it Pending -> Completed.
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

		return tec_tc_get_order( $order );
	}

	/**
	 * Whether a flag action handled an order (marker meta exists).
	 */
	private function flag_was_handled( int $order_id, string $flag, string $status_slug = Completed::SLUG ): bool {
		/** @var Status_Handler $handler */
		$handler = tribe( Status_Handler::class );
		$status  = $handler->get_by_slug( $status_slug );
		$key     = Order::get_flag_action_marker_meta_key( $flag, $status );

		return metadata_exists( 'post', $order_id, $key );
	}

	public function test_rsvp_order_runs_send_email_but_not_ticket_only_email_flags(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$order = $this->create_tc_rsvp_order( $ticket_id, 'yes' );

		$this->assertTrue(
			$this->flag_was_handled( $order->ID, 'send_email' ),
			'send_email should handle TC-RSVP orders.'
		);
		$this->assertFalse(
			$this->flag_was_handled( $order->ID, 'send_email_purchase_receipt' ),
			'Purchase Receipt must not handle TC-RSVP orders.'
		);
		$this->assertFalse(
			$this->flag_was_handled( $order->ID, 'send_email_completed_order' ),
			'Order Completed email must not handle TC-RSVP orders.'
		);
	}

	public function test_ticket_order_runs_all_completed_email_flag_actions(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$order = $this->create_order( [ $ticket_id => 1 ] );
		$order = tec_tc_get_order( $order );

		foreach ( self::COMPLETED_EMAIL_FLAGS as $flag ) {
			$this->assertTrue(
				$this->flag_was_handled( $order->ID, $flag ),
				sprintf( 'Flag action "%s" should handle ticket orders.', $flag )
			);
		}
	}

	public function test_ticket_only_flag_actions_declare_ticket_order_context(): void {
		/** @var Send_Email_Purchase_Receipt $purchase_receipt */
		$purchase_receipt = tribe( Send_Email_Purchase_Receipt::class );

		/** @var Send_Email_Completed_Order $completed_order */
		$completed_order = tribe( Send_Email_Completed_Order::class );

		$this->assertSame( [ Order_Context::TICKET ], $purchase_receipt->get_order_contexts() );
		$this->assertSame( [ Order_Context::TICKET ], $completed_order->get_order_contexts() );
	}
}
