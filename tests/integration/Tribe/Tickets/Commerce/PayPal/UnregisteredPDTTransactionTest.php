<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Notices as Notices;
use Tribe__Tickets__Commerce__PayPal__Transaction as Transaction;

class UnregisteredPDTTransactionTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * It should save PDT transaction information to database when no identity token is set
	 *
	 * @test
	 */
	public function should_save_pdt_transaction_information_to_database_when_no_identity_token_is_set() {
		add_filter( 'tribe_tickets_commerce_paypal_handler', function () {
			return 'pdt';
		} );

		$transaction_id = "1HA93120AS9545244";
		// no PDT identity token set
		tribe_update_option( 'ticket-paypal-identity-token', '' );
		// this is a PDT GET request
		$url = home_url( "?amt=3.00&cc=USD&cm=user_id%3D0&st=Completed&tx={$transaction_id}" );
		$url = add_query_arg( [ 'custom' => Custom_Argument::encode( [ 'tribe_handler' => 'tpp' ] ) ], $url );
		$this->go_to( $url );

		$gateway = new Gateway( new Notices() );
		$gateway->build_handler();
		$gateway->parse_transaction( [ 'foo' => 'bar' ] );

		$saved_transaction = Transaction::build_from_id( $transaction_id );

		$this->assertEquals( $transaction_id, $saved_transaction->get_id() );
		$this->assertEquals( 'PDT', $saved_transaction->get_data( 'handler' ) );
		$this->assertEquals( '3.00', $saved_transaction->get_data( 'amt' ) );
		$this->assertEquals( 'USD', $saved_transaction->get_data( 'cc' ) );
		$this->assertEquals( 'user_id=0', $saved_transaction->get_data( 'cm' ) );
		$this->assertEquals( 'Completed', $saved_transaction->get_data( 'st' ) );
	}
}
