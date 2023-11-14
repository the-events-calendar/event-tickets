<?php

namespace Tribe\Tickets\Test\Testcases\REST\V1;


use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Factories\QR as QR_Factory;
use Tribe\Tickets\Test\Factories\REST\V1\QR_Response;

class QR_TestCase extends WPTestCase {

	function setUp() {
		parent::setUp();

		$this->factory()->ticket = new QR_Factory();
		$this->factory()->rest_ticket_response = new QR_Response();
	}
}
