<?php

class Tribe__Tickets__Commerce__PayPal__Handler__PDT {

	/**
	 * Set up hooks for PDT transaction handling
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
		if ( ! isset( $_GET['tx'] ) ) {
			return;
		}

		$paypal  = tribe( 'tickets.commerce.paypal' );
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$results = $this->validate_transaction( $_GET['tx'] );
		$results = $gateway->parse_transaction( $results );
		$gateway->set_transaction_data( $results );

		$paypal->generate_tickets();
	}

	/**
	 * Validates a PayPal transaction ensuring that it is authentic
	 *
	 * @since TBD
	 *
	 * @param $transaction
	 *
	 * @return array|bool
	 */
	public function validate_transaction( $transaction ) {
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$args = array(
			'body' => array(
				'cmd' => '_notify-synch',
				'tx' => $transaction,
				'at' => $gateway->identity_token,
			),
			'httpversion' => '1.1',
			'timeout' => 60,
			'user-agent' => 'EventTickets/' . Tribe__Tickets__Main::VERSION,
		);

		$response = wp_safe_remote_post( $gateway->get_cart_url(), $args );

		if (
			is_wp_error( $response )
			|| ! ( 0 === strpos( $response['body'], "SUCCESS" ) )
		) {
			return false;
		}

		return $this->parse_transaction_body( $response['body'] );
	}

	/**
	 * Parses flat transaction text
	 *
	 * @since TBD
	 *
	 * @param string $transaction
	 *
	 * @return array
	 */
	public function parse_transaction_body( $transaction ) {
		$results = array();

		$body    = explode( "\n", $transaction );
		//$body    = array_map( 'tribe_clean', $body );

		foreach ( $body as $line ) {
			if ( ! trim( $line ) ) {
				continue;
			}

			$line                = explode( '=', $line );
			$var                 = array_shift( $line );
			$results[ $var ]     = urldecode( implode( '=', $line ) );
		}

		return $results;
	}
}