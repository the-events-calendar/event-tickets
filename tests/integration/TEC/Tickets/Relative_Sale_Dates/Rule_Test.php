<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;

class Rule_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}}>
	 */
	public function valid_rules_provider(): Generator {
		$units   = [
			'minutes' => Rule::UNIT_MINUTES,
			'hours'   => Rule::UNIT_HOURS,
			'days'    => Rule::UNIT_DAYS,
			'weeks'   => Rule::UNIT_WEEKS,
		];
		$anchors = [ Rule::ANCHOR_START, Rule::ANCHOR_END ];
		// The lowest and highest value the product allows.
		$values = [ 1, 60 ];

		foreach ( [ Rule::MODE_DEFAULT, Rule::MODE_SPECIFIC ] as $start_mode ) {
			foreach ( [ Rule::MODE_DEFAULT, Rule::MODE_SPECIFIC ] as $end_mode ) {
				yield "{$start_mode} start, {$end_mode} end" => [
					[
						'start' => [ 'mode' => $start_mode ],
						'end'   => [ 'mode' => $end_mode ],
					],
				];
			}
		}

		foreach ( $units as $unit_name => $unit ) {
			foreach ( $anchors as $anchor ) {
				foreach ( $values as $value ) {
					$relative = [
						'mode'   => Rule::MODE_RELATIVE,
						'value'  => $value,
						'unit'   => $unit,
						'anchor' => $anchor,
					];

					yield "relative start, {$value} {$unit_name} before {$anchor}" => [
						[
							'start' => $relative,
							'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
						],
					];

					yield "relative end, {$value} {$unit_name} before {$anchor}" => [
						[
							'start' => [ 'mode' => Rule::MODE_SPECIFIC ],
							'end'   => $relative,
						],
					];
				}
			}
		}
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>}>
	 */
	public function invalid_rules_provider(): Generator {
		$relative = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => Rule::UNIT_WEEKS,
			'anchor' => Rule::ANCHOR_START,
		];
		$default  = [ 'mode' => Rule::MODE_DEFAULT ];

		yield 'invalid start' => [ [ 'start' => array_merge( $relative, [ 'value' => 0 ] ), 'end' => $default ] ];
		yield 'invalid end' => [ [ 'start' => $default, 'end' => [ 'mode' => 'after' ] ] ];
		yield 'missing start' => [ [ 'end' => $default ] ];
		yield 'missing end' => [ [ 'start' => $relative ] ];
		yield 'null end' => [ [ 'start' => $relative, 'end' => null ] ];
		yield 'end as a string' => [ [ 'start' => $relative, 'end' => 'default' ] ];
	}

	/**
	 * @return Generator<string,array{0: array{start?: array<string,int|float|string>, end?: array<string,int|float|string>}}>
	 */
	public function shared_invalid_rules_provider(): Generator {
		$fixtures = json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-window-cases.json' ) ), true );

		foreach ( $fixtures['invalid_rules'] as $case ) {
			yield $case['name'] => [ $case['rule'] ];
		}
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_accept_a_valid_rule( array $data ): void {
		$this->assertInstanceOf( Rule::class, Rule::from_array( $data ) );
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_return_a_valid_rule_as_its_canonical_array( array $data ): void {
		$this->assertSame( $data, Rule::from_array( $data )->to_array() );
	}

	/**
	 * @test
	 */
	public function should_build_the_sales_window_rule_from_stored_data_it_shares(): void {
		$rule = [
			'start' => [ 'mode' => 'default' ],
			'end'   => [
				'mode'   => 'relative',
				'value'  => 1,
				'unit'   => Rule::UNIT_DAYS,
				'anchor' => 'start',
			],
		];

		$this->assertSame( $rule, Rule::from_stored( array_merge( [ 'sale_price' => [ 'start' => [ 'mode' => 'default' ] ] ], $rule ) )->to_array() );
	}

	/**
	 * @test
	 */
	public function should_build_no_rule_from_stored_data_without_a_valid_one(): void {
		$this->assertNull( Rule::from_stored( [] ) );
		$this->assertNull( Rule::from_stored( [ 'start' => [ 'mode' => 'default' ] ] ) );
	}

	/**
	 * @test
	 * @dataProvider invalid_rules_provider
	 */
	public function should_reject_an_invalid_rule( array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_array( $data );
	}

	/**
	 * @test
	 * @dataProvider shared_invalid_rules_provider
	 */
	public function should_reject_the_shared_fixture_invalid_rule( array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_array( $data );
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_survive_a_json_round_trip_unchanged( array $data ): void {
		$rule = Rule::from_array( $data );
		$json = $rule->to_json();

		$read_back = Rule::from_json( $json );

		$this->assertEquals( $rule, $read_back );
		$this->assertSame( $json, $read_back->to_json() );
		$this->assertSame( $data, json_decode( $json, true ) );
	}

	/**
	 * @test
	 */
	public function should_not_write_keys_the_mode_does_not_use(): void {
		$rule = Rule::from_array(
			[
				'start' => [
					'mode'   => 'specific',
					'value'  => 3,
					'unit'   => Rule::UNIT_DAYS,
					'anchor' => 'start',
				],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 2,
					'unit'   => 3600,
					'anchor' => 'end',
					'extra'  => 'dropped',
				],
			]
		);

		$this->assertSame(
			'{"start":{"mode":"specific"},"end":{"mode":"relative","value":2,"unit":3600,"anchor":"end"}}',
			$rule->to_json()
		);
	}

	/**
	 * @test
	 */
	public function should_ignore_other_top_level_keys(): void {
		$json = '{"sale_price":{"mode":"relative"},"start":{"mode":"default"},"end":{"mode":"default"}}';

		$rule = Rule::from_json( $json );

		$this->assertSame( '{"start":{"mode":"default"},"end":{"mode":"default"}}', $rule->to_json() );
	}

	/**
	 * @return Generator<string,array{0: string}>
	 */
	public function invalid_json_provider(): Generator {
		yield 'malformed' => [ '{"start":' ];
		yield 'empty string' => [ '' ];
		yield 'a list' => [ '[{"mode":"default"},{"mode":"default"}]' ];
		yield 'a scalar' => [ '"default"' ];
		yield 'null' => [ 'null' ];
		yield 'invalid rule' => [ '{"start":{"mode":"default"}}' ];
	}

	/**
	 * @test
	 * @dataProvider invalid_json_provider
	 */
	public function should_reject_invalid_json( string $json ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_json( $json );
	}
}
