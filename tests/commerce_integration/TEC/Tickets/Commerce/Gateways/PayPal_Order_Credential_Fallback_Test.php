<?php
/**
 * Regression tests for the PayPal order-update credential fallback.
 *
 * The order-update route is gated by current_user_can_edit_order(), which depends on the visitor's
 * cart cookie surviving the round trip to PayPal. When that cookie is dropped (edge caches, proxies,
 * www/non-www mismatches) the route answers 403 before handle_update_order() runs, so the capture
 * never reaches PayPal, the order is stranded in `tec-tc-pending` and no attendee is generated. The
 * recheck that exists to recover such an order rides the same route, so it is refused too.
 *
 * Stripe answers this with the Payment Intent client secret. PayPal issues no per-order secret of its
 * own, so this site mints one at create time and stores its hash on the order. These tests pin both
 * halves of that boundary: the buyer holding the issued credential gets through without a cart, and
 * everyone else still does not.
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Common\Monolog\Logger;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Order_Credential;
use TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint as PayPal_Order_Endpoint;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tests\Traits\With_Uopz;
use WP_REST_Request;

class PayPal_Order_Credential_Fallback_Test extends WPTestCase {

	use With_Uopz;

	private const BUYER_HASH   = 'buyerhash002';
	private const PAYPAL_ORDER = '37E53548EJ332603U';
	private const OTHER_ORDER  = '9KL22841TY990117B';

	/**
	 * Registers the order post statuses with WordPress, as the plugin does on `init`.
	 *
	 * Without this, WP_Query drops the post_status clause entirely and every status matches, so a
	 * lookup that wrongly relies on the orders repository's default post_status still appears to work.
	 */
	public function setUp(): void {
		parent::setUp();

		tribe( Status_Handler::class )->register_order_statuses();
	}

	/**
	 * @after
	 */
	public function reset_pending_transients(): void {
		delete_transient( sprintf( 'tec_tickets_commerce_pending_order_%s', self::BUYER_HASH ) );
	}

	/**
	 * Both authorized paths have to work for every in-flight status, not just the one the orders
	 * repository happens to default `post_status` to.
	 *
	 * @return Generator<string,array{0:string}>
	 */
	public function in_flight_status_provider(): Generator {
		yield 'created' => [ Created::SLUG ];
		yield 'pending' => [ Pending::SLUG ];
	}

	/**
	 * @return Generator<string,array{0:string,1:string}>
	 */
	public function bad_credential_provider(): Generator {
		yield 'empty' => [ '', 'An empty credential must never authorize the update.' ];
		yield 'wrong value' => [ 'totally-the-wrong-credential', 'A credential that does not match the stored one must be rejected.' ];
		yield 'order id' => [ self::PAYPAL_ORDER, 'The PayPal order id must not double as its own credential.' ];
	}

	/**
	 * The core regression: the buyer approved the payment, their cart cookie did not survive the round
	 * trip, and they present the credential this site issued for that PayPal order. The capture must be
	 * reachable rather than refused behind a 403.
	 *
	 * @dataProvider in_flight_status_provider
	 */
	public function test_buyer_without_a_cart_cookie_can_finalize_with_the_issued_credential( string $status_slug ): void {
		$order_id   = $this->create_paypal_order( self::PAYPAL_ORDER, $status_slug );
		$credential = tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		// The cart cookie never reached this request, so there is no cart hash and no pending order.
		$this->activate_cart_hash( '' );

		$this->assertTrue(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $credential ) ),
			'A buyer holding the issued credential must be able to finalize their order without a cart cookie.'
		);
	}

	/**
	 * The cart-bound path still works on its own, with no credential in the request.
	 *
	 * @dataProvider in_flight_status_provider
	 */
	public function test_cart_bound_path_still_authorizes_without_a_credential( string $status_slug ): void {
		$this->activate_cart_hash( self::BUYER_HASH );
		$this->store_pending_order( self::PAYPAL_ORDER );
		$this->create_paypal_order( self::PAYPAL_ORDER, $status_slug );

		$this->assertTrue(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER ) ),
			'The cart-bound check must keep authorizing the buyer in an intact session.'
		);
	}

	/**
	 * Knowing the PayPal order id is not enough. This is the difference between checking the credential
	 * and merely checking that some in-flight order exists for the id, which is what the id alone would
	 * amount to given it is also the value in the request URL.
	 */
	public function test_paypal_order_id_alone_does_not_authorize(): void {
		$order_id = $this->create_paypal_order( self::PAYPAL_ORDER );
		tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER ) ),
			'A PayPal order id with no credential must not authorize the update.'
		);
	}

	/**
	 * A wrong or empty credential is rejected.
	 *
	 * @dataProvider bad_credential_provider
	 */
	public function test_wrong_credential_does_not_authorize( string $candidate, string $message ): void {
		$order_id = $this->create_paypal_order( self::PAYPAL_ORDER );
		tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $candidate ) ),
			$message
		);
	}

	/**
	 * An order that never had a credential issued cannot be unlocked by supplying any value, so an
	 * interrupted create cannot silently open the route.
	 */
	public function test_order_without_a_stored_credential_cannot_be_unlocked(): void {
		$this->create_paypal_order( self::PAYPAL_ORDER );
		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order(
				$this->make_request( self::PAYPAL_ORDER, 'any-credential-at-all' )
			),
			'An order with no stored credential must not be editable through the fallback.'
		);
	}

	/**
	 * The fallback only covers orders still in flight, so a settled order cannot be driven to another
	 * status with a credential that was issued while it was still open.
	 */
	public function test_completed_order_cannot_be_reopened_with_the_credential(): void {
		$order_id   = $this->create_paypal_order( self::PAYPAL_ORDER, Completed::SLUG );
		$credential = tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $credential ) ),
			'A completed order must not be editable, even with the correct credential.'
		);
	}

	/**
	 * Restarting checkout mints a new PayPal order on the same Tickets Commerce order. The credential
	 * issued for the abandoned PayPal order must not authorize the one that replaced it.
	 */
	public function test_credential_issued_for_a_superseded_paypal_order_is_rejected(): void {
		$order_id = $this->create_paypal_order( self::OTHER_ORDER );
		$stale    = tribe( Order_Credential::class )->issue( $order_id, self::OTHER_ORDER );

		// Checkout restarted: the same order now carries a new PayPal order id and a new credential.
		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, self::PAYPAL_ORDER );
		tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );
		clean_post_cache( $order_id );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $stale ) ),
			'A credential bound to a superseded PayPal order must not authorize its replacement.'
		);
	}

	/**
	 * A credential issued for one order must not authorize another.
	 */
	public function test_credential_from_another_order_is_rejected(): void {
		$other_order_id = $this->create_paypal_order( self::OTHER_ORDER );
		$other          = tribe( Order_Credential::class )->issue( $other_order_id, self::OTHER_ORDER );

		$order_id = $this->create_paypal_order( self::PAYPAL_ORDER );
		tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $other ) ),
			"Another order's credential must not authorize this one."
		);
	}

	/**
	 * A request with no order id is rejected outright, before any fallback runs.
	 */
	public function test_request_without_order_id_is_still_rejected(): void {
		$order_id   = $this->create_paypal_order( self::PAYPAL_ORDER );
		$credential = tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( null, $credential ) ),
			'A missing order id must never be treated as authorized.'
		);
	}

	/**
	 * A credential cannot conjure an order that does not exist.
	 */
	public function test_unknown_paypal_order_is_rejected(): void {
		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order(
				$this->make_request( 'NOSUCHORDER00000', 'any-credential-at-all' )
			),
			'A PayPal order with no backing order must not authorize the update.'
		);
	}

	/**
	 * Each gateway reads only the parameter it issues its own credential under. A valid PayPal
	 * credential sent under Stripe's parameter name must not authorize the request, or the two
	 * gateways' credentials would be interchangeable on each other's routes.
	 */
	public function test_credential_sent_under_another_gateways_param_does_not_authorize(): void {
		$order_id   = $this->create_paypal_order( self::PAYPAL_ORDER );
		$credential = tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		$this->activate_cart_hash( '' );

		// Everything here is valid except the parameter the credential travels in.
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order' );
		$request->set_param( 'order_id', self::PAYPAL_ORDER );
		$request->set_param( 'client_secret', $credential );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $request ),
			"PayPal must read only its own credential parameter, never Stripe's."
		);
	}

	/**
	 * An order belonging to another gateway must not be reachable through the PayPal fallback, even
	 * when the credential matches, so a PayPal request cannot drive a Stripe order.
	 */
	public function test_order_from_another_gateway_is_rejected(): void {
		$order_id   = $this->create_paypal_order( self::PAYPAL_ORDER );
		$credential = tribe( Order_Credential::class )->issue( $order_id, self::PAYPAL_ORDER );

		update_post_meta( $order_id, Order::$gateway_meta_key, 'stripe' );
		clean_post_cache( $order_id );

		$this->activate_cart_hash( '' );

		$this->assertFalse(
			$this->make_endpoint()->current_user_can_edit_order( $this->make_request( self::PAYPAL_ORDER, $credential ) ),
			"An order owned by another gateway must not be editable through PayPal's fallback."
		);
	}

	/**
	 * Makes get_cart_hash() resolve to the given hash for the current process, modelling an active
	 * browser session. An empty hash models the cart cookie never reaching the REST request.
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
	 * Creates a PayPal order the way handle_create_order() leaves one behind.
	 *
	 * @param string $gateway_order_id The PayPal order id.
	 * @param string $status_slug      The Tickets Commerce status slug to create the order in.
	 *
	 * @return int The created order post ID.
	 */
	private function create_paypal_order( string $gateway_order_id, string $status_slug = Pending::SLUG ): int {
		$statuses = [
			Created::SLUG   => Created::class,
			Pending::SLUG   => Pending::class,
			Completed::SLUG => Completed::class,
		];

		$order_id = wp_insert_post(
			[
				'post_type'   => Order::POSTTYPE,
				'post_status' => tribe( $statuses[ $status_slug ] )->get_wp_slug(),
				'post_title'  => 'PayPal test order',
			]
		);

		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $gateway_order_id );
		update_post_meta( $order_id, Order::$gateway_meta_key, 'paypal' );
		update_post_meta( $order_id, Order::$hash_meta_key, self::BUYER_HASH );

		clean_post_cache( $order_id );

		return $order_id;
	}

	/**
	 * Builds a real PayPal endpoint backed by a Pending_Order and the shared Cart.
	 */
	private function make_endpoint(): Abstract_REST_Endpoint {
		return new PayPal_Order_Endpoint( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ), tribe( Cart::class ) );
	}

	/**
	 * Builds an order-update request.
	 *
	 * @param string|null $order_id   The PayPal order id, or null to omit it.
	 * @param string|null $credential The credential, or null to omit it.
	 */
	private function make_request( ?string $order_id, ?string $credential = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/paypal/order' );

		if ( null !== $order_id ) {
			$request->set_param( 'order_id', $order_id );
		}

		if ( null !== $credential ) {
			$request->set_param( Order_Credential::REQUEST_PARAM, $credential );
		}

		return $request;
	}
}
