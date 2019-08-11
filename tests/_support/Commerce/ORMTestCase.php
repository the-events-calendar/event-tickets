<?php

namespace Tribe\Tickets\Test\Commerce;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class ORMTestCase
 * @package Tribe\Tickets\Test\Commerce
 */
class ORMTestCase extends Test_Case {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * The array of generated data.
	 *
	 * @var array
	 */
	protected $test_data = [];

	public function setUp() {
		parent::setUp();

		$this->factory()->event = new Event();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = $paypal->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Setup test data here.
		$this->setup_test_data();
	}

	/**
	 * Get test matrix with all the assertions filled out.
	 *
	 * Method naming:
	 * "Match" means the filter finds what we expect it to with the created data.
	 * "Mismatch" means the filter should not find anything because we don't have a matching ID to find anything for.
	 */
	public function get_attendee_test_matrix() {
		// Event
		yield [ 'get_test_matrix_event_match' ];
		yield [ 'get_test_matrix_event_mismatch' ];
		// Event Not In
		yield [ 'get_test_matrix_event_not_in_match' ];
		yield [ 'get_test_matrix_event_not_in_mismatch' ];
	}

	/**
	 * Get test matrix for Event match.
	 */
	public function get_test_matrix_event_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				$this->get_event_id( 0 ),
			],
			// Assertions to make.
			[
				'get_ids' => [
					$this->get_attendee_id( 0 ),
					$this->get_attendee_id( 1 ),
					$this->get_attendee_id( 2 ),
					$this->get_attendee_id( 3 ),
				],
				'all'     => [
					get_post( $this->get_attendee_id( 0 ) ),
					get_post( $this->get_attendee_id( 1 ) ),
					get_post( $this->get_attendee_id( 2 ) ),
					get_post( $this->get_attendee_id( 3 ) ),
				],
				'count'   => 4,
				'found'   => 4,
			],
		];
	}

	/**
	 * Get test matrix for Event mismatch.
	 */
	public function get_test_matrix_event_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				$this->get_event_id( 1 ),
			],
			// Assertions to make.
			[
				'get_ids' => [],
				'all'     => [],
				'count'   => 0,
				'found'   => 0,
			],
		];
	}

	/**
	 * Get test matrix for Event Not In match.
	 */
	public function get_test_matrix_event_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				$this->get_event_id( 1 ),
			],
			// Assertions to make.
			[
				'get_ids' => [
					$this->get_attendee_id( 0 ),
					$this->get_attendee_id( 1 ),
					$this->get_attendee_id( 2 ),
					$this->get_attendee_id( 3 ),
				],
				'all'     => [
					get_post( $this->get_attendee_id( 0 ) ),
					get_post( $this->get_attendee_id( 1 ) ),
					get_post( $this->get_attendee_id( 2 ) ),
					get_post( $this->get_attendee_id( 3 ) ),
				],
				'count'   => 4,
				'found'   => 4,
			],
		];
	}

	/**
	 * Get test matrix for Event Not In mismatch.
	 */
	public function get_test_matrix_event_not_in_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				$this->get_event_id( 0 ),
			],
			// Assertions to make.
			[
				'get_ids' => [],
				'all'     => [],
				'count'   => 0,
				'found'   => 0,
			],
		];
	}

	protected function get_event_id( $index ) {
		if ( isset( $this->test_data['events'][ $index ] ) ) {
			return $this->test_data['events'][ $index ];
		}

		return 0;
	}

	protected function get_attendee_id( $index ) {
		if ( isset( $this->test_data['attendees'][ $index ] ) ) {
			return $this->test_data['attendees'][ $index ];
		}

		return 0;
	}

	protected function get_user_id( $index ) {
		if ( isset( $this->test_data['users'][ $index ] ) ) {
			return $this->test_data['users'][ $index ];
		}

		return 0;
	}

	protected function get_rsvp_id( $index ) {
		if ( isset( $this->test_data['rsvps'][ $index ] ) ) {
			return $this->test_data['rsvps'][ $index ];
		}

		return 0;
	}

	protected function get_paypal_tickets_id( $index ) {
		if ( isset( $this->test_data['paypal_tickets'][ $index ] ) ) {
			return $this->test_data['paypal_tickets'][ $index ];
		}

		return 0;
	}

	/**
	 * Setup list of test data.
	 */
	protected function setup_test_data() {
		$test_data = [
			'users'          => [], // 2 total (1 author an Event, 1 as an Attendee of the Event)
			'events'         => [], // 2 total (1 with an Author and Attendees, 1 with Author ID of zero and no Attendees)
			'rsvps'          => [], // 4 total (1 having 2 Attendees, 3 having no Attendees)
			'paypal_tickets' => [], // 4 total (1 having 2 Attendees, 3 having no Attendees)
			'attendees'      => [], // 4 total (first 2 are RSVP, next 2 are PayPal)
		];

		// Create test user 1. Author of one of the two Events.
		$user_id_one = $this->factory()->user->create();

		$test_data['users'][] = $user_id_one;

		// Create test user 2. Attendee/Purchaser of one of each kind of ticket.
		$test_data['users'][] = $user_id_two = $this->factory()->user->create();

		// Create test event 1.
		$event_id = $this->factory()->event->create( [
			'post_title'  => 'Test event 1',
			'post_author' => $user_id_one,
		] );

		$test_data['events'][] = $event_id;

		// Create test event 2.
		$test_data['events'][] = $this->factory()->event->create( [
			'post_title'  => 'Test event 2',
			'post_author' => 0,
		] );

		// Create test RSVP ticket
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );

		// Add User2 Attendee and non-User Attendee to this RSVP ticket
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id, [ 'user_id' => $user_id_two ] );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $event_id );

		// Create more RSVP tickets that do not have any attendees
		$test_data['rsvps'] = array_merge( [ $rsvp_ticket_id ], $this->create_many_rsvp_tickets( 3, $event_id ) );

		// Create test PayPal ticket
		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 5 );

		// Add User2 Attendee and non-User Attendee to this PayPal ticket
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id, [ 'user_id' => $user_id_two ] );
		$test_data['attendees'][] = $this->create_attendee_for_ticket( $paypal_ticket_id, $event_id );

		// Create more PayPal tickets that do not have any attendees
		$test_data['paypal_tickets'] = array_merge( [ $paypal_ticket_id ], $this->create_many_paypal_tickets( 3, $event_id ) );

		// Save test data to reference.
		$this->test_data = $test_data;
	}
}