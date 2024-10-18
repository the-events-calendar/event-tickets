<?php

declare( strict_types=1 );

namespace Tribe\TEC\Tickets\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Order_Modifiers\Values\Precision_Value;

class Currency_Value_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function default_values_used_with_create_formatted_correctly() {
		Currency_Value::set_defaults(
			'¢',
			'.',
			',',
			'after'
		);

		$currency_value = Currency_Value::create( new Precision_Value( 100 ) );
		$this->assertEquals( '100,00¢', $currency_value->get() );
		$this->assertEquals( '100,00¢', (string) $currency_value );

		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '1.000,00¢', $currency_value->get() );
		$this->assertEquals( '1.000,00¢', (string) $currency_value );

		Currency_Value::set_defaults();

		$currency_value = Currency_Value::create( new Precision_Value( 100 ) );
		$this->assertEquals( '$100.00', $currency_value->get() );
		$this->assertEquals( '$100.00', (string) $currency_value );

		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '$1,000.00', $currency_value->get() );
		$this->assertEquals( '$1,000.00', (string) $currency_value );
	}
}
