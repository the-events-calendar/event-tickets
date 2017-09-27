<?php

class Tribe__Tickets__Commerce__PayPal__Handler__IPN {

	/**
	 * Set up hooks for IPN transaction communication
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'template_redirect', array( $this, 'check_response' ) );
	}

	/**
	 * Checks the request to see if payment data was communicated
	 *
	 * @since TBD
	 */
	public function check_response() {
		if (
			empty( $_POST )
			|| ! isset( $_POST['txn_id'] )
			|| ! isset( $_POST['payer_email'] )
			|| ! $this->valid_transaction()
		) {
			return;
		}

		$paypal  = tribe( 'tickets.commerce.paypal' );
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$data = wp_unslash( $_POST );

		$results = $gateway->parse_transaction( $data );

		$gateway->set_transaction_data( $results );

		if ( 'completed' === trim( strtolower( $data['payment_status'] ) ) ) {
			$paypal->generate_tickets();

			// since the purchase has completed, reset the invoice number
			$gateway->reset_invoice_number();
		}
	}

	/**
	 * Validates a PayPal transaction ensuring that it is authentic
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function valid_transaction() {
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$body        = wp_unslash( $_POST );
		$body['cmd'] = '_notify-validate';

		$args = array(
			'body'        => $body,
			'httpversion' => '1.1',
			'timeout'     => 60,
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'EventTickets/' . Tribe__Tickets__Main::VERSION,
		);

		$response = wp_safe_remote_post( $gateway->get_cart_url(), $args );

		if (
			! is_wp_error( $response )
			&& 200 <= $response['response']['code']
			&& 300 > $response['response']['code']
			&& strstr( $response['body'], 'VERIFIED' )
		) {
			return true;
		}

		return false;
	}
}