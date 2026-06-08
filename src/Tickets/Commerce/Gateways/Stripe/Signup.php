<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Signup;
use TEC\Tickets\Commerce\Gateways\Contracts\OAuth_State;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Signup.
 *
 * @since 5.3.0
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
	 * Generates a Stripe connection URL from WhoDat.
	 *
	 * @since 5.3.0
	 * @since 5.27.4.1 Added state nonce management.
	 * @since TBD Issue a single-use, server-stored state token instead of a uid-0 nonce.
	 *
	 * @return string
	 */
	public function generate_signup_url() {
		$whodat = tribe( WhoDat::class );

		// Single-use, server-side state token verified on the unauthenticated REST callback.
		$nonce = tribe( OAuth_State::class )->issue();

		return $whodat->get_api_url(
			'connect',
			[
				// @todo these have been working fine for 2 years without rawurlencode.
				// Seems like a bad idea to leave them like this. Marking for discussion with reviewers.
				'token'          => $this->get_client_id(),
				'return_url'     => $whodat->get_api_url( 'connected' ),
				'version'        => rawurlencode( Tickets_Plugin::VERSION ),
				'mode'           => rawurlencode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' ),
				// array_keys to expose only webhook ids. in values we have the webhook signing secrets we don't want exposed.
				'known_webhooks' => array_map( 'rawurlencode', array_keys( tribe( Webhooks::class )->get_known_webhooks() ) ),
				'nonce'          => $nonce,
			]
		);
	}

	/**
	 * Generates a Stripe disconnection URL from WhoDat
	 *
	 * @since 5.3.0
	 * @since TBD Issue a single-use, server-stored state token instead of a uid-0 nonce.
	 *
	 * @return string
	 */
	public function generate_disconnect_url() {
		$whodat   = tribe( WhoDat::class );
		$webhooks = tribe( Webhooks::class );

		$known_webhooks = $webhooks->get_current_webhook_id();

		// Single-use, server-side state token verified on the unauthenticated REST callback.
		$nonce = tribe( OAuth_State::class )->issue();

		return $whodat->get_api_url(
			'disconnect',
			[
				'stripe_user_id' => tribe( Merchant::class )->get_client_id(),
				'return_url'     => rest_url( $this->signup_return_path ),
				'version'        => rawurlencode( Tickets_Plugin::VERSION ),
				'mode'           => rawurlencode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' ),
				'known_webhooks' => array_map( 'rawurlencode', $known_webhooks ),
				'nonce'          => $nonce,
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
	 * Get a unique tracking ID to identify this client on Stripe.
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
	 * @deprecated 5.11.0
	 *
	 * @param array $data Data returned from Stripe.
	 *
	 * @return bool
	 */
	public function is_success( $data ) {
		_deprecated_function( __METHOD__, '5.11.0' );

		return ! empty( $data->stripe_user_id )
			&& ! empty( $data->live->access_token )
			&& ! empty( $data->sandbox->access_token );
	}
}
