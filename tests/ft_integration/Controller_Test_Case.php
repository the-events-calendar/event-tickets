<?php
/**
 * The base test case to test controllers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Provider\Controller;

/**
 * Class Controller_Test_Case.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Controller_Test_Case extends WPTestCase {
	/**
	 * A reference to the container used to create the controller and run the tests.
	 *
	 * @since TBD
	 *
	 * @var \Tribe__Container
	 */
	protected $test_container;

	/**
	 * Creates a controller instance.
	 *
	 * @since TBD
	 *
	 * @param string|null $controller_class The controller class to create an instance of, or `null` to build from
	 *                                      the `controller_class` property.
	 *
	 * @return Controller The controller instance, built on a dedicated testing Service Locator.
	 */
	protected function make_controller( string $controller_class = null ) {
		if ( ! ( $controller_class || property_exists( $this, 'controller_class' ) ) ) {
			throw new \RuntimeException( 'Each Controller test case must define a controller_class property.' );
		}

		$controller_class = $controller_class ?: $this->controller_class;

		/** @var Controller $original_controller */
		$original_controller = tribe( $controller_class );
		// Unregister the original controller to avoid actions and filters hooking twice.
		$original_controller->unregister();
		// Create a container that will provide the context for the controller cloning the original container.
		$this->test_container = clone tribe();
		// The controller will NOT have registered in this container.
		$this->test_container->setVar( $controller_class . '_registered', false );

		return new $controller_class( $this->test_container );
	}

	/**
	 * It should register and unregister correctly
	 *
	 * This method will run by default to make sure the Controller will clean up after itself upon unregistration.
	 *
	 * @test
	 */
	public function should_register_and_unregister_correctly(): void {
		$controller = $this->make_controller();

		global $wp_filters, $wp_actions;
		$wp_filters_before = $wp_filters;
		$wp_actions_before = $wp_actions;

		$controller->register();

		$controller->unregister();

		$this->assertEquals( $wp_filters_before, $wp_filters );
		$this->assertEquals( $wp_actions_before, $wp_actions );
	}
}