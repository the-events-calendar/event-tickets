<?php

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class SingleTicketBaseCest extends BaseRestCest {
	use RSVP_Ticket_Maker;

	/**
	 * It should allow getting a ticket information by ticket post ID
	 *
	 * @test
	 */
	public function should_allow_getting_a_ticket_information_by_ticket_post_id( Restv1Tester $I ) {
		$post_id   = $I->havePostInDatabase();
		$ticket_id = $this->make_ticket( $post_id );

		$I->sendGET( $this->tickets_url . "/{$ticket_id}" );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$expectedJson = [
			'id' => $ticket_id,
		];
		$I->seeResponseContainsJson( $expectedJson );
	}
}
