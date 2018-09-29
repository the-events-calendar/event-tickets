<?php

namespace Tribe\Tickets;

use Tribe__Tickets__Status__Manager as Manager;
use Tribe__Tickets__RSVP__Status_Manager as RSVPManager;

/**
 * Test Status RSVPManager
 *
 * @group   core
 *
 * @package Tribe__Tickets__RSVP__Status_Manager
 */
class RSVPManagerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( RSVPManager::class, $sut );
	}

	/**
	 * @return RSVPManager
	 */
	private function make_instance() {

		return new RSVPManager();
	}


	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_statues_names() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'status_names', $sut );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_status_classes() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'statuses', $sut );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_status_no() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'No', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['No']->count_not_going );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_status_yes() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Yes', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Yes']->count_completed );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_rsvp_dispatch_statues() {
		$this->assertSame( array(
			'yes',
		), Manager::get_instance()->return_statuses_by_action( 'attendee_dispatch', 'rsvp' ) );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_all_rsvp_statues() {
		$this->assertSame( array(
			'no',
			'yes',
		), Manager::get_instance()->return_statuses_by_action( 'all', 'rsvp' ) );
	}
}
