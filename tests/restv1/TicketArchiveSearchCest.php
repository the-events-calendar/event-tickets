<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveSearchCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;


	/**
	 * It should allow searching tickets
	 *
	 * @test
	 */
	public function should_allow_searching_tickets( Restv1Tester $I ) {
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

		$I->sendGET( $this->tickets_url, [ 'search' => 'blue' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $w_title['blue'], $w_description['blue ostrich'] ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'blue' ], $this->tickets_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'search' => 'yellow' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $w_title['yellow'] ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'yellow' ], $this->tickets_url . '/' ),
			'total'       => 1,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'search' => 'red' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $w_title['red'], $w_description['red parrot'], $w_description['yellow red-panda'] ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'red' ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'search' => 'blue ostrich' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $w_description['blue ostrich'] ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'blue ostrich' ], $this->tickets_url . '/' ),
			'total'       => 1,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'search' => 'magenta' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'magenta' ], $this->tickets_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'tickets'     => [],
		] );
	}
}
