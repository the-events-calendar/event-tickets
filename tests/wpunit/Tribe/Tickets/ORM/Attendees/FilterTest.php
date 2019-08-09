<?php

namespace Tribe\Tickets\ORM\Attendees;

use Tribe\Tickets\Test\Commerce\ORMTestCase;

class FilterTest extends ORMTestCase {

	/**
	 * @param $filter_name
	 * @param $filter_arguments
	 * @param $assertions
	 *
	 * @dataProvider get_attendee_test_matrix
	 */
	public function test_attendees_orm_filters( $filter_name, $filter_arguments, $assertions ) {
		// Tell codeception what filter we are testing. You can see these by adding -vvv when running tests.
		codecept_debug( $filter_arguments );

		// Setup attendees.
		$attendees = tribe_attendees();

		// Enable found() calculations.
		$attendees->set_found_rows( true );

		// Do the filtering.
		$attendees->by( $filter_name, ...$filter_arguments );

		// Assert that we get what we expected.
		$this->assertEquals( $assertions['get_ids'], $attendees->get_ids(), $filter_name );
		//$this->assertEquals( $assertions['all'], $attendees->all(), $filter_name );
		//$this->assertEquals( $assertions['count'], $attendees->count(), $filter_name );
		//$this->assertEquals( $assertions['found'], $attendees->found(), $filter_name );
	}

}
