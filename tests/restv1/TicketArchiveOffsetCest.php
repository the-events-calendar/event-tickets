<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveOffsetCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;


	/**
	 * It should allow offsetting the ticket results
	 *
	 * @test
	 */
	public function should_allow_offsetting_the_ticket_results( Restv1Tester $I ) {
		$post_ids     = $I->haveManyPostsInDatabase( 2 );
		$titles       = [ 'red', 'blue', 'green', 'yellow' ];
		$descriptions = [ 'blue ostrich', 'green wallaby', 'red parrot', 'yellow red-panda' ];
		$tickets      = $w_title = $w_description = [];
		$i            = 0;
		foreach ( $post_ids as $post_id ) {
			$title       = $titles[ $i ];
			$description = $descriptions[ $i ++ ];
			$tickets[]   = $w_title[ $title ] = $w_description[ $description ] = $this->create_rsvp_ticket( $post_id, [
				'post_title'   => $title,
				'post_excerpt' => $description
			] );

			$title       = $titles[ $i ];
			$description = $descriptions[ $i ++ ];
			$tickets[]   = $w_title[ $title ] = $w_description[ $description ] = $this->create_paypal_ticket( $post_id, 1, [
				'post_title'   => $title,
				'post_excerpt' => $description
			] );
		}

		$I->sendGET( $this->tickets_url, [ 'offset' => 1 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', \array_slice( $tickets, 1 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'offset' => 1 ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
		$I->seeHttpHeader( 'X-ET-TOTAL', 4 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 1 );

		$I->sendGET( $this->tickets_url, [ 'offset' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ end( $tickets ) ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'offset' => 3 ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
		$I->seeHttpHeader( 'X-ET-TOTAL', 4 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 1 );

		$I->sendGET( $this->tickets_url, [ 'offset' => 4 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'offset' => 4 ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );
		$I->seeHttpHeader( 'X-ET-TOTAL', 0 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 0 );

		// more than there are tickets
		$I->sendGET( $this->tickets_url, [ 'offset' => 5 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'offset' => 5 ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );
		$I->seeHttpHeader( 'X-ET-TOTAL', 0 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 0 );
	}

	/**
	 * It should return 400 when passing invalid offset values
	 *
	 * @test
	 */
	public function should_return_400_when_passing_invalid_offset_values( Restv1Tester $I ) {
		$I->sendGET( $this->tickets_url, [ 'offset' => 'foo' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 400 );
	}
}
