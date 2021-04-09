<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners\PayPalCommerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Repositories\MerchantDetails;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners\EventListener;
use TEC\Repositories\PaymentsRepository;

/**
 * Class PaymentEventListener
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners\PayPalCommerce
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
