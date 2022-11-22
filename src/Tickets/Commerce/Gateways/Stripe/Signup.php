<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Signup;

/**
 * Class Signup.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Signup extends Abstract_Signup {

	/**
	 * @inheritDoc
	 */
	public static $signup_data_meta_key = 'tec_tc_stripe_signup_data';

	/**
	 * The return path the user will be redirected to after signing up or disconnecting.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public $signup_return_path = '/tribe/tickets/v1/commerce/stripe/return';

	/**
	 * @inheritDoc
	 */
	public $template_folder = 'src/admin-views/settings/tickets-commerce/stripe';

	/**
	 * Generates a stripe connection URL from WhoDat.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function generate_signup_url() {

		return tribe( WhoDat::class )->get_api_url(
			'connect',
			[
				'token'      => $this->get_client_id(),
				'return_url' => tribe( WhoDat::class )->get_api_url( 'connected' ),
			] );

	}

	/**
	 * Generates a stripe disconnection URL from WhoDat
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function generate_disconnect_url() {

		return tribe( WhoDat::class )->get_api_url(
			'disconnect',
			[
				'stripe_user_id' => tribe( Merchant::class )->get_client_id(),
				'return_url'     => rest_url( $this->signup_return_path ),
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_link_html() {
		$template_vars = [
			'url' => $this->generate_signup_url(),
		];

		$this->get_template()->template( 'signup-link', $template_vars );
	}

	/**
	 * Get a unique tracking ID to identify this client on stripe.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_client_id() {
		return tribe( Gateway::class )->generate_unique_tracking_id();
	}

	/**
	 * Determines if the signup was successful.
	 *
	 * @since 5.3.0
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function is_success( $data ) {

		return ! empty( $data->stripe_user_id )
			   && ! empty( $data->live->access_token )
			   && ! empty( $data->sandbox->access_token );
	}
}