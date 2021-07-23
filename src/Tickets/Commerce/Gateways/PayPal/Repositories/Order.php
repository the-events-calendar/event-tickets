<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use Exception;
use InvalidArgumentException;

// @todo Implement PayPal Checkout SDK.
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;

/**
 * Class Order
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Repositories
 *
 */
class Order {

	/**
	 * @since 5.1.6
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Order constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Client $client
	 */
	public function __construct( Client $client = null ) {
		$this->client = $client ?: tribe( Client::class );
	}

	/**
	 * Approve order.
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param string $order_id
	 *
	 * @return string
	 */
	public function approve( $order_id ) {
		$request = new OrdersCaptureRequest( $order_id );

		try {
			return $this->client->get_http_client()->execute( $request )->result;
		} catch ( Exception $ex ) {
			// @todo Log the error.
			logError( 'Capture PayPal Commerce payment failure', sprintf( '<strong>Response</strong><pre>%1$s</pre>', print_r( json_decode( $ex->getMessage(), true ), true ) ) );

			throw $ex;
		}
	}

	/**
	 * Create order based on Event Ticket Order ID we send the data to Paypal to create the order there.
	 *
	 * @since TBD
	 *
	 * @param string|int $order_id Which Order Post Type ID we are going to create a PayPal Order from.
	 *
	 * @return array
	 */
	public function create( $order_id ) {

		$data = [

		];

		$order_response = $this->client->create_order( $data );

		return $order_response['id'];
	}

	/**
	 * Refunds a processed payment
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param $capture_id
	 *
	 * @return string The id of the refund
	 */
	public function refund_payment( $capture_id ) {
		$refund = new CapturesRefundRequest( $capture_id );

		try {
			return $this->client->get_http_client()->execute( $refund )->result->id;
		} catch ( Exception $exception ) {
			logError( 'Create PayPal Commerce payment refund failure', sprintf( '<strong>Response</strong><pre>%1$s</pre>', print_r( json_decode( $exception->getMessage(), true ), true ) ) );

			throw $exception;
		}
	}
}
