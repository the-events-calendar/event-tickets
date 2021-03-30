<?php

namespace TEC\PaymentGateways\PayPalCommerce\SDK_Interface;

use Exception;
use TEC\PaymentGateways\PayPalCommerce\SDK\Repositories\PayPalOrder;

/**
 * Class RefundPaymentHandler
 *
 * @since TBD
 */
class RefundPaymentHandler {

	/**
	 * @since TBD
	 *
	 * @var PayPalOrder
	 */
	private $ordersRepository;

	/**
	 * RefundPaymentHandler constructor.
	 *
	 * @since TBD
	 *
	 * @param PayPalOrder $ordersRepository
	 */
	public function __construct( PayPalOrder $ordersRepository ) {
		$this->ordersRepository = $ordersRepository;
	}

	/**
	 * Refunds the payment when the donation is marked as refunded
	 *
	 * @since TBD
	 *
	 * @param int $donationId
	 *
	 * @throws Exception
	 */
	public function refundPayment( $donationId ) {
		if ( ! $this->isAdminOptInToRefundPaymentOnPayPal() ) {
			return;
		}

		$payPalPaymentId   = give_get_payment_transaction_id( $donationId );
		$paymentGateway    = give_get_payment_gateway( $donationId );
		$newDonationStatus = give_clean( $_POST['give-payment-status'] );

		if ( 'refunded' !== $newDonationStatus || PayPalCommerce::GATEWAY_ID !== $paymentGateway ) {
			return;
		}

		try {
			$this->ordersRepository->refundPayment( $payPalPaymentId );
		} catch ( Exception $ex ) {
			wp_safe_redirect( admin_url( "edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details&id={$donationId}&paypal-error=refund-failure" ) );
			exit();
		}
	}

	/**
	 * show Paypal Commerce payment refund failure notice.
	 *
	 * @since TBD
	 */
	public function showPaymentRefundFailureNotice() {
		if ( ! isset( $_GET['paypal-error'] ) || 'refund-failure' !== $_GET['paypal-error'] ) {
			return;
		}

		// @todo Replace the URL here.
		$logs_url = admin_url( 'edit.php?post_type=give_forms&page=give-tools&tab=logs' );

		$notice = sprintf(
			'<strong>%1$s</strong> %2$s %3$s <a href="%4$s" target="_blank">%5$s</a> %6$s',
			esc_html__( 'Tickets Commerce: PayPal Commerce', 'event-tickets' ),
			esc_html__( 'We were unable to process this refund.', 'event-tickets' ),
			esc_html__( 'Please', 'event-tickets' ),
			esc_url( $logs_url ),
			esc_html__( 'check the logs', 'event-tickets' ),
			esc_html__( 'for detailed information.', 'event-tickets' )
		);

		tribe_notice( __FUNCTION__, $notice, [
			'dismiss' => false,
			'type'    => 'warning',
		] );
	}

	/**
	 * This function will display field to opt for refund.
	 *
	 * @since TBD
	 *
	 * @param int $donationId Donation ID.
	 *
	 * @return void
	 */
	public function optInForRefundFormField( $donationId ) {
		// @todo Add code to get current payment gateway for a commerce provider.
		if ( PayPalCommerce::GATEWAY_ID !== give_get_payment_gateway( $donationId ) ) {
			return;
		}

		?>
		<div id="give-paypal-commerce-opt-refund-wrap" class="give-paypal-commerce-opt-refund give-admin-box-inside give-hidden">
			<p>
				<input type="checkbox" id="give-paypal-commerce-opt-refund" name="give_paypal_donations_optin_for_refund" value="1" />
				<label for="give-paypal-commerce-opt-refund">
					<?php esc_html_e( 'Refund Charge in PayPal?', 'event-tickets' ); ?>
				</label>
			</p>
		</div>

		<?php
	}

	/**
	 * Return whether or not admin optin for refund payment in PayPal
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isAdminOptInToRefundPaymentOnPayPal() {
		return ! empty( $_POST['give_paypal_donations_optin_for_refund'] )
			? (bool) absint( $_POST['give_paypal_donations_optin_for_refund'] )
			: false;
	}
}
