<?php
/**
 * Test that things are deprecated properly
 *
 * @group   core
 *
 * @package Tribe__Tickets__Main
 */
class Tribe_Deprecated_Test extends Tribe__Events__WP_UnitTestCase {

	public function deprecated_classes_4_0() {
		return array(
			array( 'Tribe__Events__Tickets__Attendees_Table' ),
			array( 'Tribe__Events__Tickets__Metabox' ),
			array( 'Tribe__Events__Tickets__Ticket_Object' ),
			array( 'Tribe__Events__Tickets__Tickets' ),
			array( 'Tribe__Events__Tickets__Tickets_Pro' ),
		);
	}

	public function deprecated_classes_3_10() {
		return array(
			array( 'TribeEventsTicketObject' ),
			array( 'TribeEventsTickets' ),
			array( 'TribeEventsTicketsAttendeesTable' ),
			array( 'TribeEventsTicketsMetabox' ),
			array( 'TribeEventsTicketsPro' ),
		);
	}

	/**
	 * Test if a class exists was deprecated in 4.0 exists but is deprecated.
	 *
	 * @dataProvider deprecated_classes_4_0
	 */
	public function test_deprecated_class_4_0( $class ) {
		if ( class_exists( $class, false ) ) {
			$this->markTestSkipped( $class . 'was already loaded' );
		}

		$this->expected_deprecated_file[] = 'src/deprecated/' . $class . '.php';
		$this->assertTrue( class_exists( $class ), 'Class "' . $class . '" does not exist.' );
	}

	/**
	 * Test if a class exists was deprecated in 3.10 exists but is deprecated.
	 *
	 * 	 * @dataProvider deprecated_classes_3_10
	 */
	public function test_deprecated_classes_3_10( $class ) {
		if ( class_exists( $class, false ) ) {
			$this->markTestSkipped( $class . 'was already loaded' );
		}

		$this->expected_deprecated_file[] = 'src/deprecated/' . $class . '.php';
		$this->assertTrue( class_exists( $class ), 'Class "' . $class . '" does not exist.' );
	}
}
