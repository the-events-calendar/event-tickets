<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByIdCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow include and exclude ticket IDS from results
	 *
	 * @test
	 */
	public function should_allow_include_and_exclude_ticket_ids_from_results( Restv1Tester $I ) {
		// 5 posts, 2 tickets per post = 10 tickets
		$post_ids = $I->haveManyPostsInDatabase( 5 );
		$tickets  = array_reduce( $post_ids, function ( $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id );
			$acc[] = $this->create_paypal_ticket_basic( $post_id, 3 );

			return $acc;
		}, [] );

		$included = \array_slice( $tickets, 0, 3 );
		$I->sendGET( $this->tickets_url, [ 'include' => $included ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $included )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'include' => implode( ',', $included ) ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$excluded = \array_slice( $tickets, 7 );
		$I->sendGET( $this->tickets_url, [ 'exclude' => implode( ',', $excluded ) ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 0, 7 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'exclude' => implode( ',', $excluded ) ], $this->tickets_url . '/' ),
			'total'       => 7,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$random_integers = array_map( function () use ( $tickets ) {
			do {
				$integer = random_int( 2000, 4000 );
			} while ( \in_array( $integer, $tickets, true ) );

			return $integer;
		}, range( 1, 3 ) );

		$I->sendGET( $this->tickets_url, [ 'include' => $random_integers ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'include' => implode( ',', $random_integers ) ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );

		$I->sendGET( $this->tickets_url, [ 'exclude' => implode( ',', $random_integers ) ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $tickets )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'exclude' => implode( ',', $random_integers ) ], $this->tickets_url . '/' ),
			'total'       => 10,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
	}

	/**
	 * It should return 400 if not all IDs are valid
	 *
	 * @test
	 */
	public function should_return_400_if_not_all_ids_are_valid( \Restv1Tester $I ) {
		$post_id   = $I->havePostInDatabase();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$I->sendGET( $this->tickets_url, [ 'include' => '12,34,foo' ] );
		$I->seeResponseCodeIs( 400 );

		$I->sendGET( $this->tickets_url, [ 'exclude' => [ 23, 89, 'foo' ] ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}
}
