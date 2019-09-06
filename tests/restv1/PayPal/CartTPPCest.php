<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;

class CartTPPCest extends BaseRestCest {

	use Ticket_Maker;

	/**
	 * It should allow getting cart for post.
	 *
	 * @test
	 */
	public function should_allow_getting_cart_for_post( Restv1Tester $I ) {
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

		/** @var \Tribe__Tickets__Commerce__PayPal__Cart__Interface $cart */
		$cart = tribe( 'tickets.commerce.paypal.cart' );

		$cart->set_id( '123456' );
		$cart->add_item( $first_ticket_id, 15 );

		$I->sendGET( $cart_rest_url, [ 'provider' => 'tribe-commerce' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'tickets' => [
				'ticket_id' => $first_ticket_id,
				'quantity'  => 15,
			],
		], json_decode( $I->grabResponse(), true ) );
	}
}
