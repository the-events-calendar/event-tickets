<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Return_Endpoint;
use \Tribe__Tickets__Main;
use Tribe__Utils__Array as Arr;

/**
 * Class Gateway
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Gateway extends Abstract_Gateway {

	/**
	 * @inheritDoc
	 */
	protected static $key = 'stripe';

	/**
	 * @inheritDoc
	 */
	protected static $settings = Settings::class;

	/**
	 * @inheritDoc
	 */
	protected static $merchant = Merchant::class;

	/**
	 * Stripe tracking ID version.
	 *
	 * This shouldn't be updated unless we are modifying something on the Stripe user level.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'Stripe', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		$notices = [
			[
				'slug'    => 'tc-stripe-signup-error',
				'content' => __( "Stripe wasn't able to complete your connection request. Try again.", 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-stripe-token-error',
				'content' => __( 'Stripe signup was successful but the authentication tokens could not be retrieved. Try refreshing the tokens. Try again.', 'event-tickets' ),
				'type'    => 'error',
			],
			[
				'slug'    => 'tc-stripe-disconnect-error',
				'content' => __( 'Disconnecting from Stripe failed. Please try again.', 'event-tickets' ),
				'type'    => 'error',
			],
		];

		return $notices;
	}

	/**
	 * @inheritDoc
	 */
	public function get_logo_url() {
		return Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/stripe-logo.png';
	}

	/**
	 * @inheritDoc
	 */
	public function get_subtitle() {
		return __( 'Enable credit card payment and afterpay', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function generate_unique_tracking_id() {
		$return_url = tribe( Return_Endpoint::class )->get_route_url();

		return $return_url;
	}
}
