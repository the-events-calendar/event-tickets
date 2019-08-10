<?php

namespace Tribe\Tickets\ORM\Attendees;

use Tribe\Tickets\Test\Commerce\ORMTestCase;

class FilterTest extends ORMTestCase {

	/**
	 * @dataProvider get_attendee_test_matrix
	 */
	public function test_attendees_orm_filters( $method ) {
		// Tell codeception which filter we are testing. You can see these by adding -vvv when running tests.
		codecept_debug( $method );

		list( $filter_name, $filter_arguments, $assertions ) = $this->$method();

		// Setup attendees.
		$attendees = tribe_attendees();

		// Enable found() calculations.
		$attendees->set_found_rows( true );

		// Do the filtering.
		$args = [
			$filter_name,
		];

		$args = array_merge( $args, $filter_arguments );

		$attendees->by( ...$args );

		// Assert that we get what we expected.
		$this->assertEquals( $assertions['get_ids'], $attendees->get_ids(), $filter_name );
		$this->assertEquals( $assertions['all'], $attendees->all(), $filter_name );
		$this->assertEquals( $assertions['count'], $attendees->count(), $filter_name );
		$this->assertEquals( $assertions['found'], $attendees->found(), $filter_name );
	}

}
