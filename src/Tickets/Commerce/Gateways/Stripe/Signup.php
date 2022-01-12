<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Signup;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\On_Boarding_Endpoint;

class Signup extends Abstract_Signup {

	/**
	 * @inheritDoc
	 */
	public static $signup_data_meta_key = 'tec_tc_stripe_signup_data';

	/**
	 * The return path the user will be redirected to after signing up
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $signup_return_path = '/edit.php?post_type=tribe_events&page=tribe-common&tab=payments';

	/**
	 * @inheritDoc
	 */
	public $template_folder = 'src/admin-views/settings/tickets-commerce/stripe';

	/**
	 * Generates a stripe connection URL from WhoDat
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function generate_url() {

		return tribe( WhoDat::class )->get_api_url(
			'connect',
			[
				'token'      => $this->get_client_id(),
				'return_url' => tribe( WhoDat::class )->get_api_url( 'connected' ),
			] );

	}

	/**
	 * @inheritDoc
	 */
	public function get_link_html() {
		$template_vars = [
			'url' => $this->generate_url(),
		];

		$this->get_template()->template( 'signup-link', $template_vars );
	}

	/**
	 * Get a unique tracking ID to identify this client on stripe
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_client_id() {
		return tribe( Gateway::class )->generate_unique_tracking_id();
	}

	/**
	 * Handle returning requests for established stripe connections
	 *
	 * @since TBD
	 */
	public function handle_connection_established() {

		$data = json_decode( base64_decode( $_GET['stripe'] ) );
		$tracking_id = tribe( Gateway::class )->generate_unique_tracking_id();
		$whodat_identifier_hash = explode( 'v=', $tracking_id );

		if ( $data->whodat !== md5( $whodat_identifier_hash[0] ) ) {
			return;
		}

		tribe( Merchant::class )->save_signup_data( (array) $data );

		wp_safe_redirect( admin_url( $this->signup_return_path ) );
		exit();
	}
}