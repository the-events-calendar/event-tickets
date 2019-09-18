<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;

class CartUpdateCest extends BaseRestCest {

	use Ticket_Maker;

	/**
	 * It should allow updating cart for post.
	 *
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

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url . "/{$first_post_id}";

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
				],
			],
			'meta'     => [],
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$I->assertEquals( [
			[
				'ticket_id' => $first_ticket_id,
				'quantity'  => 15,
				'provider'  => 'tribe-commerce',
			],
			[
				'ticket_id' => $second_ticket_id,
				'quantity'  => 5,
				'provider'  => 'tribe-commerce',
			],
		], $response['tickets'] );

		$I->assertContains(
			'?tribe_tickets_redirect_to=https%3A%2F%2Fwww.sandbox.paypal.com%2Fcgi-bin%2Fwebscr%2F_cart'
				. '%3Fcmd%3D_cart'
				. '%26business%3Dmerchant%2540example.com'
				. '%26bn%3DModernTribe_SP'
			, $response['cart_url'] );

		$I->assertEquals( $response['cart_url'], $response['checkout_url'] );
	}

	/**
	 * It should allow updating empty cart for post.
	 *
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

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url . "/{$first_post_id}";

		$this->paypal_add_item_to_cart( $I, [
			$first_ticket_id => 15,
			$second_ticket_id => 5,
		], 0, $first_post_id );

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 0,
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 0,
				],
			],
			'meta'     => [],
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'tickets'      => [],
			'meta'         => [],
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
			'meta'     => [],
		] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}
}
