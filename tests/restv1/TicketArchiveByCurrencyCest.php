<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByCurrencyCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow filtering tickets by currency
	 *
	 * @test
	 */
	public function should_allow_filtering_tickets_by_currency( Restv1Tester $I ) {
		// 3 posts, 2 tickets per post = 6 tickets
		// 2 w/ USD currency, 3 w/o currency
		$post_ids       = $I->haveManyPostsInDatabase( 3 );
		$rsvp_tickets   = [];
		$paypal_tickets = [];
		$tickets        = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$rsvp_tickets, &$paypal_tickets ) {
			$acc[] = $rsvp_tickets[] = $this->create_rsvp_ticket( $post_id );
			$acc[] = $paypal_tickets[] = $this->create_paypal_ticket_basic( $post_id, 2 );

			return $acc;
		}, [] );

		// no currency filter, I should get all of them
		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $tickets )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->tickets_url . '/',
			'total'       => 6,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// USD currency filter, I should get the Tribe Commerce ones
		$I->sendGET( $this->tickets_url, [ 'currency' => 'USD' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $paypal_tickets )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'currency' => 'USD' ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// usd (lowercase) currency filter, I should get the Tribe Commerce ones
		$I->sendGET( $this->tickets_url, [ 'currency' => 'usd' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'currency' => 'usd' ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// USD or CHF currency filter, I should get the Tribe Commerce ones
		$I->sendGET( $this->tickets_url, [ 'currency' => ['USD','chf'] ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'currency' => 'USD,chf' ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// CHF currency filter, I should get none
		$I->sendGET( $this->tickets_url, [ 'currency' => 'CHF' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'currency' => 'CHF' ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );


	}
}
