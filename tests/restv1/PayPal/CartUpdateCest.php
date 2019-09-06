<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;

class CartUpdateCest extends BaseRestCest {

	use Ticket_Maker;

	/**
	 * It should allow updating cart for post.
	 *
	 * @skip
	 * @test
	 */
	public function should_allow_updating_cart_for_post( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );

		$I->haveMuPlugin( 'test-attendees.php', $code );

		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id   = current( $post_ids );
		$first_ticket_id = current( $tickets[ $first_post_id ] );

		$cart_rest_url = $this->cart_url . "/{$first_post_id}";

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
				],
			],
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'tickets'      => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
					'provider'  => 'tribe-commerce',
				],
			],
			'cart_url'     => '',
			'checkout_url' => '',
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow updating empty cart for post.
	 *
	 * @skip
	 * @test
	 */
	public function should_allow_updating_empty_cart_for_post( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );

		$I->haveMuPlugin( 'test-attendees.php', $code );

		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id   = current( $post_ids );
		$first_ticket_id = current( $tickets[ $first_post_id ] );

		$cart_rest_url = $this->cart_url . "/{$first_post_id}";

		/** @var \Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$invoice_number = $gateway->set_invoice_number();

		/** @var \Tribe__Tickets__Commerce__PayPal__Cart__Interface $cart */
		$cart = tribe( 'tickets.commerce.paypal.cart' );

		$cart->set_id( $invoice_number );
		$cart->add_item( $first_ticket_id, 15 );
		$cart->save();

		// Save cart cookie.
		$I->setCookie( $gateway::$invoice_cookie_name, $invoice_number, [
			'expires' => time() + 900,
		] );

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [],
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'tickets'      => [],
			'cart_url'     => '',
			'checkout_url' => '',
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should not get cart for non-existent post.
	 *
	 * @test
	 */
	public function should_not_get_cart_for_non_existent_post( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );

		$I->haveMuPlugin( 'test-attendees.php', $code );

		$cart_rest_url = $this->cart_url . '/12345555';

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [],
		] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}
}
