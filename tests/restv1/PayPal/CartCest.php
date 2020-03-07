<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;

/**
 * Class CartCest
 *
 * @package Tribe\Tickets\Test\REST\V1
 *
 * @group   cart
 */
class CartCest extends BaseRestCest {

	use Ticket_Maker;

	/**
	 * It should allow getting cart for post.
	 *
	 * @test
	 */
	public function should_allow_getting_cart_for_post( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$this->paypal_add_item_to_cart( $I, [
			$first_ticket_id  => 15,
			$second_ticket_id => 5,
		], 0, $first_post_id );

		$I->sendGET( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'post_id'  => $first_post_id,
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$I->assertEquals( [
			[
				'ticket_id' => $first_ticket_id,
				'quantity'  => 15,
				'post_id'   => $first_post_id,
				'optout'    => 1,
				'provider'  => 'tribe-commerce',
			],
			[
				'ticket_id' => $second_ticket_id,
				'quantity'  => 5,
				'post_id'   => $first_post_id,
				'optout'    => 1,
				'provider'  => 'tribe-commerce',
			],
		], $response['tickets'] );
		$I->assertEquals( [], $response['meta'] );
		$I->assertContains( 'https://www.sandbox.paypal.com/cgi-bin/webscr/_cart?cmd=_cart', $response['cart_url'] );
		$I->assertEquals( $response['cart_url'], $response['checkout_url'] );
	}

	/**
	 * It should allow getting cart for post without post ID.
	 *
	 * @test
	 */
	public function should_allow_getting_cart_for_post_without_post_id( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$this->paypal_add_item_to_cart( $I, [
			$first_ticket_id  => 15,
			$second_ticket_id => 5,
		], 0, $first_post_id );

		$I->sendGET( $cart_rest_url, [
			'provider' => 'tribe-commerce',
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'tickets'      => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
					'post_id'   => $first_post_id,
					'optout'    => 1,
					'provider'  => 'tribe-commerce',
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
					'post_id'   => $first_post_id,
					'optout'    => 1,
					'provider'  => 'tribe-commerce',
				],
			],
			'meta'         => [],
			'cart_url'     => '',
			'checkout_url' => '',
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting empty cart for post.
	 *
	 * @test
	 */
	public function should_allow_getting_empty_cart_for_post( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id   = current( $post_ids );
		$first_ticket_id = current( $tickets[ $first_post_id ] );

		$cart_rest_url = $this->cart_url;

		$I->sendGET( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'post_id'  => $first_post_id,
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
}
