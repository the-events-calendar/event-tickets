<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class SingleAttendeeCest extends BaseRestCest {

	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * It should return the attendee response.
	 *
	 * @test
	 */
	public function should_return_attendee_response( Restv1Tester $I ) {
		$post_id = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );

		$I->havePostmetaInDatabase( $post_id, '_tribe_hide_attendees_list', '1' );

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendees = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$attendee_id     = $attendees[ 0 ]['attendee_id'];
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}
}
