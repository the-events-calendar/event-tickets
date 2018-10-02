<?php

namespace Tribe\Tickets;

use Tribe__Tickets__Status__Manager as Manager;
use Tribe__Tickets__Commerce__PayPal__Status_Manager as TTPManager;

/**
 * Test Status TTPManager
 *
 * @group   core
 *
 * @package Tribe__Tickets__Commerce__PayPal__Status_Manager
 */
class TTPManagerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 * @since TBD
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( TTPManager::class, $sut );
	}

	/**
	 * @return TTPManager
	 */
	private function make_instance() {

		return new TTPManager();
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
		$this->assertArrayHasKey( 'Not_Going', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Not_Going']->count_not_going );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_status_yes() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Going', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Going']->count_completed );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_ttp_dispatch_statues() {
		$this->assertSame( array(
			'yes',
		), Manager::get_instance()->return_statuses_by_action( 'attendee_dispatch', 'ttp' ) );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_all_ttp_statues() {
		$this->assertSame( array(
			'yes',
			'no',
		), Manager::get_instance()->return_statuses_by_action( 'all', 'ttp' ) );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_label_and_stock_reduction_for_status() {

		$options = Manager::get_instance()->return_status_options( 'ttp' );

		$this->assertSame( 'Going', $options['yes']['label'] );
		$this->assertSame( 1, $options['yes']['decrease_stock_by'] );
		$this->assertSame( 'Not Going', $options['no']['label'] );
		$this->assertSame( 0, $options['no']['decrease_stock_by'] );

	}
}
