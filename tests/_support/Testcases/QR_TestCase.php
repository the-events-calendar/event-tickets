<?php

namespace Tribe\Tickets\Test\Testcases;


use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Factories\QR;
use Tribe\Tests\Data;
use Tribe__Tickets__Data_API as Data_API;

class QR_TestCase extends WPTestCase {

	/**
	 * @var array An array of bound implementations we could replace during tests.
	 */
	protected $backups = [];

	/**
	 * @var array An associative array of backed up alias and bound implementations.
	 */
	protected $implementation_backups = [];

	function setUp() {
		parent::setUp();

		$this->factory()->attendee = new QR();

		foreach ( $this->backups as $alias ) {
			$this->implementation_backups[ $alias ] = tribe( $alias );
		}

		// Add Tickets Commerce.
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ \TEC\Tickets\Commerce\Module::class ] = \TEC\Tickets\Commerce::ABBR;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		foreach ( $this->implementation_backups as $alias => $value ) {
			tribe_singleton( $alias, $value );
		}
		parent::tearDown();
	}

	/**
	 * Converts attendee data in the REST response format to the format consumed by EA.
	 *
	 * @param array|object $attendee An input attendee data
	 *
	 * @return array The attendee data converted to the format read by EA.
	 */
	protected function convert_rest_attendee_data_to_ea_format( $attendee ) {
		$attendee = new Data( $attendee, false );

		$conversion_map = [
			'id'         => $attendee['id'],
			'checked_in' => $attendee['id'],
		];

		$attendee = array_filter( $conversion_map );

		return $attendee;
	}
}
