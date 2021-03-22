<?php

namespace TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce;

use TEC\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails;
use TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\EventListener;
use TEC\Repositories\PaymentsRepository;

/**
 * Class PaymentEventListener
 *
 * @since   TBD
 * @package TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce
 *
 */
abstract class PaymentEventListener implements EventListener {

	/**
	 * @since TBD
	 *
	 * @var PaymentsRepository
	 */
	protected $paymentsRepository;

	/**
	 * @var MerchantDetails
	 */
	protected $merchantDetails;

	/**
	 * PaymentEventListener constructor.
	 *
	 * @since TBD
	 *
	 * @param PaymentsRepository $paymentsRepository
	 * @param MerchantDetails    $merchantDetails
	 */
	public function __construct( PaymentsRepository $paymentsRepository, MerchantDetails $merchantDetails ) {
		$this->paymentsRepository = $paymentsRepository;
		$this->merchantDetails    = $merchantDetails;
	}
}
