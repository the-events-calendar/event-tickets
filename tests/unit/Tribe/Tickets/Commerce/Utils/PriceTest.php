<?php

namespace Tribe\Tickets\Commerce;

use TEC\Tickets\Commerce\Utils\Price;

class PriceTest extends \Codeception\Test\Unit {

	/**
	 * @dataProvider totals_provider
	 */
	public function test_total_is_acurate( $values, $decimal, $thousands_sep, $result ) {
		$total = Price::total( $values, $decimal, $thousands_sep );

		$this->assertEquals( $result, $total );
	}

	/**
	 * @dataProvider sub_totals_provider
	 */
	public function test_sub_total_is_acurate( $value, $quantity, $decimal, $thousands_sep, $result ) {
		$sub_total = Price::sub_total( $value, $quantity, $decimal, $thousands_sep );

		$this->assertEquals( $result, $sub_total );
	}
	/**
	 * Provider item structure:
	 * [ $values, $decimal, $thousand_sep, $total ]
	 *
	 * @return array[]
	 */
	public function totals_provider() {
		return [
			[ [ 1, 2 ], '.', ',', '0.03' ],
			[ [ 34, 56 ], '.', ',', '0.90' ],
			[ [ 789, '012' ], '.', ',', '8.01' ],
			[ [ 3456, '7890' ], '.', ',', '113.46' ],
			[ [ '12,345', 67890 ], '.', ',', '802.35' ], // formatted text without decimals
			[ [ '1.234,56', '7.890,12' ], ',', '.', '9.124,68' ], // comma as decimal separator and dot as thousands separator
			[ [ '34 567.89', '0123456' ], '.', ' ', '35 802.45' ], // space as thousands separator
			[ [ '789\'012.34', '56789012' ], '.', '\'', '1\'356\'902.46' ], // apostrophe as thousands separator
			[ [ '789012 34', '56789012' ], ' ', '', '1356902 46' ], // space as decimal separator and no thousands separator
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
			[ '0', 1, '.', ',', '0.00' ],
			[ '0.10', 0, '.', ',', '0.00' ],
			[ '0.10', 1, '.', ',', '0.10' ],
			[ '10 10', 1, ' ', ',', '10 10' ],
			[ '2.50', 2, '.', ',', '5.00' ],
			[ '1,250.45', 2, '.', ',', '2,500.90' ],
			[ '1250.45', 173, '.', ',', '216,327.85' ],
			[ '99,999.99', 15, '.', ',', '1,499,999.85' ],
			[ '2 999 123,99', 2, ',', ' ', '5 998 247,98' ],
			[ 2999123.99, 2, '.', ' ', '5 998 247.98' ],
		];
	}
}
