<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Documentation;

use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Request_Body_Definition;
use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Ticket_Request_Body_Definition_Test extends WPTestCase {
	use SnapshotAssertions;

	/**
	 * Test the Ticket_Request_Body_Definition documentation output
	 */
	public function test_ticket_request_body_definition_json_snapshot() {
		$instance = new Ticket_Request_Body_Definition();
		$this->assertMatchesJsonSnapshot( wp_json_encode( $instance->get_documentation(), JSON_SNAPSHOT_OPTIONS ) );
	}
}