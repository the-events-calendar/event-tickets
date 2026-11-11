<?php

namespace Tribe\Tickets\Test\REST\V1\QR;

use Faker\Factory;
use Faker\Generator;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class ReadCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var Generator
	 */
	protected $faker;

	public function _before( Restv1Tester $I ) {
		parent::_before( $I );

		$this->faker = Factory::create();
	}

	/**
	 * It should not tell a visitor whether a publicly listed attendee is checked in.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__QR::get
	 */
	public function should_refuse_visitor_on_public_attendee( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish', 'meta_input' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [ 'checkin' => true ] );

		$I->sendGET( $this->qr_url( $attendee_id ) );

		$I->seeResponseCodeIs( 401 );
		$I->dontSeeResponseJsonMatchesJsonPath( '$.checked_in' );
	}

	/**
	 * It should not tell a visitor whether an opted-out attendee of a password-protected post is checked in.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__QR::get
	 */
	public function should_refuse_visitor_on_hidden_attendee( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [
			'post_status'   => 'publish',
			'post_password' => $this->faker->password(),
		] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [
			'checkin' => true,
			'optout'  => 'yes',
		] );

		$I->sendGET( $this->qr_url( $attendee_id ) );

		$I->seeResponseCodeIs( 401 );
		$I->dontSeeResponseJsonMatchesJsonPath( '$.checked_in' );
	}

	/**
	 * It should return the check-in state to a request that carries the QR API key.
	 *
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__QR::get
	 */
	public function should_return_check_in_state_with_api_key( Restv1Tester $I ) {
		$post_id     = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$attendee_id = $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [ 'checkin' => true ] );
		$api_key     = $this->faker->sha1();
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$I->sendGET( $this->qr_url( $attendee_id ), [ 'api_key' => $api_key ] );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [ 'id' => $attendee_id, 'checked_in' => '1' ] );
	}

	/**
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return string The QR read URL of the attendee.
	 */
	private function qr_url( int $attendee_id ): string {
		return $this->rest_url . 'qr/' . $attendee_id;
	}
}
