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

	public function setUp() {

		// before
		parent::setUp();

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		/**
		 * Enable TTP
		 */
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
	}

	public function dont_die() {
		// no-op, go on
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( TTPManager::class, $sut );
	}

	/**
	 * @return TTPManager
	 */
	private function make_instance() {
		tribe_update_option( 'ticket-paypal-enable', true );

		return new TTPManager();
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
	public function it_has_status_completed() {

		$sut = $this->make_instance();

		$this->assertArrayHasKey( 'Completed', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Completed']->count_completed );
	}

	public function it_has_status_denied() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Denied', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Denied']->incomplete );
	}

	public function it_has_status_not_completed() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Not_Completed', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Not_Completed']->incomplete );
	}

	public function it_has_status_pending() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Pending', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Pending']->count_sales );
	}

	public function it_has_status_refunded() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Refunded', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Refunded']->count_refunded );
	}

	public function it_has_status_undefined() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Undefined', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Undefined']->incomplete );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_all_ttp_statues() {

		//run setup again to get the active modules that will include Tribe Commerce
		Manager::get_instance()->setup();
		$this->assertSame( array(
			'completed',
			'denied',
			'not-completed',
			'pending-payment',
			'refunded',
			'reversed',
			'undefined',
		), Manager::get_instance()->get_statuses_by_action( 'all', 'tpp' ) );
	}

}
