<?php

namespace TEC\PaymentGateways\PayPalCommerce;

/**
 * Class AdvancedCardFields
 *
 * @since TBD
 * @package TEC\PaymentGateways\PayPalCommerce
 *
 */
class AdvancedCardFields {

	/**
	 * PayPal commerce uses smart buttons to accept payment.
	 *
	 * @since TBD
	 *
	 * @param int $formId Donation Form ID.
	 *
	 * @access public
	 * @return string $form
	 *
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
