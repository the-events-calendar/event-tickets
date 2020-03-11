<?php

namespace Tribe\Tickets;

use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Tickets_HandlerTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {
		// before
		parent::setUp();
		// your set up methods here
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Tickets_Handler::class, $sut );
	}

	/**
	 * @return Tickets_Handler
	 */
	private function make_instance() {
		return new Tickets_Handler();
	}

	/**
	 * @test
	 * it should get the default ticket max purchase
	 */
	public function it_should_get_default_ticket_max_purchase() {
		$sut = $this->make_instance();

		// @todo Setup ticket and $ticket_id with 100+ capacity.
		$ticket_id = 0;

		$max_quantity = $sut->get_ticket_max_purchase( $ticket_id );

		$this->assertEquals( 100, $max_quantity );
	}

	/**
	 * @test
	 * it should get the lesser available ticket max purchase
	 */
	public function it_should_get_lesser_available_ticket_max_purchase() {
		$sut = $this->make_instance();

		// @todo Setup ticket and $ticket_id with less than 100 capacity.
		$capacity  = 50;
		$ticket_id = 0;

		$max_quantity = $sut->get_ticket_max_purchase( $ticket_id );

		$this->assertEquals( $capacity, $max_quantity );
	}
}
