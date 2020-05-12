<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;

class SwaggerDocumentationCest extends BaseRestCest {
	/**
	 * @test
	 * it should expose a Swagger documentation endpoint
	 */
	public function it_should_expose_a_swagger_documentation_endpoint( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
	}

	/**
	 * @test
	 * it should return a JSON array containing headers in Swagger format
	 */
	public function it_should_return_a_json_array_containing_headers_in_swagger_format( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = (array) json_decode( $I->grabResponse() );
		$I->assertArrayHasKey( 'openapi', $response );
		$I->assertArrayHasKey( 'info', $response );
		$I->assertArrayHasKey( 'servers', $response );
		$I->assertArrayHasKey( 'paths', $response );
		$I->assertArrayHasKey( 'components', $response );
	}

	/**
	 * @test
	 * it should return the correct information
	 */
	public function it_should_return_the_correct_information( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = (array) json_decode( $I->grabResponse() );
		$I->assertArrayHasKey( 'info', $response );
		$info = (array) $response['info'];
		//version
		$I->assertArrayHasKey( 'version', $info );
		// title
		$I->assertArrayHasKey( 'title', $info );
		//description
		$I->assertArrayHasKey( 'description', $info );
	}

	/**
	 * @test
	 * it should return the site URL as host
	 */
	public function it_should_return_the_site_url_as_host( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = (array) json_decode( $I->grabResponse(), true );
		$I->assertArrayHasKey( 'url', $response['servers'][0] );
	}

	/**
	 * @test
	 *
	 * @skip will be part of the work for ticket https://central.tri.be/issues/108024
	 *
	 * it should contain information about the archive endpoint
	 */
	public function it_should_contain_information_about_the_archive_endpoint( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = (array) json_decode( $I->grabResponse() );
		$I->assertArrayHasKey( 'paths', $response );
		$paths = (array) $response['paths'];
		$I->assertArrayHasKey( '/tickets', $paths );
		$I->assertArrayHasKey( 'get', (array)$paths['/events:'] );
	}

	/**
	 * @test
	 *
	 * @skip will be part of the work for ticket https://central.tri.be/issues/108024
	 *
	 * it should contain information about the single event endpoint
	 */
	public function it_should_contain_information_about_the_single_event_endpoint( Restv1Tester $I ) {
		$I->sendGET( $this->documentation_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = (array) json_decode( $I->grabResponse() );
		$I->assertArrayHasKey( 'paths', $response );
		$paths = (array) $response['paths'];
		$I->assertArrayHasKey( '/tickets/{id}', $paths );
		$I->assertArrayHasKey( 'get', (array)$paths['/events/{id}:'] );
	}
}
