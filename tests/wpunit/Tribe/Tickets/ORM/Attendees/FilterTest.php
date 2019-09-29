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
		$this->assertEquals( $assertions['get_ids'], $attendees->get_ids(), $method );
		$this->assertEquals( $assertions['all'], $attendees->all(), $method );
		$this->assertEquals( $assertions['count'], $attendees->count(), $method );
		$this->assertEquals( $assertions['found'], $attendees->found(), $method );
	}
}