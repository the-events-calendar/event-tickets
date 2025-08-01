<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Documentation;

use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Ticket_Definition_Test extends WPTestCase {
	use SnapshotAssertions;

	/**
	 * Test the Ticket_Definition documentation output
	 */
	public function test_ticket_definition_json_snapshot() {
		$instance = new Ticket_Definition();
		$this->assertMatchesJsonSnapshot( wp_json_encode( $instance->get_documentation(), JSON_SNAPSHOT_OPTIONS ) );
	}
}