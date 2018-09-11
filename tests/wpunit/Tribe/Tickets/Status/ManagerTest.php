<?php

namespace Tribe\Tickets;

use Tribe__Tickets__Status__Manager as Manager;

/**
 * Test Status Manager
 *
 * @group   core
 *
 * @package Tribe__Tickets__Status__Manager
 */
class ManagerTest extends \Codeception\TestCase\WPTestCase {

	public function setUp() {

		// before
		parent::setUp();

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		add_filter( 'tribe_tickets_get_modules', function ( array $modules ) {
			$modules[ \Tribe__Tickets__Commerce__PayPal__Main::class ] = 'tribe-commerce';

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
	 * @since TBD
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Manager::class, $sut );
	}

	/**
	 * @return Manager
	 */
	private function make_instance() {
		tribe_update_option( 'ticket-paypal-enable', true );

		return new Manager();
	}

	/**
	 * todo active test once RSVP and Tribe Commerce Managers are created
	 *
	 * @test
	 * @since TBD
	 */
	/*	public function it_has_manage_class_keys_for_rsvp_and_tribe_commerce() {
			$this->assertArrayHasKey( 'rsvp', Manager::get_instance()->get_status_managers() );
			$this->assertArrayHasKey( 'tribe-commerce', Manager::get_instance()->get_status_managers() );
		}*/

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_rsvp_active_module() {
		print_r('it_has_rsvp_active_module');
		print_r(Manager::get_instance()->get_active_modules());
		$this->assertArrayHasKey( 'Tribe__Tickets__RSVP', Manager::get_instance()->get_active_modules() );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_tribe_commerce_active_module() {

		//run setup again to get the active modules that will include Tribe Commerce
		Manager::get_instance()->setup();
		print_r('it_has_tribe_commerce_active_module');
		print_r(Manager::get_instance()->get_active_modules());
		$this->assertArrayHasKey( 'Tribe__Tickets__Commerce__PayPal__Main', Manager::get_instance()->get_active_modules() );
	}
}
