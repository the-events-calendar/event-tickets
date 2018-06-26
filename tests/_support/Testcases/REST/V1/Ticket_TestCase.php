<?php

namespace Tribe\Tickets\Test\Testcases\REST\V1;


use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Factories\Ticket as Ticket_Factory;
use Tribe\Tickets\Test\Factories\REST\V1\Ticket_Response;

class Ticket_TestCase extends WPTestCase {

	function setUp() {
		parent::setUp();

		$this->factory()->ticket = new Ticket_Factory();
		$this->factory()->rest_ticket_response = new Ticket_Response();
	}
}
