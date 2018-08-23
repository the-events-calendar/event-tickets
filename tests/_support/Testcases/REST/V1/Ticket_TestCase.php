<?php

namespace Tribe\Tickets\Test\Testcases\REST\V1;


use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Factories\RSVP_Attende as RSVP_Attendee_Factory;
use Tribe\Tickets\Test\Factories\REST\V1\Ticket_Response;

class Ticket_TestCase extends WPTestCase {

	function setUp() {
		parent::setUp();

		$this->factory()->rsvp_attendee = new RSVP_Attendee_Factory();
		$this->factory()->rsvp_ticket_response = new Ticket_Response();
	}
}
