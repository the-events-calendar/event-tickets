<?php

namespace TEC\Tickets\Relative_Sale_Dates;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;

class Rule_Test extends WPTestCase {
	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}}>
	 */
	public function valid_rules_provider(): Generator {
		$sales   = Window_Kind::sales();
		$units   = [
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
		];
		$anchors = [ Rule::ANCHOR_START, Rule::ANCHOR_END ];
		// The lowest and highest value the product allows.
		$values = [ 1, 60 ];

		foreach ( [ Rule::MODE_DEFAULT, Rule::MODE_SPECIFIC ] as $start_mode ) {
			foreach ( [ Rule::MODE_DEFAULT, Rule::MODE_SPECIFIC ] as $end_mode ) {
				yield "{$start_mode} start, {$end_mode} end" => [
					$sales,
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
						$sales,
						[
							'start' => $relative,
							'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
						],
					];

					yield "relative end, {$value} {$unit_name} before {$anchor}" => [
						$sales,
						[
							'start' => [ 'mode' => Rule::MODE_SPECIFIC ],
							'end'   => $relative,
						],
					];
				}
			}
		}

		foreach ( $this->get_sale_price_fixtures()['cases'] as $case ) {
			yield "sale price: {$case['name']}" => [ Window_Kind::sale_price(), $case['rule'] ];
		}
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,mixed>}>
	 */
	public function invalid_rules_provider(): Generator {
		$sales    = Window_Kind::sales();
		$relative = [
			'mode'   => Rule::MODE_RELATIVE,
			'value'  => 2,
			'unit'   => WEEK_IN_SECONDS,
			'anchor' => Rule::ANCHOR_START,
		];
		$default  = [ 'mode' => Rule::MODE_DEFAULT ];

		yield 'invalid start' => [ $sales, [ 'start' => array_merge( $relative, [ 'value' => 0 ] ), 'end' => $default ] ];
		yield 'invalid end' => [ $sales, [ 'start' => $default, 'end' => [ 'mode' => 'after' ] ] ];
		yield 'missing start' => [ $sales, [ 'end' => $default ] ];
		yield 'missing end' => [ $sales, [ 'start' => $relative ] ];
		yield 'null end' => [ $sales, [ 'start' => $relative, 'end' => null ] ];
		yield 'end as a string' => [ $sales, [ 'start' => $relative, 'end' => 'default' ] ];
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array{start?: array<string,int|float|string>|string, end?: array<string,int|float|string>|string}}>
	 */
	public function shared_invalid_rules_provider(): Generator {
		$fixtures = json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-window-cases.json' ) ), true );

		foreach ( $fixtures['invalid_rules'] as $case ) {
			yield $case['name'] => [ Window_Kind::sales(), $case['rule'] ];
		}

		foreach ( $this->get_sale_price_fixtures()['invalid_rules'] as $case ) {
			yield "sale price: {$case['name']}" => [ Window_Kind::sale_price(), $case['rule'] ];
		}
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_accept_a_valid_rule( Window_Kind $kind, array $data ): void {
		$this->assertInstanceOf( Rule::class, Rule::from_array( $data, $kind ) );
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_return_a_valid_rule_as_its_canonical_array( Window_Kind $kind, array $data ): void {
		$this->assertSame( $data, Rule::from_array( $data, $kind )->to_array() );
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_return_its_canonical_json( Window_Kind $kind, array $data ): void {
		$this->assertSame( wp_json_encode( $data ), Rule::from_array( $data, $kind )->to_json() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string, 2: string}>
	 */
	public function rule_modes_provider(): Generator {
		yield 'sales' => [ Window_Kind::sales(), Rule::MODE_DEFAULT, Rule::MODE_SPECIFIC ];
		yield 'sale price' => [ Window_Kind::sale_price(), Rule::MODE_NOW, Rule::MODE_SPECIFIC ];
	}

	/**
	 * @test
	 * @dataProvider rule_modes_provider
	 */
	public function should_expose_its_kind_start_and_end( Window_Kind $kind, string $start_mode, string $end_mode ): void {
		$rule = Rule::from_array(
			[
				'start' => [ 'mode' => $start_mode ],
				'end'   => [ 'mode' => $end_mode ],
			],
			$kind
		);

		$this->assertSame( $kind, $rule->get_kind() );
		$this->assertSame( $start_mode, $rule->get_start()->get_mode() );
		$this->assertSame( $end_mode, $rule->get_end()->get_mode() );
		$this->assertSame( $kind, $rule->get_start()->get_kind() );
		$this->assertSame( $kind, $rule->get_end()->get_kind() );
	}

	/**
	 * @test
	 */
	public function should_build_a_sales_window_rule_without_a_kind(): void {
		$rule = Rule::from_array( [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ] );

		$this->assertSame( Window_Kind::sales(), $rule->get_kind() );
	}

	/**
	 * @test
	 */
	public function should_name_the_now_end_a_sale_price_rule_rejects(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( "The window's end mode must be one of: relative, specific." );

		Rule::from_array(
			[
				'start' => [ 'mode' => Rule::MODE_NOW ],
				'end'   => [ 'mode' => Rule::MODE_NOW ],
			],
			Window_Kind::sale_price()
		);
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string}>
	 */
	public function stored_rules_provider(): Generator {
		yield 'sales' => [ Window_Kind::sales(), 'sales' ];
		yield 'sale price' => [ Window_Kind::sale_price(), 'sale_price' ];
	}

	/**
	 * @test
	 * @dataProvider stored_rules_provider
	 */
	public function should_build_the_rule_of_its_kind_from_stored_data_it_shares( Window_Kind $kind, string $expected_key ): void {
		$rules  = [
			'sales'      => [
				'start' => [ 'mode' => 'default' ],
				'end'   => [
					'mode'   => 'relative',
					'value'  => 1,
					'unit'   => DAY_IN_SECONDS,
					'anchor' => 'start',
				],
			],
			'sale_price' => [
				'start' => [ 'mode' => 'now' ],
				'end'   => [
					'mode'  => 'relative',
					'value' => 1,
					'unit'  => WEEK_IN_SECONDS,
				],
			],
		];
		$stored = array_merge( $rules['sales'], [ 'sale_price' => $rules['sale_price'] ] );

		$this->assertSame( $rules[ $expected_key ], Rule::from_stored( $stored, $kind )->to_array() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,mixed>}>
	 */
	public function stored_data_without_a_valid_rule_provider(): Generator {
		$sales_rule = [
			'start' => [ 'mode' => Rule::MODE_DEFAULT ],
			'end'   => [ 'mode' => Rule::MODE_DEFAULT ],
		];

		yield 'nothing stored' => [ Window_Kind::sales(), [] ];
		yield 'a start only' => [ Window_Kind::sales(), [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] ];
		yield 'only a sale price rule' => [ Window_Kind::sales(), [ 'sale_price' => [ 'start' => [ 'mode' => Rule::MODE_NOW ], 'end' => [ 'mode' => Rule::MODE_SPECIFIC ] ] ] ];
		yield 'sale price: nothing stored' => [ Window_Kind::sale_price(), [] ];
		yield 'sale price: only a sales window rule' => [ Window_Kind::sale_price(), $sales_rule ];
		yield 'sale price: a sale price rule that is not an object' => [ Window_Kind::sale_price(), [ 'sale_price' => 'now' ] ];
		yield 'sale price: an invalid sale price rule' => [ Window_Kind::sale_price(), [ 'sale_price' => [ 'start' => [ 'mode' => Rule::MODE_NOW ] ] ] ];
	}

	/**
	 * @test
	 * @dataProvider stored_data_without_a_valid_rule_provider
	 */
	public function should_build_no_rule_from_stored_data_without_a_valid_one( Window_Kind $kind, array $stored ): void {
		$this->assertNull( Rule::from_stored( $stored, $kind ) );
	}

	/**
	 * @test
	 * @dataProvider invalid_rules_provider
	 */
	public function should_reject_an_invalid_rule( Window_Kind $kind, array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_array( $data, $kind );
	}

	/**
	 * @test
	 * @dataProvider shared_invalid_rules_provider
	 */
	public function should_reject_the_shared_fixture_invalid_rule( Window_Kind $kind, array $data ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_array( $data, $kind );
	}

	/**
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_survive_a_json_round_trip_unchanged( Window_Kind $kind, array $data ): void {
		$rule = Rule::from_array( $data, $kind );
		$json = $rule->to_json();

		$read_back = Rule::from_json( $json, $kind );

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
					'unit'   => DAY_IN_SECONDS,
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
	 * @test
	 * @dataProvider valid_rules_provider
	 */
	public function should_build_a_rule_from_its_raw_array_or_json_form( Window_Kind $kind, array $data ): void {
		$this->assertSame( $data, Rule::from_raw( $data, $kind )->to_array() );
		$this->assertSame( $data, Rule::from_raw( wp_json_encode( $data ), $kind )->to_array() );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: mixed}>
	 */
	public function invalid_raw_rules_provider(): Generator {
		yield 'nothing sent' => [ Window_Kind::sales(), null ];
		yield 'a number' => [ Window_Kind::sales(), 1 ];
		yield 'malformed JSON' => [ Window_Kind::sales(), '{"start":' ];
		yield 'an invalid rule' => [ Window_Kind::sales(), [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ] ] ];
		yield 'sale price: a sales window rule' => [ Window_Kind::sale_price(), [ 'start' => [ 'mode' => Rule::MODE_DEFAULT ], 'end' => [ 'mode' => Rule::MODE_DEFAULT ] ] ];
		yield 'sale price: a now end as JSON' => [ Window_Kind::sale_price(), '{"start":{"mode":"now"},"end":{"mode":"now"}}' ];
	}

	/**
	 * @test
	 * @dataProvider invalid_raw_rules_provider
	 *
	 * @param mixed $raw The rule as sent.
	 */
	public function should_build_no_rule_from_an_invalid_raw_form( Window_Kind $kind, $raw ): void {
		$this->assertNull( Rule::from_raw( $raw, $kind ) );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: string}>
	 */
	public function invalid_json_provider(): Generator {
		$sales      = Window_Kind::sales();
		$sale_price = Window_Kind::sale_price();

		yield 'malformed' => [ $sales, '{"start":' ];
		yield 'empty string' => [ $sales, '' ];
		yield 'a list' => [ $sales, '[{"mode":"default"},{"mode":"default"}]' ];
		yield 'a scalar' => [ $sales, '"default"' ];
		yield 'null' => [ $sales, 'null' ];
		yield 'invalid rule' => [ $sales, '{"start":{"mode":"default"}}' ];
		yield 'sale price: malformed' => [ $sale_price, '{"start":' ];
		yield 'sale price: empty string' => [ $sale_price, '' ];
		yield 'sale price: a scalar' => [ $sale_price, '"now"' ];
		yield 'sale price: null' => [ $sale_price, 'null' ];
		yield 'sale price: invalid rule' => [ $sale_price, '{"start":{"mode":"now"}}' ];
	}

	/**
	 * @test
	 * @dataProvider invalid_json_provider
	 */
	public function should_reject_invalid_json( Window_Kind $kind, string $json ): void {
		$this->expectException( InvalidArgumentException::class );

		Rule::from_json( $json, $kind );
	}

	/**
	 * @return Generator<string,array{0: Window_Kind, 1: array<string,array<string,int|string>>, 2: bool}>
	 */
	public function end_follows_event_start_provider(): Generator {
		$sales        = Window_Kind::sales();
		$before_start = [ 'mode' => 'relative', 'value' => 2, 'unit' => WEEK_IN_SECONDS, 'anchor' => 'start' ];
		$before_end   = [ 'mode' => 'relative', 'value' => 2, 'unit' => HOUR_IN_SECONDS, 'anchor' => 'end' ];

		yield 'specific end, start counted from the event start' => [ $sales, [ 'start' => $before_start, 'end' => [ 'mode' => 'specific' ] ], true ];
		yield 'specific end, start counted from the event end' => [ $sales, [ 'start' => $before_end, 'end' => [ 'mode' => 'specific' ] ], false ];
		yield 'relative end' => [ $sales, [ 'start' => [ 'mode' => 'default' ], 'end' => $before_start ], false ];
		yield 'default end' => [ $sales, [ 'start' => [ 'mode' => 'default' ], 'end' => [ 'mode' => 'default' ] ], false ];
		yield 'sale price: specific end, start counted from the event start' => [
			Window_Kind::sale_price(),
			[
				'start' => [ 'mode' => 'relative', 'value' => 2, 'unit' => WEEK_IN_SECONDS ],
				'end'   => [ 'mode' => 'specific' ],
			],
			false,
		];
	}

	/**
	 * @test
	 * @dataProvider end_follows_event_start_provider
	 */
	public function should_let_only_an_end_left_to_the_ticket_follow_the_event_start( Window_Kind $kind, array $data, bool $follows ): void {
		$this->assertSame( $follows, Rule::from_array( $data, $kind )->lets_end_follow_event_start() );
	}

	/**
	 * Reads the fixtures shared by the sale price rule and resolver tests, in PHP and in Jest.
	 *
	 * @return array{cases: list<array{name: string, rule: array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}}>, invalid_rules: list<array{name: string, rule: array{start?: array<string,int|float|string>|string, end?: array<string,int|float|string>|string}, reason: string}>}
	 */
	private function get_sale_price_fixtures(): array {
		return json_decode( file_get_contents( codecept_data_dir( 'relative-sale-dates/sale-price-cases.json' ) ), true );
	}
}
