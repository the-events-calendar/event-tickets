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

		$attendee_id     = $attendees[0]['attendee_id'];
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function test_should_not_allow_check_in_for_non_complete_order_status( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'no',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$expected_json = [
			'code'    => 'tec-et-attendee-invalid-check-in',
			'message' => 'Attendee Order status is not authorized for check-in.',
		];
		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function test_should_allow_check_in_for_complete_order_status( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'checked_in' => true ] );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__Single_Attendee::validate_check_in
	 */
	public function should_not_allow_check_in_for_already_checked_in_attendee( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => false,
		] );

		$I->generate_nonce_for_role( 'administrator' );
		$ticket_rest_url = $this->attendees_url . "/{$attendee_id}";

		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [ 'checked_in' => true ] );

		// Try to check in again.
		$I->sendPost( $ticket_rest_url, [ 'check_in' => 1 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$expected_json = [
			'code'    => 'tec-et-attendee-already-checked-in',
			'message' => 'Attendee is already checked in.',
		];
		$I->seeResponseContainsJson( $expected_json );
	}
}
