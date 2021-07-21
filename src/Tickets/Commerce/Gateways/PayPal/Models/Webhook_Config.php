<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Models;

/**
 * Class Webhook_Config.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Models
 */
class Webhook_Config {

	/**
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $id;

	/**
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $return_url;

	/**
	 * @since 5.1.6
	 *
	 * @var string[]
	 */
	public $events;

	/**
	 * Webhook_Config constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param string   $id
	 * @param string   $return_url
	 * @param string[] $events
	 */
	public function __construct( $id, $return_url, $events ) {
		$this->id         = $id;
		$this->return_url = $return_url;
		$this->events     = $events;
	}

	/**
	 * Generates an instance from serialized data
	 *
	 * @since 5.1.6
	 *
	 * @param array $data
	 *
	 * @return Webhook_Config
	 */
	public static function from_array( array $data ) {
		return new self( $data['id'], $data['return_url'], $data['events'] );
	}

	/**
	 * Generates an array for serialization
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'id'         => $this->id,
			'return_url' => $this->return_url,
			'events'     => $this->events,
		];
	}
}
