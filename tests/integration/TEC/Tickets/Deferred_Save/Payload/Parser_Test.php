<?php

namespace TEC\Tickets\Deferred_Save\Payload;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Payload;

class Parser_Test extends WPTestCase {
	private Rejections $rejections;

	protected function parse( $raw ): Payload {
		$outcome          = ( new Parser() )->parse( $raw );
		$this->rejections = $outcome->rejections();

		return $outcome->payload();
	}

	protected function rejected_keys( string $part ): array {
		return array_values(
			array_map(
				static fn( array $error ) => $error['key'],
				array_filter( $this->rejections->all(), static fn( array $error ) => $error['part'] === $part )
			)
		);
	}

	protected function payload_level_rejections(): array {
		return array_values( array_filter( $this->rejections->all(), static fn( array $error ) => null === $error['part'] ) );
	}

	/**
	 * @test
	 */
	public function it_should_parse_a_full_payload(): void {
		$update_data = [ 'ticket_name' => 'Updated', 'ticket_price' => '10' ];
		$create_data = [ 'ticket_name' => 'New', 'ticket_price' => '5' ];

		$payload = $this->parse(
			[
				'update' => [ 123 => $update_data ],
				'create' => [ $create_data, $create_data ],
				'delete' => [ 456 ],
				'move'   => [ 789 => 42 ],
			]
		);

		$this->assertTrue( $this->rejections->is_empty() );
		$this->assertTrue( $payload->has_changes() );
		$this->assertSame( [ 123 => $update_data + [ 'ticket_id' => 123 ] ], $payload->get_update() );
		$this->assertSame( [ 0 => $create_data, 1 => $create_data ], $payload->get_create() );
		$this->assertSame( [ 456 ], $payload->get_delete() );
		$this->assertSame( [ 789 => 42 ], $payload->get_move() );
	}

	/**
	 * @test
	 */
	public function it_should_pass_data_on_untouched(): void {
		$data = [
			'ticket_name'               => 'With extras',
			'ticket_sale_price'         => '3',
			'tribe-ticket'              => [ 'capacity' => '10', 'mode' => 'own' ],
			'tribe-tickets-plus-iac'    => 'allowed',
			'unknown_third_party_field' => [ 'nested' => true ],
			'ticket_fees'               => [ 1, 2 ],
		];

		$payload = $this->parse( [ 'update' => [ 5 => $data ], 'create' => [ $data ] ] );

		$this->assertSame( $data + [ 'ticket_id' => 5 ], $payload->get_update()[5] );
		$this->assertSame( $data, $payload->get_create()[0] );
	}

	/**
	 * @test
	 */
	public function it_should_let_the_update_key_win_over_a_ticket_id_inside_the_data(): void {
		$payload = $this->parse(
			[
				'update' => [
					123 => [ 'ticket_name' => 'smuggled', 'ticket_id' => 999 ],
					124 => [ 'ticket_name' => 'plain' ],
				],
			]
		);

		$this->assertSame(
			[
				123 => [ 'ticket_name' => 'smuggled', 'ticket_id' => 123 ],
				124 => [ 'ticket_name' => 'plain', 'ticket_id' => 124 ],
			],
			$payload->get_update()
		);
	}

	/**
	 * @test
	 */
	public function it_should_drop_a_ticket_id_inside_create_data(): void {
		$payload = $this->parse( [ 'create' => [ [ 'ticket_name' => 'new', 'ticket_id' => 123 ] ] ] );

		$this->assertSame( [ [ 'ticket_name' => 'new' ] ], $payload->get_create() );
	}

	/**
	 * @return array<string,array{0:mixed}>
	 */
	public function empty_payloads(): array {
		return [
			'missing'         => [ null ],
			'empty array'     => [ [] ],
			'all parts empty' => [ [ 'update' => [], 'create' => [], 'delete' => [], 'move' => [] ] ],
		];
	}

	/**
	 * @test
	 * @dataProvider empty_payloads
	 */
	public function it_should_treat_an_empty_or_missing_payload_as_no_changes( $raw ): void {
		$payload = $this->parse( $raw );

		$this->assertTrue( $this->rejections->is_empty() );
		$this->assertFalse( $payload->has_changes() );
	}

	/**
	 * @return array<string,array{0:mixed}>
	 */
	public function non_array_roots(): array {
		return [
			'string' => [ 'tec_tickets' ],
			'number' => [ 12 ],
			'bool'   => [ true ],
			'object' => [ (object) [ 'update' => [] ] ],
		];
	}

	/**
	 * @test
	 * @dataProvider non_array_roots
	 */
	public function it_should_throw_on_a_payload_that_is_not_an_array( $raw ): void {
		$this->expectException( Malformed_Exception::class );

		$this->parse( $raw );
	}

	/**
	 * @test
	 */
	public function it_should_throw_on_an_unknown_part_and_name_it(): void {
		$this->expectException( Malformed_Exception::class );
		$this->expectExceptionMessage( '"updates"' );

		$this->parse( [ 'update' => [ 1 => [ 'ticket_name' => 'x' ] ], 'updates' => [] ] );
	}

	/**
	 * @test
	 */
	public function it_should_reject_a_part_that_is_not_an_array_and_keep_the_others(): void {
		$payload = $this->parse( [ 'update' => 'nope', 'delete' => [ 3 ] ] );

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertSame( [], $payload->get_update() );
		$this->assertSame( [ 3 ], $payload->get_delete() );
		$this->assertCount( 1, $this->rejections->all() );
		$this->assertSame( 'update', $this->rejections->all()[0]['part'] );
		$this->assertNull( $this->rejections->all()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function it_should_reject_non_integer_ticket_ids_per_entry(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = $this->parse(
			[
				'update' => [ 'abc' => $data, 0 => $data, -1 => $data, 7 => $data ],
				'delete' => [ 'abc', 0, '', 8, 3.5 ],
				'move'   => [ 'abc' => 1, 9 => 'xyz', 10 => 0, 11 => 2 ],
			]
		);

		$this->assertSame( [], $this->payload_level_rejections() );
		$this->assertSame( [ 7 => $data + [ 'ticket_id' => 7 ] ], $payload->get_update() );
		$this->assertSame( [ 8 ], $payload->get_delete() );
		$this->assertSame( [ 11 => 2 ], $payload->get_move() );
		$this->assertSame( [ 'abc', 0, -1 ], $this->rejected_keys( 'update' ) );
		$this->assertSame( [ 'abc', 0, '', 3.5 ], $this->rejected_keys( 'delete' ) );
		$this->assertSame( [ 'abc', 9, 10 ], $this->rejected_keys( 'move' ) );
	}

	/**
	 * @test
	 */
	public function it_should_reject_data_that_is_not_an_array_per_entry(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = $this->parse(
			[
				'update' => [ 1 => 'string', 2 => $data, 3 => null ],
				'create' => [ $data, 'string', $data, 12 ],
			]
		);

		$this->assertSame( [ 2 => $data + [ 'ticket_id' => 2 ] ], $payload->get_update() );
		$this->assertSame( [ 0 => $data, 2 => $data ], $payload->get_create() );
		$this->assertSame( [ 1, 3 ], $this->rejected_keys( 'update' ) );
		$this->assertSame( [ 1, 3 ], $this->rejected_keys( 'create' ) );
	}

	/**
	 * @test
	 */
	public function it_should_normalise_digit_strings_to_integers(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = $this->parse(
			[
				'update' => [ '123' => $data ],
				'delete' => [ '456', '456' ],
				'move'   => [ '789' => '42' ],
			]
		);

		$this->assertSame( [ 123 => $data + [ 'ticket_id' => 123 ] ], $payload->get_update() );
		$this->assertSame( [ 456 ], $payload->get_delete() );
		$this->assertSame( [ 789 => 42 ], $payload->get_move() );
	}

	/**
	 * @test
	 */
	public function it_should_preserve_create_positions_and_require_non_negative_integers(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = $this->parse( [ 'create' => [ 2 => $data, '5' => $data, 'a' => $data, -1 => $data ] ] );

		$this->assertSame( [ 2 => $data, 5 => $data ], $payload->get_create() );
		$this->assertSame( [ 'a', -1 ], $this->rejected_keys( 'create' ) );
	}

	/**
	 * @test
	 */
	public function it_should_reject_both_entries_when_the_same_ticket_is_in_update_and_delete(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = $this->parse(
			[
				'update' => [ 1 => $data, 2 => $data ],
				'delete' => [ 2, 3 ],
			]
		);

		$this->assertSame( [ 1 => $data + [ 'ticket_id' => 1 ] ], $payload->get_update() );
		$this->assertSame( [ 3 ], $payload->get_delete() );
		$this->assertCount( 1, $this->rejections->all() );
		$this->assertSame( 2, $this->rejections->all()[0]['key'] );
	}
}
