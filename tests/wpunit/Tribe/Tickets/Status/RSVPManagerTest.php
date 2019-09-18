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
	 * @since 4.10
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
	 * @since 4.10
	 */
	public function it_has_statues_names() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'status_names', $sut );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_status_classes() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'statuses', $sut );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_status_no() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Not_Going', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Not_Going']->count_not_going );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_status_yes() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Going', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Going']->count_completed );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_rsvp_dispatch_statues() {
		$this->assertSame( array(
			'yes',
		), Manager::get_instance()->get_statuses_by_action( 'attendee_dispatch', 'rsvp' ) );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_all_rsvp_statues() {
		$this->assertSame( array(
			'yes',
			'no',
		), Manager::get_instance()->get_statuses_by_action( 'all', 'rsvp' ) );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_label_and_stock_reduction_for_status() {

		$options = Manager::get_instance()->get_status_options( 'rsvp' );

		$this->assertSame( 'Going', $options['yes']['label'] );
		$this->assertSame( 1, $options['yes']['decrease_stock_by'] );
		$this->assertSame( 'Not going', $options['no']['label'] );
		$this->assertSame( 0, $options['no']['decrease_stock_by'] );

	}
}
