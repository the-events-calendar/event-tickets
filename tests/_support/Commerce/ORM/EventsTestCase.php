<?php

namespace Tribe\Tickets\Test\Commerce\ORM;

use Tribe\Tickets\Test\Commerce\ORMTestCase;

/**
 * Class EventsTestCase
 *
 * @package Tribe\Tickets\Test\Commerce\ORM
 */
class EventsTestCase extends ORMTestCase {

	public function setUp() {
		parent::setUp();

		// Use normal formatter.
		add_filter( 'tribe_repository_events_format_item', function( $formatted, $id ) { return get_post( $id ); }, 10, 2 );
	}

	/**
	 * Get test matrix with all the assertions filled out.
	 *
	 * Method naming:
	 * "Match" means the filter finds what we expect it to with the created data.
	 * "Mismatch" is for filtering ones we expect to match an empty array (in most cases), such as matching the
	 * attendees for an RSVP ticket without any. It is to confirm we don't get results when we shouldn't.
	 *
	 * @see \Tribe__Tickets__Event_Repository::__construct() These tests are in the schema's order added
	 *                                                          so we know we got them all.
	 */
	public function get_event_test_matrix() {
		// RSVP Attendees.
		yield 'rsvp attendee match single' => [ 'get_test_matrix_single_rsvp_attendee_match' ];
		yield 'rsvp attendee match multi' => [ 'get_test_matrix_multi_rsvp_attendee_match' ];

		// RSVP Attendees NOT IN.
		yield 'rsvp attendee not in match single' => [ 'get_test_matrix_single_rsvp_attendee_not_in_match' ];
		yield 'rsvp attendee not in match multi' => [ 'get_test_matrix_multi_rsvp_attendee_not_in_match' ];

		// Tribe Commerce Attendees.
		yield 'paypal attendee match single' => [ 'get_test_matrix_single_paypal_attendee_match' ];
		yield 'paypal attendee match multi' => [ 'get_test_matrix_multi_paypal_attendee_match' ];

		// Tribe Commerce Attendees NOT IN.
		yield 'paypal attendee not in match single' => [ 'get_test_matrix_single_paypal_attendee_not_in_match' ];
		yield 'paypal attendee not in match multi' => [ 'get_test_matrix_multi_paypal_attendee_not_in_match' ];

		// Attendee Users.
		yield 'attendee user match single' => [ 'get_test_matrix_single_attendee_user_match' ];
		yield 'attendee user match multi' => [ 'get_test_matrix_multi_attendee_user_match' ];

		// Attendee Users NOT IN.
		// This is not yet working, it needs more debugging to determine why it's not functional yet.
		//yield 'attendee user not in match single' => [ 'get_test_matrix_single_attendee_user_not_in_match' ];
		//yield 'attendee user not in match multi' => [ 'get_test_matrix_multi_attendee_user_not_in_match' ];
	}

	/**
	 * Get test matrix for RSVP attendee match.
	 */
	public function get_test_matrix_single_rsvp_attendee_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->get_event_id( 0 ) ),
		];
	}

	/**
	 * Get test matrix for multiple RSVP attendees match.
	 */
	public function get_test_matrix_multi_rsvp_attendee_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 0 ),
					// From event 3.
					$this->get_attendee_id( 9 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 0 ), $this->get_event_id( 2 ) ] ),
		];
	}

	/**
	 * Get test matrix for RSVP attendee Not In match.
	 */
	public function get_test_matrix_single_rsvp_attendee_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 2 ), $this->get_event_id( 3 ) ] ),
		];
	}

	/**
	 * Get test matrix for multiple RSVP attendees Not In match.
	 */
	public function get_test_matrix_multi_rsvp_attendee_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 0 ),
					// From event 3.
					$this->get_attendee_id( 9 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 3 ) ] ),
		];
	}

	/**
	 * Get test matrix for PayPal attendee match.
	 */
	public function get_test_matrix_single_paypal_attendee_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->get_event_id( 0 ) ),
		];
	}

	/**
	 * Get test matrix for multiple PayPal attendees match.
	 */
	public function get_test_matrix_multi_paypal_attendee_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 4 ),
					// From event 3.
					$this->get_attendee_id( 9 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 0 ), $this->get_event_id( 2 ) ] ),
		];
	}

	/**
	 * Get test matrix for PayPal attendee Not In match.
	 */
	public function get_test_matrix_single_paypal_attendee_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 2 ), $this->get_event_id( 3 ) ] ),
		];
	}

	/**
	 * Get test matrix for multiple PayPal attendees Not In match.
	 */
	public function get_test_matrix_multi_paypal_attendee_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_attendee_id( 4 ),
					// From event 3.
					$this->get_attendee_id( 9 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 3 ) ] ),
		];
	}

	/**
	 * Get test matrix for attendee user match.
	 */
	public function get_test_matrix_single_attendee_user_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee_user',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_user_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->get_event_id( 0 ) ),
		];
	}

	/**
	 * Get test matrix for multiple attendee users match.
	 */
	public function get_test_matrix_multi_attendee_user_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee_user',
			// Filter arguments to use.
			[
				[
					// From event 1 and 3.
					$this->get_user_id( 1 ),
					// From event 1.
					$this->get_user_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 0 ), $this->get_event_id( 2 ) ] ),
		];
	}

	/**
	 * Get test matrix for attendee user Not In match.
	 */
	public function get_test_matrix_single_attendee_user_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee_user__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1.
					$this->get_user_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 2 ), $this->get_event_id( 3 ) ] ),
		];
	}

	/**
	 * Get test matrix for multiple attendee users Not In match.
	 */
	public function get_test_matrix_multi_attendee_user_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'attendee_user__not_in',
			// Filter arguments to use.
			[
				[
					// From event 1 and 3.
					$this->get_user_id( 1 ),
					// From event 1.
					$this->get_user_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [ $this->get_event_id( 1 ), $this->get_event_id( 3 ) ] ),
		];
	}
}
