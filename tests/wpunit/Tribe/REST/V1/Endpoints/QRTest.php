<?php

namespace Tribe\Tickets\REST\V1\Endpoints;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class QRTest extends WPTestCase {
	use Attendee_Maker;
	use Ticket_Maker;

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__QR::get
	 */
	public function it_should_refuse_a_user_who_cannot_manage_attendees(): void {
		$attendee_id = $this->create_checked_in_attendee();
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->get_response( $attendee_id );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 401, $response->get_error_data()['status'] );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__REST__V1__Endpoints__QR::get
	 */
	public function it_should_return_the_check_in_state_to_an_administrator(): void {
		$attendee_id = $this->create_checked_in_attendee();
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$response = $this->get_response( $attendee_id );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( [ 'id' => $attendee_id, 'checked_in' => '1' ], $response->get_data() );
	}

	/**
	 * @return int The ID of a checked-in RSVP attendee of a published post.
	 */
	private function create_checked_in_attendee(): int {
		$post_id = $this->factory()->post->create( [ 'post_status' => 'publish' ] );

		return $this->create_attendee_for_ticket( $this->create_rsvp_ticket( $post_id ), $post_id, [ 'checkin' => true ] );
	}

	/**
	 * @param int $attendee_id The attendee to read.
	 *
	 * @return WP_Error|WP_REST_Response The endpoint response.
	 */
	private function get_response( int $attendee_id ) {
		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr/' . $attendee_id );
		$request->set_param( 'id', $attendee_id );

		return tribe( 'tickets.rest-v1.endpoints.qr' )->get( $request );
	}
}
