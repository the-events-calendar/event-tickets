<?php

use Codeception\TestCase\WPTestCase;

/**
 * Test that things are deprecated properly
 *
 * @group   core
 *
 * @package Tribe__Tickets__Main
 */
class Tribe_Deprecated_Test extends WPTestCase {

	public function setUp() {
		parent::setUp();
		add_action( 'deprecated_file_included', array( $this, 'deprecated_function_run' ) );
		add_filter( 'deprecated_file_trigger_error', '__return_false' );
	}

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

	public function deprecated_classes_5_6_5() {
		return [
			[ 'Tribe__Tickets__Cache__Cache_Interface' ],
			[ 'Tribe__Tickets__Cache__Abstract_Cache' ],
			[ 'Tribe__Tickets__Cache__Central' ],
			[ 'Tribe__Tickets__Cache__Transient_Cache' ],
		];
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

		$file_path = codecept_root_dir( "src/deprecated/{$class}.php" );
		$this->setExpectedDeprecated( $file_path );
		$this->assertTrue( class_exists( $class ), 'Class "' . $class . '" does not exist.' );
	}

	/**
	 * Test if a class exists was deprecated in 3.10 exists but is deprecated.
	 *
	 * @dataProvider deprecated_classes_3_10
	 */
	public function test_deprecated_classes_3_10( $class ) {
		if ( class_exists( $class, false ) ) {
			$this->markTestSkipped( $class . 'was already loaded' );
		}

		$file_path = codecept_root_dir( "src/deprecated/{$class}.php" );
		$this->setExpectedDeprecated( $file_path );
		$this->assertTrue( class_exists( $class ), 'Class "' . $class . '" does not exist.' );
	}

	/**
	 * @dataProvider deprecated_classes_5_6_5
	 */
	public function test_deprecated_classes_5_6_5( string $class ): void {
		if ( class_exists( $class, false ) || interface_exists( $class, false ) ) {
			$this->markTestSkipped( $class . 'was already loaded' );
		}

		$file_path = codecept_root_dir( "src/deprecated/{$class}.php" );
		$this->setExpectedDeprecated( $file_path );
		$this->assertTrue( class_exists( $class ) || interface_exists( $class ), 'Class "' . $class . '" does not exist.' );
	}
}
