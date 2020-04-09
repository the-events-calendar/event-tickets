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
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/disable-etplus.php' ) );
		$I->haveMuPlugin( 'disable-etplus.php', $code );

		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 401 );
	}

	/**
	 * Should return error if ET Plus is inactive when querying the Attendee Single Endpoint.
	 *
	 * @test
	 */
	public function single_should_return_error_if_et_plus_inactive( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/disable-etplus.php' ) );
		$I->haveMuPlugin( 'disable-etplus.php', $code );

		$I->sendGET( $this->attendees_url . '/1' );
		$I->seeResponseCodeIs( 401 );
	}

}
