<?php
/**
 * The base test case to test controllers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets\Test;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Provider\Controller;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Class Controller_Test_Case.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Controller_Test_Case extends WPTestCase {
	use With_Uopz;

	/**
	 * A reference to the container used to create the controller and run the tests.
	 *
	 * @since TBD
	 *
	 * @var \Tribe__Container
	 */
	protected $test_container;

	/**
	 * Creates a controller instance and sets up a dedicated Service Locator for it.
	 *
	 * In the context of the dedicated Service Locator the controller is not yet registered.
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
		// Register the test container in the test container.
		$this->test_container->singleton( get_class( $this->test_container ), $this->test_container );
		$this->test_container->singleton( \tad_DI52_Container::class, $this->test_container );
		// The controller will NOT have registered in this container.
		$this->test_container->setVar( $controller_class . '_registered', false );
		// Unset the previous, maybe, bound and resolved instance of the controller.
		unset( $this->test_container[ $controller_class ] );
		// Nothing should be bound in the container for the controller.
		$this->assertFalse( $this->test_container->has( $controller_class ) );

		// Due to the previous unset, the container will build this as a prototype.
		return $this->test_container->make( $controller_class );

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
		// Run this now to check the `controller_class` property is set.
		$controller = $this->make_controller();

		$added_filters    = [];
		$controller_class = $this->controller_class;

		$this->set_fn_return( 'add_filter', function (
			string $tag, callable $callback, int $priority = 10, int $args = 1
		) use (
			$controller_class, &$added_filters
		) {
			if ( is_array( $callback ) && $callback[0] instanceof $controller_class ) {
				$added_filters[] = [ $tag, $callback, $priority ];
			}
			add_filter( $tag, $callback, $priority, $args );
		}, true );
		$this->set_fn_return( 'remove_filter', function (
			string $tag, callable $callback, int $priority = 10
		) use (
			$controller_class, &$added_filters
		) {
			if (
				is_array( $callback )
				&& $callback[0] instanceof $controller_class
			) {
				$found = array_search( [ $tag, $callback, $priority ], $added_filters, true );
				if ( $found !== false ) {
					unset( $added_filters[ $found ] );
				}
			}
			remove_filter( $tag, $callback, $priority );
		}, true );

		$controller->register();
		$controller->unregister();

		$this->assertCount(
			0,
			$added_filters,
			'The controller should have removed all its filters and actions: '
			. PHP_EOL . json_encode( $added_filters, JSON_PRETTY_PRINT )
		);
	}
}