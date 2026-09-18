<?php
/**
 * The happy path through the PayPal webhook: a completed capture finishes the order.
 *
 * This is the whole point of serving the webhook route. When the buyer's browser never comes back,
 * PAYMENT.CAPTURE.COMPLETED is the only thing that tells the site the money was taken, and it has to
 * carry the order all the way to Completed so attendees are generated and the ticket email goes out.
 * Asserting that the route is registered says nothing about whether the event is understood.
 *
 * The one outbound PayPal call is stubbed; nothing here touches the network.
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Handler;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use WP_Post;

class Webhook_Handler_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;

	private const PAYPAL_ORDER = '37E53548EJ332603U';

	/**
	 * The arguments the handler passed to Client::request(), in call order.
	 *
	 * @var array<int,array{method: string, url: string|null}>
	 */
	private array $requests = [];

	public function setUp(): void {
		parent::setUp();

		$this->requests = [];

		tribe( Status_Handler::class )->register_order_statuses();
	}

	/**
	 * Order_Maker writes tribe options, memoized in a var the per-test DB rollback does not touch.
	 */
	public function tearDown(): void {
		tribe_set_var( Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, [] );

		parent::tearDown();
	}

	/**
	 * The happy path: PayPal reports the capture completed, and the pending order it belongs to is
	 * carried to Completed through the status transition.
	 *
	 * The transition is asserted rather than the attendee rows, because generating attendees hangs off
	 * this action in production and Order_Maker leaves an order's items empty here, so no order in this
	 * suite has attendees to count -- including one the maker creates Completed itself. Pinning the
	 * transition proves the handler drove modify_status(), which is what generates the attendee and
	 * sends the ticket email on a real site, rather than writing post_status behind its back.
	 */
	public function test_completed_capture_event_completes_the_order(): void {
		$order = $this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		$transitions = [];
		add_action(
			'tec_tickets_commerce_order_status_transition',
			static function ( $new, $old, $post ) use ( &$transitions ) {
				$transitions[] = [ $new::SLUG, $old::SLUG, $post->ID ];
			},
			10,
			3
		);

		$result = tribe( Handler::class )->process_event( $this->capture_completed_event() );

		$this->assertNotWPError(
			$result,
			'A well-formed completed-capture event must be processed, not refused.'
		);

		clean_post_cache( $order->ID );

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			'A completed capture must carry the order to Completed.'
		);

		$this->assertContains(
			[ Completed::SLUG, Pending::SLUG, $order->ID ],
			$transitions,
			'The order must reach Completed through the status transition, which is what generates the attendee and sends the ticket email.'
		);

		$this->assertSame(
			[],
			$this->requests,
			'A capture carrying the order id needs no round trip to PayPal to resolve it.'
		);
	}

	/**
	 * Older or partial payloads may omit supplementary data. Then the order is resolved by following the
	 * link back to it -- which must be the `up` relation a v2 capture carries, not the `parent_payment`
	 * relation that belongs to Payments v1 and appears on no v2 event.
	 */
	public function test_order_is_resolved_from_the_order_link_when_supplementary_data_is_absent(): void {
		$order = $this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		$event = $this->capture_completed_event();
		unset( $event['resource']['supplementary_data'] );

		$this->assertNotWPError(
			tribe( Handler::class )->process_event( $event ),
			'An event with only links must still resolve its order.'
		);

		clean_post_cache( $order->ID );

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			'Reading the order link must carry the order to Completed.'
		);

		$this->assertSame(
			[],
			$this->requests,
			'The order id is in the link itself, so resolving it costs no request.'
		);
	}

	/**
	 * PayPal serves the same API under two hostnames, and its v2 webhook links use `api-m` while the
	 * rest of this gateway talks to `api`. Both have to resolve, or the link path is dead in production.
	 *
	 * @dataProvider api_host_provider
	 */
	public function test_order_links_resolve_on_either_paypal_api_host( string $host ): void {
		$order = $this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		$event = $this->capture_completed_event();
		unset( $event['resource']['supplementary_data'] );
		$event['resource']['links'] = [
			[
				'rel'    => 'up',
				'method' => 'GET',
				'href'   => 'https://' . $host . '/v2/checkout/orders/' . self::PAYPAL_ORDER,
			],
		];

		$this->assertNotWPError(
			tribe( Handler::class )->process_event( $event ),
			"An order link on {$host} must resolve."
		);

		clean_post_cache( $order->ID );

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			"An order link on {$host} must carry the order to Completed."
		);
	}

	/**
	 * @return Generator<string,array{0:string}>
	 */
	public function api_host_provider(): Generator {
		yield 'api' => [ 'api.paypal.com' ];
		yield 'api-m' => [ 'api-m.paypal.com' ];
	}

	/**
	 * A capture's links also address the capture, the refund and, on an authorized payment, the
	 * authorization. None of those ids is an order id, so none may be read as one.
	 *
	 * @dataProvider non_order_link_provider
	 */
	public function test_links_that_do_not_address_an_order_are_not_read( string $href, string $message ): void {
		$this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		$event = $this->capture_completed_event();
		unset( $event['resource']['supplementary_data'] );
		$event['resource']['links'] = [
			[
				'rel'    => 'up',
				'method' => 'GET',
				'href'   => $href,
			],
		];

		$this->assertWPError( tribe( Handler::class )->process_event( $event ), $message );
	}

	/**
	 * @return Generator<string,array{0:string,1:string}>
	 */
	public function non_order_link_provider(): Generator {
		yield 'a capture' => [
			'https://api.paypal.com/v2/payments/captures/3C679366HH908993F',
			'A capture id must not be read as an order id.',
		];

		yield 'an authorization' => [
			'https://api.paypal.com/v2/payments/authorizations/0VF52814937998046',
			'An authorization id must not be read as an order id.',
		];

		yield 'a refund' => [
			'https://api.paypal.com/v2/payments/refunds/1JU08902781691411',
			'A refund id must not be read as an order id.',
		];
	}

	/**
	 * A link pointing somewhere other than PayPal must not be followed: the request carries this
	 * merchant's access token, and a webhook body is not a trusted source of hostnames.
	 */
	public function test_a_link_pointing_off_paypal_is_not_followed(): void {
		$this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		$event = $this->capture_completed_event();
		unset( $event['resource']['supplementary_data'] );
		$event['resource']['links'] = [
			[
				'rel'    => 'up',
				'method' => 'GET',
				'href'   => 'https://attacker.example/v2/checkout/orders/' . self::PAYPAL_ORDER,
			],
		];

		$this->assertWPError(
			tribe( Handler::class )->process_event( $event ),
			'An order link pointing off PayPal must not resolve.'
		);

		$this->assertSame(
			[],
			$this->requests,
			'A host the event named must never be contacted.'
		);
	}

	/**
	 * An event for a PayPal payment this site has no order for is refused rather than acted on.
	 */
	public function test_event_for_an_unknown_payment_is_refused(): void {
		$this->stub_parent_payment_lookup( 'NOSUCHPAYPALORDER' );

		$this->assertWPError(
			tribe( Handler::class )->process_event( $this->capture_completed_event() ),
			'An event whose payment matches no order must be refused.'
		);
	}

	/**
	 * Replaying the same event must not be treated as a second payment.
	 */
	public function test_replaying_the_event_does_not_re_complete_the_order(): void {
		$order = $this->make_pending_paypal_order();

		$this->stub_parent_payment_lookup();

		tribe( Handler::class )->process_event( $this->capture_completed_event() );
		clean_post_cache( $order->ID );

		$this->assertWPError(
			tribe( Handler::class )->process_event( $this->capture_completed_event() ),
			'An order already on the status the event reports must be left alone.'
		);

		$this->assertSame(
			tribe( Completed::class )->get_wp_slug(),
			get_post_status( $order->ID ),
			'The replay must leave the order where the first event put it.'
		);
	}

	/**
	 * Creates a Pending PayPal order carrying one ticket, the way checkout leaves one behind when the
	 * buyer has approved the payment but the browser never returned.
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
	 * Stubs the follow-up call the handler makes to read the capture's parent payment.
	 *
	 * @param string $paypal_order_id The PayPal order id the lookup resolves to.
	 */
	private function stub_parent_payment_lookup( string $paypal_order_id = self::PAYPAL_ORDER ): void {
		// By reference, not $this: uopz runs the replacement in the stubbed class's scope.
		$requests = &$this->requests;

		$this->set_class_fn_return(
			Client::class,
			'request',
			static function ( $method, $url ) use ( $paypal_order_id, &$requests ) {
				$requests[] = [
					'method' => $method,
					'url'    => $url,
				];

				return [
					'id'     => $paypal_order_id,
					'status' => 'COMPLETED',
				];
			},
			true
		);
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
						'href'   => 'https://api-m.paypal.com/v2/checkout/orders/' . self::PAYPAL_ORDER,
					],
				],
			],
		];
	}
}
