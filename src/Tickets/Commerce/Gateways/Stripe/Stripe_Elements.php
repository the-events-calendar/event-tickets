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
	 * Include the Stripe Payment form.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 */
	public function include_form( $file, $name, $template ) {
		$template->template( 'gateway/stripe/form', [
			'must_login'      => $this->must_login(),
			'payment_element' => $this->include_payment_element(),
		] );
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
