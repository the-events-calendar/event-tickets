<?php
/**
 * The PayPal webhook route, driven the way PayPal drives it.
 *
 * Handler::process_event() can be tested with a hand-built array, and Webhook_Route_Test can assert the
 * route is registered, and both can pass while the route is incapable of reading a single real
 * delivery. These go through rest_do_request() with a JSON body and PayPal's own headers, which is the
 * only shape that proves the endpoint works end to end.
 *
 * The outbound calls to PayPal are stubbed; nothing here touches the network.
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use Tribe__Utils__Array as Arr;
use WP_Post;
use WP_REST_Request;

class Webhook_Endpoint_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;

	private const PAYPAL_ORDER = '37E53548EJ332603U';
	private const WEBHOOK_ID   = 'WH-TEST-WEBHOOK-ID';

	/**
	 * The headers PayPal signs a delivery with, keyed by the property the endpoint maps each to.
	 *
	 * @var array<string,array{header:string,value:string}>
	 */
	private const SIGNATURE_HEADERS = [
		'transmission_id'   => [
			'header' => 'Paypal-Transmission-Id',
			'value'  => 'b1c1e2f0-0000-11ef-8000-000000000000',
		],
		'transmission_time' => [
			'header' => 'Paypal-Transmission-Time',
			'value'  => '2026-09-16T10:00:00Z',
		],
		'transmission_sig'  => [
			'header' => 'Paypal-Transmission-Sig',
			'value'  => 'dGVzdC1zaWduYXR1cmU=',
		],
		'cert_url'          => [
			'header' => 'Paypal-Cert-Url',
			'value'  => 'https://api.paypal.com/v1/notifications/certs/CERT-TEST',
		],
		'auth_algo'         => [
			'header' => 'Paypal-Auth-Algo',
			'value'  => 'SHA256withRSA',
		],
	];

	/**
	 * Outbound HTTP requests the endpoint made while handling the delivery.
	 *
	 * @var array<int,string>
	 */
	private array $outbound = [];

	/**
	 * Bodies of those requests, keyed by the URL they were sent to.
	 *
	 * @var array<string,mixed>
	 */
	private array $outbound_bodies = [];

	public function setUp(): void {
		parent::setUp();

		$this->outbound        = [];
		$this->outbound_bodies = [];

		tribe( Status_Handler::class )->register_order_statuses();

		// rest_get_server() fires rest_api_init, where the gateway registers its routes.
		rest_get_server();

		add_filter( 'pre_http_request', [ $this, 'block_outbound' ], 10, 3 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', [ $this, 'block_outbound' ], 10 );
		tribe_set_var( Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, [] );

		parent::tearDown();
	}

	/**
	 * Records and refuses any outbound request, so a test can assert none was made.
	 *
	 * @param mixed  $preempt The short-circuit value.
	 * @param array  $args    The request arguments.
	 * @param string $url     The request URL.
	 *
	 * @return array A canned empty response.
	 */
	public function block_outbound( $preempt, $args, $url ) {
		$this->outbound[]              = $url;
		$this->outbound_bodies[ $url ] = Arr::get( $args, 'body', '' );

		return [
			'headers'  => [],
			'body'     => wp_json_encode( [] ),
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * The core of it: a genuine PayPal delivery completes the order.
	 *
	 * PayPal posts application/json. Read with the wrong accessor the body is empty, the event type is
	 * null, and the route answers "invalid type" for every real delivery -- the same outcome for the
	 * buyer as the 404 that serving the route was meant to fix.
	 */
	public function test_json_delivery_completes_the_order(): void {
		$order = $this->make_pending_paypal_order();

		$this->stub_verified_webhook();

		$response = rest_do_request( $this->make_delivery() );

		$this->assertFalse(
			$response->is_error(),
			'A signed PayPal delivery must be accepted. Got: ' . wp_json_encode( $response->get_data() )
		);

		clean_post_cache( $order->ID );

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			'A completed-capture delivery must carry the order to Completed.'
		);
	}

	/**
	 * PayPal redelivers, and retries anything that is not answered with a 2xx. A second delivery of an
	 * event already applied has to read as success, or the order sits settled while PayPal retries the
	 * same event indefinitely.
	 */
	public function test_a_redelivered_event_is_answered_as_success(): void {
		$this->make_pending_paypal_order();
		$this->stub_verified_webhook();

		rest_do_request( $this->make_delivery() );

		$response = rest_do_request( $this->make_delivery() );

		$this->assertSame(
			200,
			$response->get_status(),
			'A redelivery of an already-applied event must be acknowledged, not retried forever.'
		);
	}

	/**
	 * A delivery whose signature does not verify must not disclose the merchant's webhook id, which is
	 * one of the three inputs PayPal's signature binds.
	 */
	public function test_signature_failure_does_not_leak_the_webhook_id(): void {
		$this->make_pending_paypal_order();
		$this->stub_verified_webhook( false );

		$response = rest_do_request( $this->make_delivery() );
		$body     = wp_json_encode( $response->get_data() );

		$this->assertTrue( $response->is_error(), 'An unverified delivery must be refused.' );

		$this->assertStringNotContainsString(
			self::WEBHOOK_ID,
			$body,
			'The response must not disclose the webhook id to an unauthenticated caller.'
		);
	}

	/**
	 * A delivery carrying none of PayPal's headers cannot be verified, so it must be refused before the
	 * endpoint spends an outbound call on it.
	 */
	public function test_headerless_delivery_is_refused_without_calling_paypal(): void {
		$this->make_pending_paypal_order();
		$this->stub_verified_webhook();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/paypal/webhook' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->capture_completed_event() ) );

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error(), 'A delivery with no PayPal headers must be refused.' );

		$this->assertSame(
			[],
			$this->outbound,
			'An unverifiable delivery must not cost an outbound call to PayPal.'
		);
	}

	/**
	 * The five signature headers have to reach PayPal as the strings it sent, because the verify call
	 * carries them in a JSON body and PayPal matches them against the signature byte for byte.
	 *
	 * WP_REST_Request stores every header as a list of values, so reading get_headers() straight out
	 * hands on ['b1c1...'] rather than 'b1c1...'. Encoded that is "transmission_id":["b1c1..."], which
	 * PayPal answers with anything but SUCCESS -- so every genuine delivery is refused. The other tests
	 * here stub verify_webhook_signature, which is exactly the seam this bug lives behind, so this one
	 * goes through the real client and reads the body on the wire.
	 */
	public function test_signature_headers_reach_paypal_as_strings(): void {
		$this->make_pending_paypal_order();

		// Deliberately not stub_verified_webhook(): the real client has to build the verify request.
		$this->set_class_fn_return( Merchant::class, 'is_active', true );
		tribe( Webhooks::class )->update_settings( [ 'id' => self::WEBHOOK_ID ] );

		rest_do_request( $this->make_delivery() );

		$verify_url = '';
		foreach ( $this->outbound as $url ) {
			if ( false !== strpos( $url, 'verify-webhook-signature' ) ) {
				$verify_url = $url;
				break;
			}
		}

		$this->assertNotSame( '', $verify_url, 'The endpoint must ask PayPal to verify the signature.' );

		$sent = json_decode( Arr::get( $this->outbound_bodies, $verify_url, '' ), true );

		$this->assertIsArray( $sent, 'The verify request must carry a JSON body.' );

		foreach ( self::SIGNATURE_HEADERS as $property => $header ) {
			$this->assertSame(
				$header['value'],
				Arr::get( $sent, $property ),
				sprintf( 'PayPal must receive %s as the string it sent.', $property )
			);
		}
	}

	/**
	 * A malformed body must be refused cleanly, without dereferencing keys that are not there.
	 */
	public function test_malformed_body_is_refused_cleanly(): void {
		$this->stub_verified_webhook();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/paypal/webhook' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'nothing' => 'useful' ] ) );

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error(), 'A body with no event type must be refused.' );
	}

	/**
	 * Creates a Pending PayPal order carrying one ticket.
	 */
	private function make_pending_paypal_order(): WP_Post {
		$post   = static::factory()->post->create();
		$ticket = $this->create_tc_ticket( $post, 10 );

		$order = $this->create_order( [ $ticket => 1 ], [ 'order_status' => Pending::SLUG ] );

		update_post_meta( $order->ID, Order::$gateway_meta_key, 'paypal' );
		update_post_meta( $order->ID, Order::$gateway_order_id_meta_key, self::PAYPAL_ORDER );
		clean_post_cache( $order->ID );

		return $order;
	}

	/**
	 * Makes the merchant active, the webhook configured, and the signature check answer as given.
	 *
	 * @param bool $verified What the signature verification answers.
	 */
	private function stub_verified_webhook( bool $verified = true ): void {
		$this->set_class_fn_return( Merchant::class, 'is_active', true );
		$this->set_class_fn_return( Client::class, 'verify_webhook_signature', $verified );

		// Stored rather than stubbed: get_setting() reads the option, so this is the real path.
		tribe( Webhooks::class )->update_settings( [ 'id' => self::WEBHOOK_ID ] );
		$this->set_class_fn_return(
			Client::class,
			'request',
			[
				'id'     => self::PAYPAL_ORDER,
				'status' => 'COMPLETED',
			]
		);
	}

	/**
	 * A delivery shaped the way PayPal sends one: a JSON body plus the five signature headers.
	 */
	private function make_delivery(): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/commerce/paypal/webhook' );

		/*
		 * Built from a PayPal-shaped $_SERVER through the REST server's own reader, rather than by
		 * calling set_header() directly, so the headers reach the endpoint by the route a request off
		 * the wire takes.
		 */
		$server = [ 'CONTENT_TYPE' => 'application/json' ];

		foreach ( self::SIGNATURE_HEADERS as $header ) {
			$server[ 'HTTP_' . strtoupper( str_replace( '-', '_', $header['header'] ) ) ] = $header['value'];
		}

		$request->set_headers( rest_get_server()->get_headers( $server ) );

		$request->set_body( wp_json_encode( $this->capture_completed_event() ) );

		return $request;
	}

	/**
	 * A PAYMENT.CAPTURE.COMPLETED event as Payments v2 sends one: the order id under supplementary
	 * data, and self / refund / up links. Payments v1's `parent_payment` relation is absent, because
	 * no v2 capture carries it.
	 *
	 * @return array{
	 *     id: string,
	 *     event_type: string,
	 *     resource: array{
	 *         id: string,
	 *         status: string,
	 *         amount: array{currency_code: string, value: string},
	 *         supplementary_data: array{related_ids: array{order_id: string}},
	 *         links: array<int, array{rel: string, method: string, href: string}>
	 *     }
	 * }
	 */
	private function capture_completed_event(): array {
		return [
			'id'         => 'WH-TESTEVENT-0001',
			'event_type' => Webhooks\Events::PAYMENT_CAPTURE_COMPLETED,
			'resource'   => [
				'id'                 => '3C679366HH908993F',
				'status'             => 'COMPLETED',
				'amount'             => [
					'currency_code' => 'USD',
					'value'         => '10.00',
				],
				'supplementary_data' => [
					'related_ids' => [
						'order_id' => self::PAYPAL_ORDER,
					],
				],
				'links'              => [
					[
						'rel'    => 'self',
						'method' => 'GET',
						'href'   => 'https://api.paypal.com/v2/payments/captures/3C679366HH908993F',
					],
					[
						'rel'    => 'refund',
						'method' => 'POST',
						'href'   => 'https://api.paypal.com/v2/payments/captures/3C679366HH908993F/refund',
					],
					[
						'rel'    => 'up',
						'method' => 'GET',
						'href'   => 'https://api.paypal.com/v2/checkout/orders/' . self::PAYPAL_ORDER,
					],
				],
			],
		];
	}
}
