<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketNotAccessibleCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should not allow accessing non public tickets
	 *
	 * @test
	 */
	public function should_not_allow_accessing_non_public_tickets( \Restv1Tester $I ) {
		$post_id   = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id, [ 'post_status' => 'private' ] );

		$I->sendGET( $this->tickets_url . "/{$ticket_id}" );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 401 );

		$I->generate_nonce_for_role( 'editor' );

		$I->sendGET( $this->tickets_url . "/{$ticket_id}" );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
	}

	/**
	 * It should not allow accessing tickets for non public events
	 *
	 * @test
	 */
	public function should_not_allow_accessing_tickets_for_non_public_events( \Restv1Tester $I ) {
		$post_id   = $I->havePostInDatabase( [ 'post_status' => 'private' ] );
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$I->sendGET( $this->tickets_url . "/{$ticket_id}" );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 401 );

		$I->generate_nonce_for_role( 'editor' );

		$I->sendGET( $this->tickets_url . "/{$ticket_id}" );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
	}
}
