<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Signup;

class Signup extends Abstract_Signup {

	/**
	 * @inheritDoc
	 */
	public static $signup_data_meta_key = 'tec_tc_stripe_signup_data';

	public $signup_url_base = 'https://connect.stripe.com/oauth/authorize';

	/**
	 * @inheritDoc
	 */
	public $template_folder = 'src/admin-views/settings/tickets-commerce/stripe';

	public function get_link_html() {
		$url = add_query_arg( [
			'response_type' => 'code',
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_redirect_uri(),
			'scope'         => 'read_write',
		],
			$this->signup_url_base
		);

		$template_vars = [
			'url' => $url,
		];

		$this->get_template()->template( 'signup-link', $template_vars );
	}

	public function get_client_id() {
		return 1;
	}

	public function get_redirect_uri() {
		return 'signup-link';
	}

}