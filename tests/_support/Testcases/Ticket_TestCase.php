<?php

namespace Tribe\Tickets\Test\Testcases;


use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Data;
use Tribe\Tickets\Test\Factories\RSVPAttendee;

class Ticket_TestCase extends WPTestCase {
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

		$this->factory()->rsvp_attendee = new RSVPAttendee();

		foreach ( $this->backups as $alias ) {
			$this->implementation_backups[ $alias ] = tribe( $alias );
		}
	}

	public function tearDown() {
		foreach ( $this->implementation_backups as $alias => $value ) {
			tribe_singleton( $alias, $value );
		}
		parent::tearDown();
	}

	/**
	 * Converts ticket data in the REST response format to the format consumed by EA.
	 *
	 * @param array|object $ticket An input ticket data
	 *
	 * @return array The ticket data converted to the format read by EA.
	 */
	protected function convert_rest_ticket_data_to_ea_format( $ticket ) {
		$ticket = new Data( $ticket, false );

		$conversion_map = [
			'title'              => $ticket['title'],
			'id'                 => $ticket['id'],
		];

		$ticket = array_filter( $conversion_map );

		return $ticket;
	}
}
