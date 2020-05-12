<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Class TicketSingularPermissionsCest
 *
 * Single ticket ID testing if correct information is returned based on user permissions.
 *
 * @package Tribe\Tickets\Test\REST\V1
 */
class TicketSingularPermissionsCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	private function get_a_single_rsvp_id_having_attendees( Restv1Tester $I ) {
		$post_id = $I->havePostInDatabase();

		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$this->create_many_attendees_for_ticket( 5, $ticket_id, $post_id );

		return $ticket_id;
	}

	/**
	 * It should include a Ticket's Attendee Information if request is from an Admin.
	 *
	 * @test
	 */
	public function should_contain_attendee_info_if_admin( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'administrator' );

		$ticket_id = $this->get_a_single_rsvp_id_having_attendees( $I );

		$this_ticket_url = esc_url( trailingslashit( $this->tickets_url ) . $ticket_id );

		$I->sendGET( $this_ticket_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_attendees = tribe_attendees( 'restv1' )->all();
		$I->seeResponseContainsJson(
			[
				'rest_url'  => $this_ticket_url,
				'id'        => $ticket_id,
				'attendees' => $expected_attendees,
			]
		);

		// 'tickets' only appears in archives
		$I->cantSeeResponseContainsJson( [ 'tickets' ] );
	}

	/**
	 * It should not include a Ticket's Attendee Information if request is from an Editor.
	 *
	 * @test
	 */
	public function should_not_contain_attendee_info_if_editor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$ticket_id = $this->get_a_single_rsvp_id_having_attendees( $I );

		$this_ticket_url = esc_url( trailingslashit( $this->tickets_url ) . $ticket_id );

		$I->sendGET( $this_ticket_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->canSeeResponseContainsJson(
			[
				'rest_url'  => $this_ticket_url,
				'id'        => $ticket_id,
				'attendees' => [], // property should be present as an empty array
			]
		);

		// 'tickets' only appears in archives
		$I->cantSeeResponseContainsJson( [ 'tickets' ] );
	}

	/**
	 * It should not include a Ticket's Attendee Information if request is from a Contributor.
	 *
	 * @test
	 */
	public function should_not_contain_attendee_info_if_contributor( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'contributor' );

		$ticket_id = $this->get_a_single_rsvp_id_having_attendees( $I );

		$this_ticket_url = esc_url( trailingslashit( $this->tickets_url ) . $ticket_id );

		$I->sendGET( $this_ticket_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->canSeeResponseContainsJson(
			[
				'rest_url'  => $this_ticket_url,
				'id'        => $ticket_id,
				'attendees' => [], // property should be present as an empty array
			]
		);

		// 'tickets' only appears in archives
		$I->cantSeeResponseContainsJson( [ 'tickets' ] );
	}
}