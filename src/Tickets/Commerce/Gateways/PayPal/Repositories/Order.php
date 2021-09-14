<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\Client;

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
	 * @param string|int $order_id Which Order Post Type ID we are going to create a PayPal Order from.
	 *
	 * @return string
	 */
	public function approve( $order_id ) {

		// @todo @rafsuntaskin @gustavo update sinature with $order_data array.
		$response = $this->client->capture_order( $order_id, [] );

		return $response;
	}

	/**
	 * Create order based on Event Ticket Order ID we send the data to Paypal to create the order there.
	 *
	 * @since 5.1.9
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

	 * @param string|int $order_id Which Order Post Type ID we are going to create a PayPal Order from.
	 *
	 * @return string The id of the refund
	 */
	public function refund_payment( $order_id ) {
		$response = $this->client->refund_payment( $order_id );

		return $response;
	}
}
