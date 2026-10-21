<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use WP_Error;
use WP_REST_Request;

class Attendee_ArchiveTest extends WPTestCase {
	private const VALID_KEY = 'valid-lab-key-1234567890';

	public function setUp(): void {
		parent::setUp();

		tribe_update_option( 'tickets-plus-qr-options-api-key', self::VALID_KEY );
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		unset( $_GET['api_key'], $_REQUEST['api_key'] );

		parent::tearDown();
	}

	/**
	 * The endpoint reads the key from the request superglobals, as it does for a real REST call.
	 *
	 * @param string|null $api_key The api_key to present, or null to present none.
	 *
	 * @return WP_Error|\WP_REST_Response
	 */
	private function get_response( ?string $api_key ) {
		if ( null === $api_key ) {
			unset( $_GET['api_key'], $_REQUEST['api_key'] );
		} else {
			$_GET['api_key']     = $api_key;
			$_REQUEST['api_key'] = $api_key;
		}

		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/attendees' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 10 );

		return tribe( 'tickets.rest-v1.endpoints.attendees-archive' )->get( $request );
	}

	/**
	 * @test
	 * @see https://linear.app/nexcess/issue/SMTNC-3048
	 */
	public function it_should_reject_a_provided_but_invalid_api_key_with_401(): void {
		$response = $this->get_response( 'this-key-is-not-valid' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 401, $response->get_error_data()['status'] );
	}

	/**
	 * @test
	 */
	public function it_should_allow_an_anonymous_read_when_no_api_key_is_provided(): void {
		$response = $this->get_response( null );

		$this->assertNotInstanceOf( WP_Error::class, $response );
	}

	/**
	 * @test
	 */
	public function it_should_allow_a_valid_api_key(): void {
		$response = $this->get_response( self::VALID_KEY );

		$this->assertNotInstanceOf( WP_Error::class, $response );
	}
}
