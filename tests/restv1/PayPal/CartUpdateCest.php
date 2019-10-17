<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;

/**
 * Class CartUpdateCest
 *
 * @package Tribe\Tickets\Test\REST\V1
 *
 * @group   cart
 */
class CartUpdateCest extends BaseRestCest {

	use Ticket_Maker;

	/**
	 * It should allow updating cart for post.
	 *
	 * @test
	 */
	public function should_allow_updating_cart_for_post( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
					'optout'    => 1,
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
					'optout'    => 0,
				],
			],
			'meta'     => [],
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
				'optout'    => 0,
				'provider'  => 'tribe-commerce',
			],
		], $response['tickets'] );
		$I->assertEquals( [], $response['meta'] );
		$I->assertContains(
			'https://www.sandbox.paypal.com/cgi-bin/webscr/_cart'
				. '?cmd=_cart'
				. '&business=merchant%40example.com'
				. '&bn=ModernTribe_SP',
			$response['cart_url']
		);
		$I->assertEquals( $response['cart_url'], $response['checkout_url'] );
	}

	/**
	 * It should allow updating empty cart for post.
	 *
	 * @test
	 */
	public function should_allow_updating_empty_cart_for_post( Restv1Tester $I ) {
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

	/**
	 * It should not get cart for non-existent post.
	 *
	 * @test
	 */
	public function should_not_get_cart_url_for_non_post( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 15,
					'optout'    => 1,
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
					'optout'    => 0,
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
				'post_id'   => $first_post_id,
				'optout'    => 1,
				'provider'  => 'tribe-commerce',
			],
			[
				'ticket_id' => $second_ticket_id,
				'quantity'  => 5,
				'post_id'   => $first_post_id,
				'optout'    => 0,
				'provider'  => 'tribe-commerce',
			],
		], $response['tickets'] );

		$I->assertEquals( '', $response['cart_url'] );
		$I->assertEquals( '', $response['checkout_url'] );
	}

	/**
	 * It should not get cart for non-existent post.
	 *
	 * @test
	 */
	public function should_not_get_cart_for_non_existent_post( Restv1Tester $I ) {
		$cart_rest_url = $this->cart_url;

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [],
			'meta'     => [],
			'post_id'  => 12345555,
		] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should not get cart for non-existent ticket.
	 *
	 * @test
	 */
	public function should_not_allow_non_existent_ticket( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => 123456789,
					'quantity'  => 15,
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
				],
			],
			'meta'     => [],
			'post_id'  => $first_post_id,
		] );
		$I->seeResponseCodeIs( 500 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$I->assertEquals( 'ticket-does-not-exist', $response['code'] );
	}

	/**
	 * It should not get cart for ticket with not enough capacity.
	 *
	 * @test
	 */
	public function should_not_allow_ticket_with_not_enough_capacity( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );

		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ] = $this->create_many_paypal_tickets( 2, $post_id );

			return $acc;
		}, [] );

		$first_post_id = current( $post_ids );

		list( $first_ticket_id, $second_ticket_id ) = $tickets[ $first_post_id ];

		$cart_rest_url = $this->cart_url;

		$I->sendPOST( $cart_rest_url, [
			'provider' => 'tribe-commerce',
			'tickets'  => [
				[
					'ticket_id' => $first_ticket_id,
					'quantity'  => 1000, // Default capacity for Test tickets is 100.
				],
				[
					'ticket_id' => $second_ticket_id,
					'quantity'  => 5,
				],
			],
			'meta'     => [],
			'post_id'  => $first_post_id,
		] );
		$I->seeResponseCodeIs( 500 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$I->assertEquals( 'ticket-capacity-not-available', $response['code'] );
	}
}
