<?php
namespace TEC\Tickets\Commerce\Utils;

class PriceTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @dataProvider totals_provider
	 */
	public function test_total_is_acurate( $values, $decimal, $thousands_sep, $result, $result_int ) {
		$total = Price::total( $values, $decimal, $thousands_sep );
		$total_as_int = Price::to_integer( $total, $decimal, $thousands_sep );

		$this->assertEquals( $result_int, $total_as_int );
	}

	/**
	 * @dataProvider totals_provider
	 */
	public function test_clear_formatting( $values, $decimal, $thousands_sep, $result, $result_int ) {
		$total_as_int = Price::to_integer( $result, $decimal, $thousands_sep );

		$this->assertEquals( $result_int, $total_as_int );
	}

	/**
	 * @dataProvider totals_provider
	 */
	public function test_total_formatting_is_proper( $values, $decimal, $thousands_sep, $result ) {
		$total = Price::total( $values, $decimal, $thousands_sep );

		$this->assertEquals( $result, $total );
	}

	/**
	 * @dataProvider sub_totals_provider
	 */
	public function test_sub_total_is_accurate( $value, $quantity, $decimal, $thousands_sep, $result ) {
		$sub_total = Price::sub_total( $value, $quantity, $decimal, $thousands_sep );

		$expected_as_int = Price::to_integer( $result, $decimal, $thousands_sep );
		$sub_total_as_int = Price::to_integer( $sub_total, $decimal, $thousands_sep );

		$this->assertEquals( $expected_as_int, $sub_total_as_int );
	}

	/**
	 * @dataProvider sub_totals_provider
	 */
	public function test_sub_total_formatting_is_proper( $value, $quantity, $decimal, $thousands_sep, $result ) {
		$sub_total = Price::sub_total( $value, $quantity, $decimal, $thousands_sep );

		$this->assertEquals( $result, $sub_total );
	}

	/**
	 * Provider item structure:
	 * [ $values, $decimal, $thousand_sep, $formatted_total, $int_total ]
	 *
	 * @return array[]
	 */
	public function totals_provider() {
		return [
			[ [ '0.2', '2.75' ], '.', ',', '2.95', 295 ], // formatted string with decimals
			[ [ 1, 2 ], '.', ',', '3.00', 300 ], // integers
			[ [ 34, 56 ], '.', ',', '90.00', 9000 ], // integers
			[ [ 789, '012' ], '.', ',', '801.00', 80100 ], // integer + unformatted string w/ leading zero
			[ [ 3456, '7890' ], '.', ',', '11,346.00', 1134600 ], // integer + unformatted string without decimals
			[ [ '12,345', 67890 ], '.', ',', '80,235.00', 8023500 ], // formatted text without decimals
			[ [ '12,345.983', 67890 ], '.', ',', '80,235.98', 8023598 ], // formatted text with 3 decimals
			[ [ '1.234,56', '7.890,12' ], ',', '.', '9.124,68', 912468 ], // comma as decimal separator and dot as thousands separator
			[ [ '34 567.89', '0123456' ], '.', ' ', '158 023.89', 15802389 ], // space as thousands separator
			[ [ '789\'012.34', '56789012' ], '.', '\'', '57\'578\'024.34', 5757802434 ], // apostrophe as thousands separator
			[ [ '789012 34', '56789012' ], ' ', '&lt;', '57&lt;578&lt;024 34', 5757802434 ], // space as decimal separator and html entity as thousands separator
			[ [ '78901234.', '56789012' ], '.', ',', '135,690,246.00', 13569024600 ], // decimal at the end of the string means string.00
			[ [ '.78', '567890.12' ], '.', ',', '567,890.90', 56789090 ], // decimal at the start of the string means 0.string
			[ [ '99.00', '99.00' ], '.', ',', '198.00', 19800 ], // Copy of snapshot test
		];
	}

	/**
	 * Provider item structure:
	 * [ $values, $quantity, $decimal, $thousand_sep, $total ]
	 *
	 * @return array[]
	 */
	public function sub_totals_provider() {
		return [
			[ '0', 1, '.', ',', '0.00' ], // multiply by zero
			[ '0.10', 0, '.', ',', '0.00' ], // multiply by zero
			[ '0.10', 1, '.', ',', '0.10' ], // multiply value < 1 formatted with .
			[ '0,05', 3, ',', '.', '0,15' ], // multiply value < 1 formatted with ,
			[ '10 10', 1, ' ', ',', '10 10' ], // value formatted with space decimal
			[ '2345&nbsp;50', 2, '&nbsp;', '&quot;', '469&quot;100&nbsp;00' ], // html entities as separators
			[ '1,250.45', 2, '.', ',', '2,500.90' ], // formatted larger value
			[ '1,250.45', 999999, '.', ',', '1,250,448,749.55' ], // super-large result
			[ '99999.99', 15, '.', ',', '1,499,999.85' ], // semi-formatted input with large result
			[ '2 999 123,99', 2, ',', ' ', '5 998 247,98' ], // large result with spaces as thousands separator
			[ 2999123.99, 2, '.', ' ', '5 998 247.98' ], // multiply a float with . as the decimal
			[ '1010.', 101, '.', ',', '102,010.00' ], // decimal at the end of the string
			[ '.10', 10, '.', ',', '1.00' ], // decimal at the start of the string
			[ '1.102', 10, '.', ',', '11,020.00' ], // 3 decimals
			[ '99.00', 2, '.', ',', '198.00' ], // Copy of snapshot test
		];
	}
}
