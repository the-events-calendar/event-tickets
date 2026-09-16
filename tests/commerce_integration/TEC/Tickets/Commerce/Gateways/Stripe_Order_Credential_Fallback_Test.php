<?php
/**
 * Regression tests for the Stripe order-update credential fallback.
 *
 * The order-update route is gated by current_user_can_edit_order(), which depends on the visitor's
 * cart cookie surviving the round trip to Stripe. When that cookie is dropped (edge caches, proxies,
 * www/non-www mismatches) the buyer has already been charged but the route answers 401, the order is
 * stranded in `tec-tc-created`, and no attendee is generated.
 *
 * Stripe endpoints therefore fall back to the Payment Intent client secret, which this site issued for
 * that specific order and stored on it. These tests pin both halves of that boundary: the buyer with
 * the real secret gets through without a cart, and everyone else still does not.
 *
 * @package TEC\Tickets\Commerce\Gateways
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
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tests\Traits\With_Uopz;
use WP_REST_Request;

class Stripe_Order_Credential_Fallback_Test extends WPTestCase {

	use With_Uopz;

	private const BUYER_HASH     = 'buyerhash001';
	private const PAYMENT_INTENT = 'pi_test_buyer';
	private const CLIENT_SECRET  = 'pi_test_buyer_secret_abc123';

	/**
	 * Registers the order post statuses with WordPress, as the plugin does on `init`.
	 *
	 * Without this, WP_Query drops the post_status clause entirely and every status matches, so a
	 * lookup that wrongly relies on the orders repository's default post_status still appears to work.
	 * Registering them makes these tests match production and fail when that reliance creeps back in.
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
	 * Creates a Stripe order carrying a gateway payload, the way handle_create_order() saves the
	 * Payment Intent it just created.
	 *
	 * @param string $gateway_order_id The Payment Intent ID.
	 * @param string $client_secret    The client secret to store on the order, or '' to store none.
	 * @param string $status_slug      The Tickets Commerce status slug to create the order in.
	 *
	 * @return int The created order post ID.
	 */
	private function create_stripe_order(
		string $gateway_order_id,
		string $client_secret,
		string $status_slug = Created::SLUG
	): int {
		$statuses = [
			Created::SLUG   => Created::class,
			Pending::SLUG   => Pending::class,
			Completed::SLUG => Completed::class,
		];

		$status = tribe( $statuses[ $status_slug ] );

		$order_id = wp_insert_post(
			[
				'post_type'   => Order::POSTTYPE,
				'post_status' => $status->get_wp_slug(),
				'post_title'  => 'Stripe test order',
			]
		);

		update_post_meta( $order_id, Order::$gateway_order_id_meta_key, $gateway_order_id );
		update_post_meta( $order_id, Order::$gateway_meta_key, 'stripe' );
		update_post_meta( $order_id, Order::$hash_meta_key, self::BUYER_HASH );

		$payload = [
			'id'     => $gateway_order_id,
			'status' => 'succeeded',
		];

		if ( '' !== $client_secret ) {
			$payload['client_secret'] = $client_secret;
		}

		add_post_meta( $order_id, Order::get_gateway_payload_meta_key( $status ), $payload );

		clean_post_cache( $order_id );

		return $order_id;
	}

	/**
	 * Builds a real gateway endpoint backed by a Pending_Order and the shared Cart.
	 */
	private function make_endpoint( string $class ): Abstract_REST_Endpoint {
		return new $class( new Pending_Order( tribe( Cart::class ), new Logger( 'test' ) ), tribe( Cart::class ) );
	}

	/**
	 * Builds an order-update request.
	 *
	 * @param string|null $order_id      The Payment Intent ID, or null to omit it.
	 * @param string|null $client_secret The client secret, or null to omit it.
	 */
	private function make_request( ?string $order_id, ?string $client_secret = null ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/stripe/order' );

		if ( null !== $order_id ) {
			$request->set_param( 'order_id', $order_id );
		}

		if ( null !== $client_secret ) {
			$request->set_param( 'client_secret', $client_secret );
		}

		return $request;
	}

	/**
	 * Both authorized paths have to work for every in-flight status, not just the one the orders
	 * repository happens to default `post_status` to. Querying without an explicit `status` silently
	 * narrows the lookup to that default, which strands Pending orders while Created ones sail through.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function in_flight_status_provider(): array {
		return [
			'created' => [ Created::SLUG ],
			'pending' => [ Pending::SLUG ],
		];
	}

	/**
	 * The core regression: the buyer has paid, their cart cookie did not survive the round trip, and
	 * they present the client secret this site issued for that Payment Intent. The order must be
	 * finalizable rather than stranded behind a 401.
	 *
	 * @dataProvider in_flight_status_provider
	 */
	public function test_buyer_without_a_cart_cookie_can_finalize_with_the_issued_client_secret( string $status_slug ): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET, $status_slug );

		// The cart cookie never reached this request, so there is no cart hash and no pending order.
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertTrue(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT, self::CLIENT_SECRET ) ),
			'A paid buyer holding the issued client secret must be able to finalize their order without a cart cookie.'
		);
	}

	/**
	 * The cart-bound path still works on its own, with no client secret in the request.
	 *
	 * @dataProvider in_flight_status_provider
	 */
	public function test_cart_bound_path_still_authorizes_without_a_client_secret( string $status_slug ): void {
		$this->activate_cart_hash( self::BUYER_HASH );
		$this->store_pending_order( self::PAYMENT_INTENT );
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET, $status_slug );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertTrue(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT ) ),
			'The cart-bound check must keep authorizing the buyer in an intact session.'
		);
	}

	/**
	 * Knowing the Payment Intent ID is not enough. This is the difference between checking the secret
	 * and merely checking that some in-flight order exists for the id.
	 */
	public function test_payment_intent_id_alone_does_not_authorize(): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT ) ),
			'A Payment Intent ID with no client secret must not authorize the update.'
		);
	}

	/**
	 * A wrong or empty secret is rejected.
	 *
	 * @dataProvider bad_client_secret_provider
	 */
	public function test_wrong_client_secret_does_not_authorize( string $client_secret, string $message ): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT, $client_secret ) ),
			$message
		);
	}

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public function bad_client_secret_provider(): array {
		return [
			'empty'          => [ '', 'An empty client secret must never authorize the update.' ],
			'wrong value'    => [ 'pi_test_buyer_secret_WRONG', 'A client secret that does not match the stored one must be rejected.' ],
			'prefix only'    => [ 'pi_test_buyer_secret_', 'A partial client secret must be rejected.' ],
			'other order id' => [ 'pi_other_order_secret_zzz999', 'Another order\'s client secret must not authorize this one.' ],
		];
	}

	/**
	 * An order with no stored client secret cannot be unlocked by supplying any value, including an
	 * empty one, so a malformed create cannot silently open the route.
	 */
	public function test_order_without_a_stored_secret_cannot_be_unlocked(): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, '' );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT, self::CLIENT_SECRET ) ),
			'An order with no stored client secret must not be editable through the fallback.'
		);
	}

	/**
	 * The fallback only covers orders still in flight. A completed order cannot be re-opened with the
	 * client secret, which stays valid on Stripe's side after the payment succeeds.
	 */
	public function test_completed_order_cannot_be_reopened_with_the_client_secret(): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET, Completed::SLUG );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT, self::CLIENT_SECRET ) ),
			'A completed order must not be editable, even with the correct client secret.'
		);
	}

	/**
	 * A secret stored against one Payment Intent must not authorize an update for another, even when
	 * both payloads live on the same order.
	 */
	public function test_secret_from_a_different_payment_intent_on_the_same_order_is_rejected(): void {
		$order_id = $this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET );

		// An earlier, superseded Payment Intent for the same order.
		add_post_meta(
			$order_id,
			Order::get_gateway_payload_meta_key( tribe( Created::class ) ),
			[
				'id'            => 'pi_test_stale',
				'client_secret' => 'pi_test_stale_secret_xyz',
			]
		);
		clean_post_cache( $order_id );

		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order(
				$this->make_request( self::PAYMENT_INTENT, 'pi_test_stale_secret_xyz' )
			),
			'The secret belonging to a different Payment Intent must not authorize this one.'
		);
	}

	/**
	 * Gateways that issue no order-scoped secret keep the cart-bound check as their only path, so a
	 * stray client_secret param cannot widen them.
	 */
	public function test_paypal_has_no_credential_fallback(): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( PayPal_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( self::PAYMENT_INTENT, self::CLIENT_SECRET ) ),
			'PayPal must not gain a credential fallback it never issues credentials for.'
		);
	}

	/**
	 * A request with no order id is still rejected outright, before any fallback runs.
	 */
	public function test_request_without_order_id_is_still_rejected(): void {
		$this->create_stripe_order( self::PAYMENT_INTENT, self::CLIENT_SECRET );
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( null, self::CLIENT_SECRET ) ),
			'A missing order id must never be treated as authorized.'
		);
	}

	/**
	 * A client secret cannot conjure an order that does not exist.
	 */
	public function test_unknown_payment_intent_is_rejected(): void {
		$this->activate_cart_hash( '' );

		$endpoint = $this->make_endpoint( Stripe_Order_Endpoint::class );

		$this->assertFalse(
			$endpoint->current_user_can_edit_order( $this->make_request( 'pi_does_not_exist', self::CLIENT_SECRET ) ),
			'A Payment Intent with no backing order must not authorize the update.'
		);
	}
}
