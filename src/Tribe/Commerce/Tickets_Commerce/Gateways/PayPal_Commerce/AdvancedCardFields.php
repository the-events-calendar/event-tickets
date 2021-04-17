<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

/**
 * Class AdvancedCardFields
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class AdvancedCardFields {

	/**
	 * PayPal commerce uses smart buttons to accept payment.
	 *
	 * @since TBD
	 *
	 * @param int $formId Payment Form ID.
	 *
	 * @access public
	 */
	public function addCreditCardForm( $formId ) {
		$this->removeBillingField();
		give_get_cc_form( $formId );
	}

	/**
	 * Remove Address Fields if user has option enabled.
	 *
	 * @since TBD
	 */
	private function removeBillingField() {
		remove_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );
	}
}
