<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;

class DiscoveryCest extends BaseRestCest {

	/**
	 * @test
	 * it should return a custom headers for discovery
	 */
	public function it_should_return_custom_headers_for_discovery( Restv1Tester $I ) {
		$I->sendHEAD( $this->site_url );

		$I->seeHttpHeader( 'X-ET-API-VERSION', 'v1' );
		$I->seeHttpHeader( 'X-ET-API-ROOT', $this->rest_url );
		$I->seeHttpHeader( 'X-ET-API-ORIGIN', $this->site_url );
	}

	/**
	 * @test
	 * it should return disabled header if TEC REST API is disabled via option
	 */
	public function it_should_return_disabled_header_if_tec_rest_api_is_disabled_via_option( Restv1Tester $I ) {
		$I->haveOptionInDatabase( $this->tec_option, [ $this->rest_disable_option => true ] );

		$I->sendHEAD( $this->site_url );

		$I->seeHttpHeader( 'X-ET-API-VERSION', 'disabled' );
		$I->dontSeeHttpHeader( 'X-ET-API-ROOT', $this->rest_url );
		$I->dontSeeHttpHeader( 'X-ET-API-ORIGIN', $this->site_url );
	}

}
