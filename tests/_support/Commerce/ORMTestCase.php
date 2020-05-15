<?php

namespace Tribe\Tickets\Test\Commerce;

/**
 * Class ORMTestCase
 *
 * @package Tribe\Tickets\Test\Commerce
 */
class ORMTestCase extends Test_Case {

	/**
	 * {@inheritDoc}
	 */
	public $should_setup_test_data = true;

	/**
	 * Get test matrix with all the assertions filled out.
	 *
	 * Method naming:
	 * "Match" means the filter finds what we expect it to with the created data.
	 * "Mismatch" is for filtering ones we expect to match an empty array (in most cases), such as matching the
	 * attendees for an RSVP ticket without any. It is to confirm we don't get results when we shouldn't.
	 *
	 * @see \Tribe__Tickets__Attendee_Repository::__construct() These tests are in the schema's order added
	 *                                                          so we know we got them all.
	 */
	public function get_attendee_test_matrix() {
		// Event
		yield 'event match single' => [ 'get_test_matrix_single_event_match' ];
		yield 'event match multi' => [ 'get_test_matrix_multi_event_match' ];
		yield 'event mismatch single' => [ 'get_test_matrix_single_event_mismatch' ];
		yield 'event mismatch multi' => [ 'get_test_matrix_multi_event_mismatch' ];
		// Event Not In
		yield 'event not in match single' => [ 'get_test_matrix_single_event_not_in_match' ];
		yield 'event not in match multi' => [ 'get_test_matrix_multi_event_not_in_match' ];
		yield 'event not in mismatch single' => [ 'get_test_matrix_single_event_not_in_mismatch' ];
		yield 'event not in mismatch multi' => [ 'get_test_matrix_multi_event_not_in_mismatch' ];

		// Ticket
		yield 'ticket match single' => [ 'get_test_matrix_single_ticket_match' ];
		yield 'ticket match multi' => [ 'get_test_matrix_multi_ticket_match' ];
		yield 'ticket mismatch single' => [ 'get_test_matrix_single_ticket_mismatch' ];
		yield 'ticket mismatch multi' => [ 'get_test_matrix_multi_ticket_mismatch' ];
		// Ticket Not In
		yield 'ticket not in match single' => [ 'get_test_matrix_single_ticket_not_in_match' ];
		yield 'ticket not in match multi' => [ 'get_test_matrix_multi_ticket_not_in_match' ];
		yield 'ticket not in mismatch single' => [ 'get_test_matrix_single_ticket_not_in_mismatch' ];
		yield 'ticket not in mismatch multi' => [ 'get_test_matrix_multi_ticket_not_in_mismatch' ];

		// Order
		yield 'order match single' => [ 'get_test_matrix_single_order_match' ];
		yield 'order match multi' => [ 'get_test_matrix_multi_order_match' ];
		yield 'order mismatch single' => [ 'get_test_matrix_single_order_mismatch' ];
		yield 'order mismatch multi' => [ 'get_test_matrix_multi_order_mismatch' ];
		// Order Not In
		yield 'order not in match single' => [ 'get_test_matrix_single_order_not_in_match' ];
		yield 'order not in match multi' => [ 'get_test_matrix_multi_order_not_in_match' ];
		yield 'order not in mismatch single' => [ 'get_test_matrix_single_order_not_in_mismatch' ];
		yield 'order not in mismatch multi' => [ 'get_test_matrix_multi_order_not_in_mismatch' ];

		// Purchaser Name
		yield 'purchaser_name match single' => [ 'get_test_matrix_single_purchaser_name_match' ];
		yield 'purchaser_name match multi' => [ 'get_test_matrix_multi_purchaser_name_match' ];
		yield 'purchaser_name mismatch single' => [ 'get_test_matrix_single_purchaser_name_mismatch' ];
		yield 'purchaser_name mismatch multi' => [ 'get_test_matrix_multi_purchaser_name_mismatch' ];
		// Purchaser Name Not In
		yield 'purchaser_name not in match single' => [ 'get_test_matrix_single_purchaser_name_not_in_match' ];
		yield 'purchaser_name not in match multi' => [ 'get_test_matrix_multi_purchaser_name_not_in_match' ];
		yield 'purchaser_name not in mismatch single' => [ 'get_test_matrix_single_purchaser_name_not_in_mismatch' ];
		yield 'purchaser_name not in mismatch multi' => [ 'get_test_matrix_multi_purchaser_name_not_in_mismatch' ];
		// Purchaser Name Like (does not support Multi)
		yield 'purchaser_name like match single' => [ 'get_test_matrix_single_purchaser_name_like_match' ];
		yield 'purchaser_name like mismatch single' => [ 'get_test_matrix_single_purchaser_name_like_mismatch' ];

		// RSVP
		yield 'rsvp match single' => [ 'get_test_matrix_single_rsvp_match' ];
		yield 'rsvp match multi' => [ 'get_test_matrix_multi_rsvp_match' ];
		yield 'rsvp mismatch single' => [ 'get_test_matrix_single_rsvp_mismatch' ];
		yield 'rsvp mismatch multi' => [ 'get_test_matrix_multi_rsvp_mismatch' ];
		// RSVP Not In
		yield 'rsvp not in match single' => [ 'get_test_matrix_single_rsvp_not_in_match' ];
		yield 'rsvp not in match multi' => [ 'get_test_matrix_multi_rsvp_not_in_match' ];
		yield 'rsvp not in mismatch single' => [ 'get_test_matrix_single_rsvp_not_in_mismatch' ];
		yield 'rsvp not in mismatch multi' => [ 'get_test_matrix_multi_rsvp_not_in_mismatch' ];

		// Tribe Commerce PayPal
		yield 'paypal match single' => [ 'get_test_matrix_single_paypal_match' ];
		yield 'paypal match multi' => [ 'get_test_matrix_multi_paypal_match' ];
		yield 'paypal mismatch single' => [ 'get_test_matrix_single_paypal_mismatch' ];
		yield 'paypal mismatch multi' => [ 'get_test_matrix_multi_paypal_mismatch' ];
		// Tribe Commerce PayPal Not In
		yield 'paypal not in match single' => [ 'get_test_matrix_single_paypal_not_in_match' ];
		yield 'paypal not in match multi' => [ 'get_test_matrix_multi_paypal_not_in_match' ];
		yield 'paypal not in mismatch single' => [ 'get_test_matrix_single_paypal_not_in_mismatch' ];
		yield 'paypal not in mismatch multi' => [ 'get_test_matrix_multi_paypal_not_in_mismatch' ];

		// User
		yield 'user match single' => [ 'get_test_matrix_single_user_match' ];
		yield 'user match multi' => [ 'get_test_matrix_multi_user_match' ];
		yield 'user mismatch single' => [ 'get_test_matrix_single_user_mismatch' ];
		yield 'user mismatch multi' => [ 'get_test_matrix_multi_user_mismatch' ];
		// User Not In
		yield 'user not in match single' => [ 'get_test_matrix_single_user_not_in_match' ];
		yield 'user not in match multi' => [ 'get_test_matrix_multi_user_not_in_match' ];
		yield 'user not in mismatch single' => [ 'get_test_matrix_single_user_not_in_mismatch' ];
		yield 'user not in mismatch multi' => [ 'get_test_matrix_multi_user_not_in_mismatch' ];

		// Price Paid, Paid Min, and Paid Max
		yield 'price match single' => [ 'get_test_matrix_single_price_match' ];
		yield 'price mismatch single' => [ 'get_test_matrix_single_price_mismatch' ];
		// @todo ORM broken: yield 'price minimum match single' => [ 'get_test_matrix_single_price_min_match' ];
		// @todo ORM broken: yield 'price minimum mismatch single' => [ 'get_test_matrix_single_price_min_mismatch' ];
		// @todo ORM broken: yield 'price maximum match single' => [ 'get_test_matrix_single_price_max_match' ];
		// @todo ORM broken: yield 'price maximum mismatch single' => [ 'get_test_matrix_single_price_max_mismatch' ];
		// Price Paid, Paid Min, and Paid Max Not In
		////yield 'price not in match single' => [ 'get_test_matrix_single_price_not_in_match' ];
		////yield 'price not in mismatch single' => [ 'get_test_matrix_single_price_not_in_mismatch' ];
		////yield 'price not in minimum match single' => [ 'get_test_matrix_single_price_min_not_in_match' ];
		////yield 'price not in minimum mismatch single' => [ 'get_test_matrix_single_price_min_not_in_mismatch' ];
		////yield 'price not in maximum match single' => [ 'get_test_matrix_single_price_max_not_in_match' ];
		////yield 'price not in maximum mismatch single' => [ 'get_test_matrix_single_price_max_not_in_mismatch' ];
	}

	/**
	 * EVENTS
	 */

	/**
	 * Get test matrix for Event match.
	 */
	public function get_test_matrix_single_event_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_event_1'] ),
		];
	}

	/**
	 * Get test matrix for multiple Event match.
	 */
	public function get_test_matrix_multi_event_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 0 ),
					$this->get_event_id( 1 ),
					$this->get_event_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_all'] ),
		];
	}

	/**
	 * Get test matrix for Event mismatch.
	 */
	public function get_test_matrix_single_event_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 1 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Events mismatch.
	 */
	public function get_test_matrix_multi_event_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 1 ),
					$this->get_event_id( 3 ),
				]
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Event Not In match.
	 */
	public function get_test_matrix_single_event_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 1 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_all'] ),
		];
	}

	/**
	 * Get test matrix for multiple Events Not In match.
	 */
	public function get_test_matrix_multi_event_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 1 ),
					$this->get_event_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_all'] ),
		];
	}

	/**
	 * Get test matrix for Event Not In mismatch.
	 */
	public function get_test_matrix_single_event_not_in_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_event_3'] ),
		];
	}

	/**
	 * Get test matrix for multiple Events Not In mismatch.
	 */
	public function get_test_matrix_multi_event_not_in_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'event__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_event_id( 0 ),
					$this->get_event_id( 2 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * TICKETS
	 */

	/**
	 * Get test matrix for Ticket match.
	 */
	public function get_test_matrix_single_ticket_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->test_data['tickets_products_rsvp'][0]
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_rsvp_1'] ),
		];
	}

	/**
	 * Get test matrix for multiple Ticket match.
	 */
	public function get_test_matrix_multi_ticket_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->test_data['tickets_products_rsvp']
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_rsvp'] ),
		];
	}

	/**
	 * Get test matrix for Ticket mismatch.
	 */
	public function get_test_matrix_single_ticket_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_fake_ids( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Tickets mismatch.
	 */
	public function get_test_matrix_multi_ticket_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				$this->get_fake_ids(),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Ticket Not In match.
	 */
	public function get_test_matrix_single_ticket_not_in_match() {
		$expected = array_merge(
			$this->test_data['attendees_rsvp'],
			$this->test_data['attendees_paypal_5']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->test_data['tickets_products_paypal'][0],
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Tickets Not In match.
	 */
	public function get_test_matrix_multi_ticket_not_in_match() {
		$expected = array_merge(
			$this->test_data['attendees_rsvp'],
			$this->test_data['attendees_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_ids(),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Ticket Not In mismatch.
	 */
	public function get_test_matrix_single_ticket_not_in_mismatch() {
		$expected = array_merge(
			$this->test_data['attendees_rsvp'],
			$this->test_data['attendees_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_ids( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Tickets Not In mismatch.
	 */
	public function get_test_matrix_multi_ticket_not_in_mismatch() {
		$filter = array_merge(
			$this->test_data['tickets_products_rsvp'],
			$this->test_data['tickets_products_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				$filter,
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * ORDERS
	 */

	/**
	 * Get test matrix for Order match.
	 */
	public function get_test_matrix_single_order_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'order',
			// Filter arguments to use.
			[
				[
					$this->test_data['tickets_orders_rsvp'][0]
				]
			],
			// Assertions to make.
			$this->get_assertions_array( (array) $this->test_data['attendees_rsvp'][0] ),
		];
	}

	/**
	 * Get test matrix for multiple Order match.
	 */
	public function get_test_matrix_multi_order_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'order',
			// Filter arguments to use.
			[
				$this->test_data['tickets_orders_rsvp']
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_rsvp'] ),
		];
	}

	/**
	 * Get test matrix for Order mismatch.
	 */
	public function get_test_matrix_single_order_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'order',
			// Filter arguments to use.
			[
				$this->get_fake_ids( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Orders mismatch.
	 */
	public function get_test_matrix_multi_order_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'order',
			// Filter arguments to use.
			[
				$this->get_fake_ids(),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Order Not In match.
	 */
	public function get_test_matrix_single_order_not_in_match() {
		$expected = array_merge(
			$this->test_data['attendees_paypal'],
			$this->test_data['attendees_rsvp']
		);

		array_shift( $expected );

		return [
			// Repository
			'default',
			// Filter name.
			'order__not_in',
			// Filter arguments to use.
			[
				[
					$this->test_data['tickets_orders_paypal'][0],
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Orders Not In match.
	 */
	public function get_test_matrix_multi_order_not_in_match() {
		$expected = array_merge(
			$this->test_data['attendees_rsvp'],
			$this->test_data['attendees_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'order__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_ids(),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Order Not In mismatch.
	 */
	public function get_test_matrix_single_order_not_in_mismatch() {
		$expected = array_merge(
			$this->test_data['attendees_paypal'],
			$this->test_data['attendees_rsvp']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'order__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_ids( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Orders Not In mismatch.
	 */
	public function get_test_matrix_multi_order_not_in_mismatch() {
		$filter = array_merge(
			$this->test_data['tickets_orders_rsvp'],
			$this->test_data['tickets_orders_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'order__not_in',
			// Filter arguments to use.
			[
				$filter,
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * PURCHASER NAMES
	 */

	/**
	 * Get test matrix for Purchaser Name match.
	 */
	public function get_test_matrix_single_purchaser_name_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2 on Event1
			$this->get_attendee_id( 8 ), // User2 on Event1
			$this->get_attendee_id( 9 ), // User2 on Event3
		];

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name',
			// Filter arguments to use.
			[
				[
					$this->test_data['tickets_purchaser_names_rsvp'][0],
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Purchaser Name match.
	 */
	public function get_test_matrix_multi_purchaser_name_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2 on Event1
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 8 ), // User2 on Event1
			$this->get_attendee_id( 9 ), // User2 on Event3
		];

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name',
			// Filter arguments to use.
			[
				[
					$this->test_data['user_2_details']['first_name']
					. ' '
					. $this->test_data['user_2_details']['last_name'],

					$this->test_data['user_4_details']['first_name']
					. ' '
					. $this->test_data['user_4_details']['last_name'],
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Purchaser Name mismatch.
	 */
	public function get_test_matrix_single_purchaser_name_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name',
			// Filter arguments to use.
			[
				$this->get_fake_names( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Purchaser Names mismatch.
	 */
	public function get_test_matrix_multi_purchaser_name_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name',
			// Filter arguments to use.
			[
				$this->get_fake_names(),
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Purchaser Name Not In match.
	 */
	public function get_test_matrix_single_purchaser_name_not_in_match() {
		$expected = [
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 2 ), // Guest
			$this->get_attendee_id( 3 ), // Guest
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 6 ), // Guest
			$this->get_attendee_id( 7 ), // Guest
		];

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__not_in',
			// Filter arguments to use.
			[
				[
					$this->test_data['user_2_details']['first_name']
					. ' '
					. $this->test_data['user_2_details']['last_name'],
				]
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Purchaser Names Not In match.
	 */
	public function get_test_matrix_multi_purchaser_name_not_in_match() {
		$expected = array_merge(
			$this->test_data['attendees_rsvp'],
			$this->test_data['attendees_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_names(),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Purchaser Name Not In mismatch.
	 */
	public function get_test_matrix_single_purchaser_name_not_in_mismatch() {
		$expected = array_merge(
			$this->test_data['attendees_paypal'],
			$this->test_data['attendees_rsvp']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__not_in',
			// Filter arguments to use.
			[
				$this->get_fake_names( 0 ),
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Purchaser Names Not In mismatch.
	 */
	public function get_test_matrix_multi_purchaser_name_not_in_mismatch() {
		$filter = array_merge(
			$this->test_data['tickets_purchaser_names_rsvp'],
			$this->test_data['tickets_purchaser_names_paypal']
		);

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__not_in',
			// Filter arguments to use.
			[
				$filter,
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Purchaser Name Like match.
	 */
	public function get_test_matrix_single_purchaser_name_like_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2 on Event1
			$this->get_attendee_id( 8 ), // User2 on Event1
			$this->get_attendee_id( 9 ), // User2 on Event3
		];

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__like',
			// Filter arguments to use.
			[
				$this->test_data['user_2_details']['first_name'] . '%',
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for Purchaser Name Like mismatch.
	 */
	public function get_test_matrix_single_purchaser_name_like_mismatch() {
		$name = $this->get_fake_names( 0 );

		return [
			// Repository
			'default',
			// Filter name.
			'purchaser_name__like',
			// Filter arguments to use.
			[
				$name[0] . '%',
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * RSVPS
	 */

	/**
	 * Get test matrix for RSVP match.
	 */
	public function get_test_matrix_single_rsvp_match() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_rsvp_1'] ),
		];
	}

	/**
	 * Get test matrix for multiple RSVPs match.
	 */
	public function get_test_matrix_multi_rsvp_match() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
					$this->get_rsvp_ticket_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_rsvp'] ),
		];
	}

	/**
	 * Get test matrix for RSVP mismatch.
	 */
	public function get_test_matrix_single_rsvp_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 1 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple RSVPs mismatch.
	 */
	public function get_test_matrix_multi_rsvp_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 1 ),
					$this->get_rsvp_ticket_id( 2 ),
					$this->get_rsvp_ticket_id( 3 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for RSVP Not In match.
	 */
	public function get_test_matrix_single_rsvp_not_in_match() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_event_3'] ),
		];
	}

	/**
	 * Get test matrix for multiple RSVPs Not In match.
	 */
	public function get_test_matrix_multi_rsvp_not_in_match() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
					$this->get_rsvp_ticket_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for RSVP Not In mismatch.
	 */
	public function get_test_matrix_single_rsvp_not_in_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_event_3'] ),
		];
	}

	/**
	 * Get test matrix for multiple RSVPs Not In mismatch.
	 */
	public function get_test_matrix_multi_rsvp_not_in_mismatch() {
		return [
			// Repository
			'rsvp',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_rsvp_ticket_id( 0 ),
					$this->get_rsvp_ticket_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Tribe Commerce
	 */

	/**
	 * Get test matrix for Tribe Commerce PayPal match.
	 */
	public function get_test_matrix_single_paypal_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal_1'] ),
		];
	}

	/**
	 * Get test matrix for multiple Tribe Commerce PayPal match.
	 */
	public function get_test_matrix_multi_paypal_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 0 ),
					$this->get_paypal_tickets_id( 4 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal'] ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal mismatch.
	 */
	public function get_test_matrix_single_paypal_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 1 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Tribe Commerce PayPal mismatch.
	 */
	public function get_test_matrix_multi_paypal_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 1 ),
					$this->get_paypal_tickets_id( 2 ),
					$this->get_paypal_tickets_id( 3 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal Not In match.
	 */
	public function get_test_matrix_single_paypal_not_in_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 1 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal'] ),
		];
	}

	/**
	 * Get test matrix for multiple Tribe Commerce PayPal Not In match.
	 */
	public function get_test_matrix_multi_paypal_not_in_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 1 ),
					$this->get_paypal_tickets_id( 2 ),
					$this->get_paypal_tickets_id( 3 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal'] ),
		];
	}

	/**
	 * Get test matrix for Tribe Commerce PayPal Not In mismatch.
	 */
	public function get_test_matrix_single_paypal_not_in_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 0 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal_5'] ),
		];
	}

	/**
	 * Get test matrix for multiple Tribe Commerce PayPal Not In mismatch.
	 */
	public function get_test_matrix_multi_paypal_not_in_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'ticket__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_paypal_tickets_id( 1 ),
					$this->get_paypal_tickets_id( 2 ),
					$this->get_paypal_tickets_id( 3 ),
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal'] ),
		];
	}

	/**
	 * USERS
	 */

	/**
	 * Get test matrix for User match. 2nd user is first attendee.
	 */
	public function get_test_matrix_single_user_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2 on Event1
			$this->get_attendee_id( 8 ), // User2 on Event1
			$this->get_attendee_id( 9 ), // User2 on Event3
		];

		return [
			// Repository
			'default',
			// Filter name.
			'user',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 1 ), // User2
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple User match.
	 *
	 * 2nd user is first attendee. 3rd user is 2nd and 3rd attendee. 4th user is 4th attendee.
	 */
	public function get_test_matrix_multi_user_match() {
		$expected = [
			$this->get_attendee_id( 0 ), // User2
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 8 ), // User2 on Event1
			$this->get_attendee_id( 9 ), // User2 on Event3
		];

		return [
			// Repository
			'default',
			// Filter name.
			'user',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 1 ), // User2
					$this->get_user_id( 2 ), // User3
					$this->get_user_id( 3 ), // User4
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for User mismatch.
	 */
	public function get_test_matrix_single_user_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'user',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 0 ), // User1
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for multiple Users mismatch.
	 */
	public function get_test_matrix_multi_user_mismatch() {
		return [
			// Repository
			'default',
			// Filter name.
			'user',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 0 ), // User1
					$this->get_user_id( 4 ), // User5
				],
			],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for User Not In match.
	 */
	public function get_test_matrix_single_user_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'user__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 0 ), // User1
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_all'] ),
		];
	}

	/**
	 * Get test matrix for multiple Users Not In match.
	 */
	public function get_test_matrix_multi_user_not_in_match() {
		return [
			// Repository
			'default',
			// Filter name.
			'user__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 0 ), // User1
					$this->get_user_id( 4 ), // User5
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_all'] ),
		];
	}

	/**
	 * Get test matrix for User Not In mismatch.
	 *
	 * Get all the attendees that weren't purchased by User2.
	 */
	public function get_test_matrix_single_user_not_in_mismatch() {
		$expected = [
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 2 ), // Guest
			$this->get_attendee_id( 3 ), // Guest
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 5 ), // User4
			$this->get_attendee_id( 6 ), // Guest
			$this->get_attendee_id( 7 ), // Guest
		];

		return [
			// Repository
			'default',
			// Filter name.
			'user__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 1 ), // User2
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Get test matrix for multiple Users Not In mismatch.
	 *
	 * Get all the attendees that weren't purchased by User2 nor User4.
	 */
	public function get_test_matrix_multi_user_not_in_mismatch() {
		$expected = [
			$this->get_attendee_id( 1 ), // User3
			$this->get_attendee_id( 2 ), // Guest
			$this->get_attendee_id( 3 ), // Guest
			$this->get_attendee_id( 4 ), // User3
			$this->get_attendee_id( 6 ), // Guest
			$this->get_attendee_id( 7 ), // Guest
		];

		return [
			// Repository
			'default',
			// Filter name.
			'user__not_in',
			// Filter arguments to use.
			[
				[
					$this->get_user_id( 1 ), // User2
					$this->get_user_id( 3 ), // User4
				],
			],
			// Assertions to make.
			$this->get_assertions_array( $expected ),
		];
	}

	/**
	 * Price Paid
	 */

	/**
	 * Get test matrix for Price Paid match.
	 */
	public function get_test_matrix_single_price_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price',
			// Filter arguments to use.
			[ 5 ],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal_1'] ),
		];
	}

	/**
	 * Get test matrix for Price Paid match.
	 */
	public function get_test_matrix_single_price_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price',
			// Filter arguments to use.
			[ 15 ],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for minimum Price Paid match.
	 */
	public function get_test_matrix_single_price_min_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price_min',
			// Filter arguments to use.
			[ 6 ],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal_5'] ),
		];
	}

	/**
	 * Get test matrix for minimum Price Paid mismatch.
	 */
	public function get_test_matrix_single_price_min_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price_min',
			// Filter arguments to use.
			[ 15 ],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Get test matrix for maximum Price Paid match.
	 */
	public function get_test_matrix_single_price_max_match() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price_max',
			// Filter arguments to use.
			[ 6 ],
			// Assertions to make.
			$this->get_assertions_array( $this->test_data['attendees_paypal_1'] ),
		];
	}

	/**
	 * Get test matrix for maximum Price Paid mismatch.
	 */
	public function get_test_matrix_single_price_max_mismatch() {
		return [
			// Repository
			'tribe-commerce',
			// Filter name.
			'price_max',
			// Filter arguments to use.
			[ 2 ],
			// Assertions to make.
			$this->get_assertions_array( [] ),
		];
	}

	/**
	 * Given an array of post IDs, get the assertions array that flows through to the test.
	 *
	 * @param int|array $post_ids
	 *
	 * @return array
	 */
	protected function get_assertions_array( $post_ids ) {
		if ( ! is_array( $post_ids ) ) {
			$post_ids = (array) $post_ids;
		}

		// Assume 'count' and 'found' will always be the same, since ORM defaults to unlimited (-1) results.
		$total = count( $post_ids );

		return [
			'get_ids' => $post_ids,
			'all'     => array_map( 'get_post', $post_ids ),
			'count'   => $total,
			'found'   => $total,
		];
	}
}