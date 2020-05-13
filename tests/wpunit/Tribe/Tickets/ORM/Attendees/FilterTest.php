<?php

namespace Tribe\Tickets\ORM\Attendees;

use Tribe\Tickets\Test\Commerce\ORMTestCase;

/**
 * Class FilterTest
 * @package Tribe\Tickets\ORM\Attendees
 *
 * @see \tribe_attendees() What all these tests are for, for the following classes:
 * @see \Tribe__Tickets__Attendee_Repository Default.
 * @see \Tribe__Tickets__Repositories__Attendee__RSVP RSVP.
 * @see \Tribe__Tickets__Repositories__Attendee__Commerce Tribe Commerce.
 */
class FilterTest extends ORMTestCase {

	/**
	 * @dataProvider get_attendee_test_matrix
	 */
	public function test_attendees_orm_filters( $method ) {
		list( $repository, $filter_name, $filter_arguments, $assertions ) = $this->$method();

		// Setup attendees.
		$attendees = tribe_attendees( $repository );

		// Enable found() calculations.
		$attendees->set_found_rows( true );

		// Do the filtering.
		$args = array_merge( [ $filter_name ], $filter_arguments );

		$attendees->by( ...$args );

		// Assert that we get what we expected.

		/**
		 * Same as `all()` except only an array of Post IDs, not full Post objects.
		 * Is affected by pagination, but ORM defaults to unlimited.
		 *
		 * We use the 'canonicalize' argument to compare arrays as sorted so the order returned doesn't matter.
		 *
		 * @see \Tribe__Repository::get_ids()
		 */
		$this->assertEquals( $assertions['get_ids'], $attendees->get_ids(), $method, 0.0, 10, true );

		/**
		 * The total number of posts found matching the current query parameters.
		 * Is affected by pagination, but ORM defaults to unlimited.
		 *
		 * We use the 'canonicalize' argument to compare arrays as sorted so the order returned doesn't matter.
		 *
		 * @see \Tribe__Repository::all() Runs get_posts() then format_item().
		 * @see \WP_Query::get_posts()
		 * @see \Tribe__Repository::format_item()
		 */
		$this->assertEquals( $assertions['all'], $attendees->all(), $method, 0.0, 10, true );

		/**
		 * @see \Tribe__Repository::count() WP_Query's `post_count`:
		 *      The number of posts being displayed. Is affected by pagination but ORM defaults to unlimited.
		 */
		$this->assertEquals( $assertions['count'], $attendees->count(), $method );

		/**
		 * The total number of posts found matching the current query parameters.
		 * Is NOT affected by pagination.
		 *
		 * @see \Tribe__Repository::found() WP_Query's `found_posts`.
		 */
		$this->assertEquals( $assertions['found'], $attendees->found(), $method );
	}
}