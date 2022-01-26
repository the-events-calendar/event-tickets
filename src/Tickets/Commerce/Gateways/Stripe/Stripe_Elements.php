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
			'must_login' => $this->must_login(),
			'payment_element' => $this->include_payment_element(),
			'card_element' => $this->include_card_element(),
		] );
	}

	/**
	 * Include the Stripe Payment Element form.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 */
	public function include_payment_element() {
		return true;
	}

	/**
	 * Include the Stripe Card Element form.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 */
	public function include_card_element() {
		return true;
	}

}