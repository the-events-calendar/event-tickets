<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models;

class WebhookConfig {

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
	public $returnUrl;

	/**
	 * @since 5.1.6
	 *
	 * @var string[]
	 */
	public $events;

	/**
	 * WebhookConfig constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param string   $id
	 * @param string   $returnUrl
	 * @param string[] $events
	 */
	public function __construct( $id, $returnUrl, $events ) {
		$this->id        = $id;
		$this->returnUrl = $returnUrl;
		$this->events    = $events;
	}

	/**
	 * Generates an instance from serialized data
	 *
	 * @since 5.1.6
	 *
	 * @param array $data
	 *
	 * @return WebhookConfig
	 */
	public static function fromArray( array $data ) {
		return new self( $data['id'], $data['returnUrl'], $data['events'] );
	}

	/**
	 * Generates an array for serialization
	 *
	 * @since 5.1.6
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'id'        => $this->id,
			'returnUrl' => $this->returnUrl,
			'events'    => $this->events,
		];
	}
}
