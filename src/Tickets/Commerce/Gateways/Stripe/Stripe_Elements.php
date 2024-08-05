<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;

/**
 * Class Payment_Element
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Stripe_Elements {

	/**
	 * Are we forcing users to log in before checking out?
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function must_login() {
		return ! is_user_logged_in() && tribe( Module::class )->login_required();
	}

	/**
	 * Returns the variables for gateway's checkout template.
	 *
	 * @since 5.3.0
	 *
	 * @return []
	 */
	public function get_checkout_template_vars() {
		return [
			'payment_element'   => $this->include_payment_element(),
			'card_element_type' => $this->card_element_type(),
		];
	}

	/**
	 * Include the Stripe Payment Element form.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function include_payment_element() {
		if ( tribe_get_option( Settings::$option_checkout_element ) !== Settings::PAYMENT_ELEMENT_SLUG ) {
			return false;
		}

		$payment_methods = ( new Merchant() )->get_payment_method_types();

		// Don't load the Payment Element if just the Credit Card method is selected.
		return ! ( 1 === count( $payment_methods ) && 'card' === $payment_methods[0] );
	}

	/**
	 * Include the Stripe Card Element form.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function card_element_type() {
		return tribe_get_option( Settings::$option_checkout_element_card_fields );
	}

}
