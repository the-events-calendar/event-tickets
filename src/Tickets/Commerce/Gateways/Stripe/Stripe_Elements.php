<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;

/**
 * Class Payment_Element
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Stripe_Elements {

	/**
	 * Are we forcing users to log in before checking out?
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function must_login() {
		return ! is_user_logged_in() && tribe( Module::class )->login_required();
	}

	/**
	 * Returns the variables for gateway's checkout template.
	 *
	 * @since TBD
	 *
	 * @return []
	 */
	public function get_checkout_template_vars() {
		return [
			'payment_element' => $this->include_payment_element(),
		];
	}

	/**
	 * Include the Stripe Payment Element form.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function include_payment_element() {
		return tribe_get_option( Settings::$option_checkout_element ) === Settings::PAYMENT_ELEMENT_SLUG;
	}

	/**
	 * Include the Stripe Card Element form.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function card_element_type() {
		return tribe_get_option( Settings::$option_checkout_element_card_fields );
	}

}
