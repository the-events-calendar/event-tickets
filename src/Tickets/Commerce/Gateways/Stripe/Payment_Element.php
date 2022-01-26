<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Module;

/**
 * Class Payment_Element
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Element {

	/**
	 * Include the payment buttons from PayPal into the Checkout page.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 */
	public function include_payment_element( $file, $name, $template ) {
		$must_login = ! is_user_logged_in() && tribe( Module::class )->login_required();

		$template->template( 'gateway/stripe/payment_element', [ 'must_login' => $must_login ] );
	}

}