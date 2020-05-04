<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;

class AttendeeArchiveCest extends BaseRestCest {

	/**
	 * Should return error if ET Plus is inactive when querying the Attendee Archive Endpoint.
	 *
	 * @test
	 */
	public function archive_should_return_error_if_et_plus_inactive( Restv1Tester $I ) {
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 401 );
	}

	/**
	 * Should return error if ET Plus is inactive when querying the Attendee Archive Endpoint even if admin.
	 *
	 * @test
	 */
	public function archive_should_return_error_if_et_plus_inactive_even_if_admin( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'administrator' );
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 401 );
	}

	/**
	 * Should return error if ET Plus is inactive when querying the Attendee Single Endpoint.
	 *
	 * @test
	 */
	public function single_should_return_error_if_et_plus_inactive( Restv1Tester $I ) {
		$I->sendGET( $this->attendees_url . '/1' );
		$I->seeResponseCodeIs( 401 );
	}

	/**
	 * Should return error if ET Plus is inactive when querying the Attendee Single Endpoint even if admin.
	 *
	 * @test
	 */
	public function single_should_return_error_if_et_plus_inactive_even_if_admin( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'administrator' );
		$I->sendGET( $this->attendees_url . '/1' );
		$I->seeResponseCodeIs( 401 );
	}

}
