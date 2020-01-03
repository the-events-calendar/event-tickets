<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByCheckedinCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by checkedin status
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_checkedin_status( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$rsvp_post                             = $I->havePostInDatabase();
		$rsvp_ticket                           = $this->create_rsvp_ticket( $rsvp_post );
		$rsvp_checked_in_attendees       = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket, $rsvp_post, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) ),
				'source' => 'bar',
				'author' => 'John Doe',
			]
		] );
		$rsvp_not_checked_in_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket, $rsvp_post, [
			'checkin' => false,
		] );
		$paypal_post                           = $I->havePostInDatabase();
		$paypal_ticket                         = $this->create_paypal_ticket( $paypal_post, 3 );
		$paypal_checked_in_attendees     = $this->create_many_attendees_for_ticket( 2, $paypal_ticket, $paypal_post, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) ),
				'source' => 'bar',
				'author' => 'Jane Doe',
			]
		] );
		$paypal_not_checked_in_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket, $paypal_post, [
			'checkin' => false,
		] );
		$all_attendees                   = array_merge(
			$rsvp_checked_in_attendees,
			$rsvp_not_checked_in_attendees,
			$paypal_checked_in_attendees,
			$paypal_not_checked_in_attendees
		);

		$I->sendGET( $this->attendees_url, [ 'checkedin' => true ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $rsvp_checked_in_attendees, $paypal_checked_in_attendees ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin' => true ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'checkedin' => false ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $rsvp_not_checked_in_attendees, $paypal_not_checked_in_attendees ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin' => false ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $all_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 8,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
