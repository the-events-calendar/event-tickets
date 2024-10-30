<?php
namespace TEC\Tickets\Commerce\Settings\Payment;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Class to test Currency Format Settings.
 *
 * @since 5.6.2
 *
 * @covers \TEC\Tickets\Commerce\Utils\Currency
 */
class FormatTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @test
	 */
	public function test_default_format_settings_is_correct() {
		$default_currency = tec_tickets_commerce_currency_code();
		$this->assertEquals( Currency::$currency_code_fallback, $default_currency );
		$this->assertEquals( Currency::$currency_code_fallback_symbol, tec_tickets_commerce_currency_symbol() );
		$this->assertEquals( Currency::$currency_code_thousands_separator, tec_tickets_commerce_currency_thousands_separator() );
		$this->assertEquals( Currency::$currency_code_decimal_separator, tec_tickets_commerce_currency_decimal_separator() );
		$this->assertEquals( Currency::$currency_code_number_of_decimals, tec_tickets_commerce_currency_number_of_decimals() );
	}

	/**
	 * @test
	 */
	public function test_default_currency_format_is_correct() {
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '&#x24;1,000.00', $formatted_amount );
	}

	/**
	 * @test
	 */
	public function test_changing_currency_symbol_is_working() {
		tribe_update_option( Settings::$option_currency_code, 'EUR' );
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '&#8364;1.000,00', $formatted_amount );
	}

	/**
	 * @test
	 */
	public function test_changing_currency_decimal_separator_is_working() {
		tribe_update_option( Settings::$option_currency_decimal_separator, '--' );
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '&#8364;1.000--00', $formatted_amount );
	}

	/**
	 * @test
	 */
	public function test_changing_currency_thousands_separator_is_working() {
		tribe_update_option( Settings::$option_currency_thousands_separator, '|' );
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '&#8364;1|000--00', $formatted_amount );
	}

	/**
	 * @test
	 */
	public function test_changing_number_of_decimals_is_working() {
		tribe_update_option( Settings::$option_currency_number_of_decimals, 4 );
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '&#8364;1|000--0000', $formatted_amount );
	}

	/**
	 * @test
	 */
	public function test_changing_currency_symbol_position_is_working() {
		tribe_update_option( Settings::$option_currency_position, 'postfix' );
		$formatted_amount = \TEC\Tickets\Commerce\Utils\Value::create( 1000 )->get_currency();
		$this->assertEquals( '1|000--0000&#8364;', $formatted_amount );
	}
}
