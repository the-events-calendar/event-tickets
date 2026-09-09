<?php
/**
 * Exercises the Attendee archive REST endpoint against Series Pass Attendees to prove that a request
 * authorized by API key alone, and therefore carrying no WP user - which is every Event Tickets Plus
 * App request - is served the per-Occurrence clone Attendee that holds the check-in status for the
 * Occurrence it asked about, and can tell that clone apart from a regular Attendee.
 */

namespace Tribe\Tickets\REST\V1\Endpoints;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Attendees;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

class Attendee_Archive_Series_Pass_Test extends Controller_Test_Case {
	use Ticket_Maker;
	use Order_Maker;

	protected string $controller_class = Attendees::class;

	/**
	 * The API key the requests are authorized with.
	 *
	 * @var string
	 */
	private string $api_key = 'test-api-key';

	/**
	 * @before
	 */
	public function set_up_api_key_request(): void {
		tribe_update_option( 'tickets-plus-qr-options-api-key', $this->api_key );

		// The API key is read off the request vars, not off the `WP_REST_Request`.
		$_GET['api_key']     = $this->api_key;
		$_REQUEST['api_key'] = $this->api_key;

		if ( ! did_action( 'rest_api_init' ) ) {
			do_action( 'rest_api_init' );
		}
	}

	/**
	 * @after
	 */
	public function tear_down_api_key_request(): void {
		unset( $_GET['api_key'], $_REQUEST['api_key'] );
	}

	/**
	 * Creates a Series Pass Attendee and a recurring Event, part of the Series, with 3 Occurrences.
	 *
	 * @return array{0: int, 1: array<int>} The Series Pass Attendee ID and the Occurrence provisional IDs.
	 */
	private function make_series_pass_attendee_with_occurrences(): array {
		$series_id = static::factory()->post->create(
			[
				'post_type' => Series_Post_Type::POSTTYPE,
			]
		);
		$pass_id   = $this->create_tc_ticket( $series_id, 1, [ 'ticket_type' => Series_Passes::TICKET_TYPE ] );
		$this->create_order( [ $pass_id => 1 ] );

		$attendee_id = tribe_attendees()->where( 'event', $series_id )->first_id();
		$this->assertNotEmpty( $attendee_id, 'A Series Pass Attendee should have been created.' );

		$recurring_event_id = tribe_events()->set_args(
			[
				'title'      => 'Recurring Event in Series',
				'status'     => 'publish',
				'start_date' => '-1 hour',
				'duration'   => 3 * HOUR_IN_SECONDS,
				'recurrence' => 'RRULE:FREQ=DAILY;COUNT=3',
				'series'     => $series_id,
			]
		)->create()->ID;

		$provisional_ids = Occurrence::where( 'post_id', '=', $recurring_event_id )
			->map( fn( Occurrence $occurrence ) => (int) $occurrence->provisional_id );

		$this->assertCount( 3, $provisional_ids, 'The recurring Event should have 3 Occurrences.' );

		return [ (int) $attendee_id, array_values( $provisional_ids ) ];
	}

	/**
	 * Fetches the Attendee archive for an Occurrence the way the App does: by provisional ID, authorized
	 * by API key, with no logged in user.
	 *
	 * @param int $provisional_id The provisional ID of the Occurrence to fetch the Attendees of.
	 *
	 * @return array<array<string,mixed>> The Attendee entries in the response.
	 */
	private function fetch_occurrence_attendees( int $provisional_id ): array {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/tribe/tickets/v1/attendees' );
		$request->set_param( 'api_key', $this->api_key );
		$request->set_param( 'post_id', $provisional_id );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 25 );
		$request->set_param( 'order_status', 'public' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'The Attendee archive request should succeed.' );

		return $response->get_data()['attendees'];
	}

	/**
	 * @test
	 */
	public function should_list_the_original_series_pass_attendee_for_an_occurrence_not_yet_checked_into(): void {
		[ $attendee_id, $provisional_ids ] = $this->make_series_pass_attendee_with_occurrences();

		$this->make_controller()->register();

		foreach ( $provisional_ids as $provisional_id ) {
			$attendees = $this->fetch_occurrence_attendees( $provisional_id );

			$this->assertCount( 1, $attendees, "Occurrence {$provisional_id} should list the Series Pass Attendee." );
			$this->assertEquals( $attendee_id, $attendees[0]['id'] );
			$this->assertFalse( $attendees[0]['checked_in'] );
			$this->assertArrayNotHasKey(
				'clone_of',
				$attendees[0],
				'An Attendee that is not a clone should carry no `clone_of` entry.'
			);
		}
	}

	/**
	 * The check-in status of a Series Pass Attendee lives on the clone Attendee made for the Occurrence,
	 * so the archive has to return that clone. It used to be dropped: the clone is related to its
	 * Occurrence by provisional ID, which has no `wp_posts` row for the related post status filter to
	 * match, and that filter applies to any request without a WP user.
	 *
	 * @test
	 */
	public function should_list_the_per_occurrence_clone_attendee_once_it_has_been_checked_into(): void {
		[ $attendee_id, $provisional_ids ] = $this->make_series_pass_attendee_with_occurrences();

		$this->make_controller()->register();

		// Check the Attendee into the last Occurrence, the one furthest outside the QR check-in window.
		$checked_in_id  = end( $provisional_ids );
		$not_checked_in = reset( $provisional_ids );

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		tribe_tickets_get_ticket_provider( $attendee_id )->checkin( $attendee_id, false, $checked_in_id );

		$checked_in_attendees = $this->fetch_occurrence_attendees( $checked_in_id );

		$this->assertCount( 1, $checked_in_attendees, 'The checked into Occurrence should list one Attendee.' );
		$this->assertTrue(
			$checked_in_attendees[0]['checked_in'],
			'The Occurrence the Attendee was checked into should report the Attendee as checked in.'
		);
		$this->assertEquals(
			$attendee_id,
			$checked_in_attendees[0]['clone_of'],
			'The clone Attendee should point back at the original Series Pass Attendee.'
		);
		$this->assertNotEquals(
			$attendee_id,
			$checked_in_attendees[0]['id'],
			'The clone Attendee has its own post ID.'
		);

		$other_attendees = $this->fetch_occurrence_attendees( $not_checked_in );

		$this->assertCount( 1, $other_attendees, 'Every other Occurrence should still list the Attendee.' );
		$this->assertEquals( $attendee_id, $other_attendees[0]['id'] );
		$this->assertFalse(
			$other_attendees[0]['checked_in'],
			'Checking in for one Occurrence should not check the Attendee into the others.'
		);
	}
}
