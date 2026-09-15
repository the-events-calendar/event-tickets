<?php
/**
 * Exercises the QR check-in REST endpoint against Series Pass Attendees to prove the
 * checkin status reported back to the app, and detected on a repeat scan, reflects the
 * per-Occurrence clone Attendee that actually carries the checkin meta, not the original
 * Series-level Attendee.
 */

namespace Tribe\Tickets\REST\V1\Endpoints;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Attendees;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

class QR_Series_Pass_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Attendees::class;

	/**
	 * Creates a Series Pass Attendee and a single Event, part of the Series, that is the
	 * only checkin candidate for that Attendee.
	 *
	 * @return array{0: int, 1: int} The Series Pass Attendee ID and the Event ID.
	 */
	private function make_series_pass_attendee_with_one_occurrence(): array {
		$series_id = static::factory()->post->create( [
			'post_type' => Series_Post_Type::POSTTYPE,
		] );
		$pass_id = $this->create_tc_ticket( $series_id, 1, [ 'ticket_type' => Series_Passes::TICKET_TYPE ] );
		$this->create_order( [ $pass_id => 1 ] );

		$attendee_id = tribe_attendees()->where( 'event', $series_id )->first_id();
		$this->assertNotEmpty( $attendee_id, 'A Series Pass Attendee should have been created.' );

		$event_id = tribe_events()->set_args( [
			'title'      => 'Event in Series',
			'status'     => 'publish',
			'start_date' => '-30 minutes',
			'duration'   => 3 * HOUR_IN_SECONDS,
			'series'     => $series_id,
		] )->create()->ID;

		return [ $attendee_id, $event_id ];
	}

	private function build_qr_check_in_request( string $api_key, int $attendee_id, int $event_id ): WP_REST_Request {
		$commerce = Module::get_instance();

		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/qr' );
		$request->set_param( 'api_key', $api_key );
		$request->set_param( 'ticket_id', (string) $attendee_id );
		$request->set_param( 'security_code', get_post_meta( $attendee_id, $commerce->security_code, true ) );
		$request->set_param( 'event_id', $event_id );

		return $request;
	}

	/**
	 * @before
	 */
	public function set_up_qr_check_in(): void {
		// Become an administrator so the REST response includes the manage-access-only fields, e.g. `checked_in`.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		// Do not restrict check-ins to Events currently happening.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', false );

		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}

		$this->make_controller()->register();
	}

	/**
	 * @test
	 */
	public function should_report_the_persisted_checkin_status_after_checking_in_a_series_pass_attendee_via_qr(): void {
		$api_key = 'test-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		[ $attendee_id, $event_id ] = $this->make_series_pass_attendee_with_one_occurrence();

		$response = rest_get_server()->dispatch( $this->build_qr_check_in_request( $api_key, $attendee_id, $event_id ) );

		$this->assertEquals( 201, $response->status, 'The check-in should succeed.' );
		$this->assertArrayHasKey( 'attendee', $response->data );
		$this->assertArrayHasKey(
			'checked_in',
			$response->data['attendee'],
			'The check-in response should report the checkin status of the Attendee.'
		);
		$this->assertTrue(
			$response->data['attendee']['checked_in'],
			'The Attendee data returned by the check-in response should report the Attendee as checked in, ' .
			'not the stale status of the original Series-level Attendee, which is never checked in itself.'
		);
	}

	/**
	 * @test
	 */
	public function should_detect_a_duplicate_scan_of_a_checked_in_series_pass_attendee_via_qr(): void {
		$api_key = 'test-api-key';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		[ $attendee_id, $event_id ] = $this->make_series_pass_attendee_with_one_occurrence();

		$first_response = rest_get_server()->dispatch( $this->build_qr_check_in_request( $api_key, $attendee_id, $event_id ) );
		$this->assertEquals( 201, $first_response->status, 'The first check-in should succeed.' );

		$second_response = rest_get_server()->dispatch( $this->build_qr_check_in_request( $api_key, $attendee_id, $event_id ) );

		$this->assertEquals(
			403,
			$second_response->status,
			'Scanning the same Series Pass Attendee for the same Occurrence again should be rejected as a duplicate.'
		);
		$this->assertEquals( 'attendee_already_checked_in', $second_response->data['error'] );
	}
}
