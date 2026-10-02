<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;

class Sale_Price_Rule_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}}>
	 */
	public function shared_valid_rules_provider(): Generator {
		foreach ( $this->get_fixtures()['cases'] as $case ) {
			yield $case['name'] => [ $case['rule'] ];
		}
	}

	/**
	 * @return Generator<string,array{0: array{start?: mixed, end?: mixed}}>
	 */
	public function shared_invalid_rules_provider(): Generator {
		foreach ( $this->get_fixtures()['invalid_rules'] as $case ) {
			yield $case['name'] => [ $case['rule'] ];
		}
	}

	/**
	 * @test
	 * @dataProvider shared_valid_rules_provider
	 */
	public function should_return_the_shared_fixture_rule_as_its_canonical_array( array $data ): void {
		$this->assertSame( $data, Sale_Price_Rule::from_array( $data )->to_array() );
	}

	/**
	 * @test
	 * @dataProvider shared_valid_rules_provider
	 */
	public function should_encode_as_its_canonical_array( array $data ): void {
		$this->assertSame( wp_json_encode( $data ), wp_json_encode( Sale_Price_Rule::from_array( $data ) ) );
	}

	/**
	 * @test
	 * @dataProvider shared_invalid_rules_provider
	 */
	public function should_reject_the_shared_fixture_invalid_rule( array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Sale_Price_Rule::from_array( $data );
	}

	/**
	 * @test
	 */
	public function should_expose_its_start_and_end(): void {
		$rule = Sale_Price_Rule::from_array(
			[
				'start' => [ 'mode' => Sale_Price_Rule::MODE_NOW ],
				'end'   => [ 'mode' => Rule::MODE_SPECIFIC ],
			]
		);

		$this->assertSame( Sale_Price_Rule::MODE_NOW, $rule->get_start()->get_mode() );
		$this->assertSame( Rule::MODE_SPECIFIC, $rule->get_end()->get_mode() );
	}

	/**
	 * @test
	 */
	public function should_name_the_now_end_it_rejects(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The sale price end' );

		Sale_Price_Rule::from_array(
			[
				'start' => [ 'mode' => Sale_Price_Rule::MODE_NOW ],
				'end'   => [ 'mode' => Sale_Price_Rule::MODE_NOW ],
			]
		);
	}

	/**
	 * @test
	 */
	public function should_build_the_sale_price_rule_from_stored_data_it_shares(): void {
		$sale_price_rule   = [
			'start' => [ 'mode' => Sale_Price_Rule::MODE_NOW ],
			'end'   => [
				'mode'  => Rule::MODE_RELATIVE,
				'value' => 1,
				'unit'  => WEEK_IN_SECONDS,
			],
		];
		$sales_window_rule = [
			'start' => [ 'mode' => Rule::MODE_DEFAULT ],
			'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
		];

		$rule = Sale_Price_Rule::from_stored( array_merge( $sales_window_rule, [ Sale_Price_Rule::KEY => $sale_price_rule ] ) );

		$this->assertSame( $sale_price_rule, $rule->to_array() );
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>}>
	 */
	public function stored_data_without_a_valid_rule_provider(): Generator {
		yield 'nothing stored' => [ [] ];
		yield 'only a sales window rule' => [
			[
				'start' => [ 'mode' => Rule::MODE_DEFAULT ],
				'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
			],
		];
		yield 'a sale price rule that is not an object' => [ [ Sale_Price_Rule::KEY => 'now' ] ];
		yield 'an invalid sale price rule' => [ [ Sale_Price_Rule::KEY => [ 'start' => [ 'mode' => Sale_Price_Rule::MODE_NOW ] ] ] ];
	}

	/**
	 * @test
	 * @dataProvider stored_data_without_a_valid_rule_provider
	 */
	public function should_build_no_rule_from_stored_data_without_a_valid_one( array $stored ): void {
		$this->assertNull( Sale_Price_Rule::from_stored( $stored ) );
	}

	/**
	 * @test
	 * @dataProvider shared_valid_rules_provider
	 */
	public function should_build_the_rule_from_its_json_form( array $data ): void {
		$this->assertSame( $data, Sale_Price_Rule::from_json( wp_json_encode( $data ) )->to_array() );
	}

	/**
	 * @return Generator<string,array{0: string}>
	 */
	public function invalid_json_provider(): Generator {
		yield 'malformed' => [ '{"start":' ];
		yield 'empty string' => [ '' ];
		yield 'a scalar' => [ '"now"' ];
		yield 'null' => [ 'null' ];
		yield 'invalid rule' => [ '{"start":{"mode":"now"}}' ];
	}

	/**
	 * @test
	 * @dataProvider invalid_json_provider
	 */
	public function should_reject_invalid_json( string $json ): void {
		$this->expectException( InvalidArgumentException::class );

		Sale_Price_Rule::from_json( $json );
	}

	/**
	 * Reads the fixtures shared by the sale price rule and resolver tests.
	 *
	 * @return array{cases: list<array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}}>, invalid_rules: list<array{name: string, rule: array{start?: mixed, end?: mixed}, reason: string}>}
	 */
	private function get_fixtures(): array {
		return json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-price-cases.json' ) ), true );
	}
}
