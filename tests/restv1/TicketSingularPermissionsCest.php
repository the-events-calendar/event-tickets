<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketSingularPermissionsCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	private function get_a_single_rsvp_id_having_attendees( Restv1Tester $I ) {
		$event_id = $I->haveEventInDatabase();

		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $event_id );

		return $ticket_id;
	}

	/**
	 * It should include a Ticket's Attendee Information if request is from an Editor.
	 *
	 * @test
	 */
	public function should_contain_attendee_info_if_editor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$ticket_id = $this->get_a_single_rsvp_id_having_attendees( $I );

		$this_ticket_url = esc_url( add_query_arg( 'id', $ticket_id, trailingslashit( $this->tickets_url ) ) );

		$I->sendGET( $this_ticket_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets   = tribe_tickets( 'restv1' )->all();
		$expected_attendees = tribe_attendees( 'restv1' )->all();
		$I->seeResponseContainsJson(
			[
				'rest_url'  => $this_ticket_url,
				'tickets'   => $expected_tickets,
				'attendees' => $expected_attendees,
			]
		);
	}

	/**
	 * It should not include a Ticket's Attendee Information if request is from a Contributor.
	 *
	 * @test
	 */
	public function should_not_contain_attendee_info_if_contributor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'contributor' );

		$ticket_id = $this->get_a_single_rsvp_id_having_attendees( $I );

		$this_ticket_url = esc_url( add_query_arg( 'id', $ticket_id, trailingslashit( $this->tickets_url ) ) );

		$I->sendGET( $this_ticket_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )->all();
		$I->canSeeResponseContainsJson(
			[
				'rest_url'  => $this_ticket_url,
				'tickets'   => $expected_tickets,
				'attendees' => [], // property should be present as an empty array
			]
		);
	}
}
