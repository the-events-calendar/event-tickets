<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deprecated_Autoloader;

class Deprecated_AutoloaderTest extends WPTestCase {

	/**
	 * @test
	 * @dataProvider deprecated_class_provider
	 */
	public function it_should_autoload_deprecated_classes( string $class ) {
		$this->setExpectedDeprecated( $class );
		$this->assertTrue( class_exists( $class ) );
	}

	/**
	 * @test
	 * @dataProvider deprecated_interface_provider
	 */
	public function it_should_autoload_deprecated_interaces( string $class ) {
		$this->setExpectedDeprecated( $class );
		$this->assertTrue( interface_exists( $class ) );
	}

	public function deprecated_class_provider() {
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Base_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Integer_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Legacy_Value_Factory::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Positive_Integer_Value::class ];
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value::class ];
	}

	public function deprecated_interface_provider() {
		yield [ \TEC\Tickets\Commerce\Order_Modifiers\Values\Value_Interface::class ];
	}

	/**
	 * @before
	 */
	public function register_autoloader() {
		Deprecated_Autoloader::get_instance()->register();
	}

	/**
	 * @after
	 */
	public function unregister_autoloader() {
		Deprecated_Autoloader::get_instance()->unregister();
	}
}
