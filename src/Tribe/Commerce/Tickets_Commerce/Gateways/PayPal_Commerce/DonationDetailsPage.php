<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\PayPalClient;

class DonationDetailsPage {

	/**
	 * Return PayPal Commerce payment details page url.
	 *
	 * @since TBD
	 *
	 * @param string $transactionId donation transaction id.
	 *
	 * @return string
	 */
	public function getPayPalPaymentUrl( $transactionId ) {
		return sprintf(
			'<a href="%1$sactivity/payment/%2$s" title="%3$s" target="_blank">%2$s</a>',
			esc_url( tribe( PayPalClient::class )->getHomePageUrl() ),
			esc_attr( $transactionId ),
			esc_attr__( 'View PayPal Commerce payment', 'event-tickets' )
		);
	}
}
