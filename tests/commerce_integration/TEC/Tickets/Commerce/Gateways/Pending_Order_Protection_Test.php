<?php
/**
 * Integration tests for the pending-order protection that guards the gateway
 * "update order" / "fail order" REST endpoints.
 *
 * The protection lives in Abstract_REST_Endpoint::current_user_can_edit_order() and is wired as the
 * `permission_callback` of the capture/complete and fail routes for the PayPal and Stripe gateways.
 * It authorizes a request only when ALL of the following hold:
 *   - a truthy order_id is provided;
 *   - it equals the gateway order id stored as the pending order for the current cart hash;
 *   - a Created/Pending order exists whose stored hash equals the current cart hash.
 *
 * These tests prove the boundary holds for the well-behaved cases. The residual bypass, where an
 * attacker fixates the victim onto a known cart hash so all three conditions line up, is proven
 * separately in Pending_Order_Session_Fixation_Test.
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Monolog\Logger;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint as PayPal_Order_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint as Stripe_Order_Endpoint;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Uopz;
use WP_REST_Request;

class Pending_Order_Protection_Test extends WPTestCase {

	use With_Uopz;

	private const VICTIM_HASH     = 'victimhash01';
	private const ATTACKER_HASH   = 'attackrhash2';
	private const VICTIM_ORDER    = 'ORDER-VICTIM';
	private const ATTACKER_ORDER  = 'ORDER-ATTACKER';

	/**
	 * @after
	 */
	public function reset_pending_transients(): void {
		foreach ( [ self::VICTIM_HASH, self::ATTACKER_HASH ] as $hash ) {
			delete_transient( sprintf( 'tec_tickets_commerce_pending_order_%s', $hash ) );
		}
	}

	/**
	 * The gateway endpoints that gate editing with current_user_can_edit_order().
	 *
	 * @return array<string,array{0:string}>
	 */
	public function gateway_endpoint_provider(): array {
		return [
			'paypal' => [ PayPal_Order_Endpoint::class ],
			'stripe' => [ Stripe_Order_Endpoint::class ],
		];
	}

	/**
	 * Makes get_cart_hash() resolve to the given hash for the current process (no cart-item hydration),
	 * by setting the shared cart repository's hash directly. Model an active browser session.
	 */
	private function activate_cart_hash( string $hash ): void {
		$this->set_class_property( tribe( Cart::class )->get_repository(), 'cart_hash', $hash );
	}

	/**
	 * Stores a gateway order id as the pending order for the currently active cart hash, mirroring what
	 * the create-order handler does.
	 */
	private function store_pending_order( string $gateway_order_id ): void {
		( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ) )->set( $gateway_order_id );
	}

	/**
	 * Creates a minimal pending Tickets Commerce order carrying the given gateway order id and cart hash.
	 */
	private function create_pending_order( string $gateway_order_id, string $hash ): void {
		$order_id = wp_insert_post(
			[
				'post_type'   => Order::POSTTYPE,
				'post_status' => tribe( Pending::class )->get_wp_slug(),
				'post_title'  => 'Test pending order',
			]
		);

		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $gateway_order_id );
		update_post_meta( $order_id, Order::$hash_meta_key, $hash );
	}

	/**
	 * Builds a real gateway endpoint backed by a Pending_Order and the shared Cart.
	 */
	private function make_endpoint( string $class ): Abstract_REST_Endpoint {
		return new $class( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ), tribe( Cart::class ) );
	}

	/**
	 * Builds a request targeting a given gateway order id.
	 */
	private function make_request( ?string $order_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/order' );

		if ( null !== $order_id ) {
			$request->set_param( 'order_id', $order_id );
		}

		return $request;
	}

	/**
	 * The legitimate buyer who created the order can edit it within their own session.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_legitimate_buyer_can_edit_their_pending_order( string $endpoint_class ): void {
		$this->activate_cart_hash( self::VICTIM_HASH );
		$this->store_pending_order( self::VICTIM_ORDER );
		$this->create_pending_order( self::VICTIM_ORDER, self::VICTIM_HASH );

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertTrue(
			$endpoint->current_user_can_edit_order( $this->make_request( self::VICTIM_ORDER ) ),
			'The buyer who created the pending order should be allowed to edit it.'
		);
	}

	/**
	 * An attacker who knows the victim's order id but has no cart session cannot edit it.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_attacker_without_a_cart_session_cannot_edit_order( string $endpoint_class ): void {
		$this->activate_cart_hash( self::VICTIM_HASH );
		$this->store_pending_order( self::VICTIM_ORDER );
		$this->create_pending_order( self::VICTIM_ORDER, self::VICTIM_HASH );

		// The attacker crafts a raw request with no cart session at all.
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::VICTIM_ORDER ) ),
			'Knowing the gateway order id must not be enough to edit it without the cart session.'
		);
	}

	/**
	 * An attacker in a DIFFERENT cart session cannot edit the victim's order, even with their own order.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_attacker_in_another_session_cannot_edit_victims_order( string $endpoint_class ): void {
		// The victim's order, created under the victim's hash.
		$this->create_pending_order( self::VICTIM_ORDER, self::VICTIM_HASH );

		// The attacker has their own separate cart, pending order, and order.
		$this->activate_cart_hash( self::ATTACKER_HASH );
		$this->store_pending_order( self::ATTACKER_ORDER );
		$this->create_pending_order( self::ATTACKER_ORDER, self::ATTACKER_HASH );

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::VICTIM_ORDER ) ),
			'A separate cart session must not be able to edit another session\'s order.'
		);

		$this->assertTrue(
			$endpoint->current_user_can_edit_order( $this->make_request( self::ATTACKER_ORDER ) ),
			'Sanity check: the attacker can still edit their own order within their own session.'
		);
	}

	/**
	 * Even the legitimate buyer can only edit the exact order id tied to their session.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_buyer_cannot_edit_a_different_order_than_their_pending_one( string $endpoint_class ): void {
		$this->activate_cart_hash( self::VICTIM_HASH );
		$this->store_pending_order( self::VICTIM_ORDER );
		$this->create_pending_order( self::VICTIM_ORDER, self::VICTIM_HASH );

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( 'SOME-OTHER-ORDER' ) ),
			'A session may only edit the exact gateway order id stored as its pending order.'
		);
	}

	/**
	 * A request without an order id is rejected outright.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_request_without_order_id_is_rejected( string $endpoint_class ): void {
		$this->activate_cart_hash( self::VICTIM_HASH );
		$this->store_pending_order( self::VICTIM_ORDER );
		$this->create_pending_order( self::VICTIM_ORDER, self::VICTIM_HASH );

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( null ) ),
			'A missing order id must never be treated as authorized.'
		);
	}

	/**
	 * Defense-in-depth: a matching pending-order transient is NOT sufficient on its own; without a
	 * matching Created/Pending order row the request is still rejected.
	 *
	 * @dataProvider gateway_endpoint_provider
	 */
	public function test_pending_transient_without_matching_order_is_rejected( string $endpoint_class ): void {
		$this->activate_cart_hash( self::VICTIM_HASH );
		$this->store_pending_order( self::VICTIM_ORDER ); // transient only, no order row created.

		$endpoint = $this->make_endpoint( $endpoint_class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::VICTIM_ORDER ) ),
			'A pending-order transient with no backing order row must not authorize editing.'
		);
	}
}
