<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByAttendeeMetaCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by attendee meta
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_attendee_meta( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_id        = $I->havePostInDatabase();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );

		$attendees = $this->create_many_attendees_for_ticket( 3, $rsvp_ticket_id, $post_id );

		$with_meta = \array_slice( $attendees, 0, 2 );
		tribe_attendees()
			->where( 'post__in', $with_meta )
			->set( '_tribe_tickets_meta', [ 'foo' => 'bar' ] )
			->save();

		$I->sendGET( $this->attendees_url, [ 'attendee_information_available' => true ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $with_meta )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendee_information_available' => true ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'attendee_information_available' => false ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )->by_primary_key( $attendees[2] );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendee_information_available' => false ], $this->attendees_url . '/' ),
			'total'       => 1,
			'total_pages' => 1,
			'attendees'   => [ $expected_attendees ],
		] );
	}
}
