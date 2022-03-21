<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Status\Completed;
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
	 *
	 * @test
	 * @since 4.10
	 */
	public function it_has_manage_class_keys_for_rsvp_and_tribe_commerce() {
		$this->assertArrayHasKey( 'rsvp', Manager::get_instance()->get_status_managers() );
		$this->assertArrayHasKey( 'tpp', Manager::get_instance()->get_status_managers() );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_rsvp_active_module() {
		$this->assertArrayHasKey( 'Tribe__Tickets__RSVP', Manager::get_instance()->get_active_modules() );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_tribe_commerce_active_module() {
		//run setup again to get the active modules that will include Tribe Commerce
		Manager::get_instance()->setup();
		$this->assertArrayHasKey( 'Tribe__Tickets__Commerce__PayPal__Main', Manager::get_instance()->get_active_modules() );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_returns_tickets_commerce_completed_statuses() {
		$statues = Manager::get_instance()->get_completed_status_by_provider_name( Module::class );

		$completed = tribe( Completed::class );
		$this->assertTrue( in_array( $completed->get_slug(), $statues ), 'Tickets Commerce completed status slug is not found.' );
		$this->assertTrue( in_array( $completed->get_name(), $statues ), 'Tickets Commerce completed status name is not found.' );
	}
}
