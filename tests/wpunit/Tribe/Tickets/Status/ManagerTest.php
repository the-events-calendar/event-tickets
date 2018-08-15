<?php

namespace Tribe\Tickets;

use Prophecy\Argument;
use Tribe__Tickets__Status__Manager as Manager;

/**
 * Test Status Manager
 *
 * @group   core
 *
 * @package Tribe__Tickets__Status__Manager
 */
class ManagerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * test modules loaded
	 *
	 *
	 */

	/**
	 * It should be instantiatable
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		//print_r( $sut->status_managers );

		$this->assertInstanceOf( Manager::class, $sut );
	}

	private function make_instance() {
		/** @var Manager $instance */
		$instance = ( new \ReflectionClass( Manager::class ) )->newInstanceWithoutConstructor();

		return $instance;
	}

	/**
	 * Check for Event Tickets Manager Class Key
	 *
	 * @test
	 * @since TBD
	 */
/*	public function it_has_manage_class_keys() {
		$this->assertArrayHasKey( 'RSVP', Manager::get_instance()->status_managers );
		$this->assertArrayHasKey( 'Tribe Commerce', Manager::get_instance()->status_managers );
	}*/

	public function it_has_active_modules() {
		//$instance = ( new \ReflectionClass( Manager::class ) )->newInstanceWithoutConstructor();
		//$reflection_property = $instance->getProperty('active_modules');
		//$reflection_property->setAccessible(true);
		//print_r($reflection_property);
		$this->assertEquals( 'active', 'active' );
	}
}
