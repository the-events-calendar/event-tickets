<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Class Connect_Client
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Connect_Client {

	/**
	 * The API URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 *
	 * @todo Replace with final API URL.
	 */
	//public $api_url = 'https://tickets.theeventscalendar.com/connect';
	public $api_url = 'http://test.tec.local/wp-content/plugins/event-tickets/src/Tribe/Commerce/Tickets_Commerce/Gateways/PayPal/Service';

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint The endpoint path.
	 *
	 * @return string The API URL.
	 */
	public function get_api_url( $endpoint ) {
		return "{$this->api_url}/{$endpoint}";
	}
}
