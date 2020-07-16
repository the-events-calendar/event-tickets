<?php

namespace Tribe\Tickets\Promoter\Triggers;

use Firebase\JWT\JWT;
use RuntimeException;
use Tribe\Tickets\Promoter\Triggers\Contracts\Triggered;
use Tribe__Promoter__Connector;
use Tribe__Promoter__PUE;

class Dispatcher {
	/**
	 * @var mixed
	 */
	private $secret;
	/**
	 * @var string
	 */
	private $license_key;
	/**
	 * @var Tribe__Promoter__Connector
	 */
	private $connector;
	/**
	 * @var Tribe__Promoter__PUE
	 */
	private $pue;

	public function __construct( Tribe__Promoter__Connector $connector, Tribe__Promoter__PUE $pue ) {
		$this->connector   = $connector;
		$this->pue         = $pue;
		$this->license_key = $this->get_license_key();
		$this->secret      = $this->connector->get_secret_key();
	}

	/**
	 * Dispatch triggers as soon as a new action has been fired.
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'tribe_tickets_promoter_trigger', [ $this, 'trigger' ] );
	}

	/**
	 * Send trigger message back to connector application to notify promoter about this trigger action.
	 *
	 * @since TBD
	 *
	 * @param Triggered $trigger
	 */
	public function trigger( $trigger ) {
		try {
			$trigger->build();

			if ( empty( $this->license_key ) ) {
				throw new RuntimeException( 'License Key must be present.' );
			}

			if ( empty( $this->secret ) ) {
				throw new RuntimeException( 'Secret key must be present.' );
			}

			if ( $trigger->post()->post_type !== 'tribe_events' ) {
				throw new RuntimeException( "The type: '{$trigger->post()->post_type}' is not supported." );
			}

			$args = [
				'body'      => [
					'token' => JWT::encode( $this->get_payload( $trigger ), $this->secret ),
				],
				'sslverify' => false,
				'timeout'   => 30,
			];

			$this->connector->make_call( $this->connector->base_url() . 'connect/trigger', $args );
		} catch ( RuntimeException $exception ) {
			$log_data = [
				'type'  => $trigger->type(),
				'error' => $exception->getMessage(),
			];
			do_action( 'tribe_log', 'debug', __METHOD__, $log_data );
		}
	}

	/**
	 * Creat ea payload using the trigger object.
	 *
	 * @since TBD
	 *
	 * @param Triggered $trigger The trigger object creating this action.
	 *
	 * @return array The payload to be encoded and delivered to the connector app.
	 */
	private function get_payload( $trigger ) {
		return [
			'license'  => $this->license_key,
			'type'     => $trigger->type(),
			'event'    => [
				'id' => $trigger->post()->ID,
			],
			'ticket'   => [
				'id'   => $trigger->ticket()->ID,
				'name' => $trigger->ticket()->name,
			],
			'attendee' => [
				'id'    => $trigger->attendee()->id(),
				'email' => $trigger->attendee()->email(),
			],
		];
	}

	/**
	 * Get the value of the license key of promoter for this installation.
	 *
	 * @since TBD
	 *
	 * @return mixed|string
	 */
	private function get_license_key() {
		$license_info = $this->pue->get_license_info();

		if ( empty( $license_info ) || empty( $license_info['key'] ) ) {
			return '';
		}

		return $license_info['key'];
	}
}