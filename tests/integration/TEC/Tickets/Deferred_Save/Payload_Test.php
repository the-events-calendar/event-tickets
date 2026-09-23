<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;

class Payload_Test extends WPTestCase {
	private function error_keys( Payload $payload, string $part ): array {
		return array_values(
			array_map(
				static fn( array $error ) => $error['key'],
				array_filter( $payload->get_errors(), static fn( array $error ) => $error['part'] === $part )
			)
		);
	}

	/**
	 * @test
	 */
	public function it_parses_a_full_payload(): void {
		$update_data = [ 'ticket_name' => 'Updated', 'ticket_price' => '10' ];
		$create_data = [ 'ticket_name' => 'New', 'ticket_price' => '5' ];

		$payload = Payload::from_array(
			[
				'update' => [ 123 => $update_data ],
				'create' => [ $create_data, $create_data ],
				'delete' => [ 456 ],
				'move'   => [ 789 => 42 ],
			]
		);

		$this->assertTrue( $payload->is_valid() );
		$this->assertTrue( $payload->has_changes() );
		$this->assertSame( [], $payload->get_errors() );
		$this->assertSame( [ 123 => $update_data ], $payload->get_update() );
		$this->assertSame( [ 0 => $create_data, 1 => $create_data ], $payload->get_create() );
		$this->assertSame( [ 456 ], $payload->get_delete() );
		$this->assertSame( [ 789 => 42 ], $payload->get_move() );
	}

	/**
	 * @test
	 */
	public function it_passes_data_on_untouched(): void {
		$data = [
			'ticket_name'                  => 'With extras',
			'ticket_sale_price'            => '3',
			'tribe-ticket'                 => [ 'capacity' => '10', 'mode' => 'own' ],
			'tribe-tickets-plus-iac'       => 'allowed',
			'unknown_third_party_field'    => [ 'nested' => true ],
			'ticket_fees'                  => [ 1, 2 ],
		];

		$payload = Payload::from_array( [ 'update' => [ 5 => $data ], 'create' => [ $data ] ] );

		$this->assertSame( $data, $payload->get_update()[5] );
		$this->assertSame( $data, $payload->get_create()[0] );
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
	public function an_empty_or_missing_payload_is_valid_and_has_no_changes( $raw ): void {
		$payload = Payload::from_array( $raw );

		$this->assertTrue( $payload->is_valid() );
		$this->assertFalse( $payload->has_changes() );
		$this->assertSame( [], $payload->get_errors() );
		$this->assertSame( [], $payload->get_update() );
		$this->assertSame( [], $payload->get_create() );
		$this->assertSame( [], $payload->get_delete() );
		$this->assertSame( [], $payload->get_move() );
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
	public function a_non_array_payload_is_rejected( $raw ): void {
		$payload = Payload::from_array( $raw );

		$this->assertFalse( $payload->is_valid() );
		$this->assertFalse( $payload->has_changes() );
		$this->assertCount( 1, $payload->get_errors() );
		$this->assertNull( $payload->get_errors()[0]['part'] );
		$this->assertNull( $payload->get_errors()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function an_unknown_part_rejects_the_whole_payload(): void {
		$payload = Payload::from_array( [ 'update' => [ 1 => [ 'ticket_name' => 'x' ] ], 'updates' => [] ] );

		$this->assertFalse( $payload->is_valid() );
		$this->assertFalse( $payload->has_changes() );
		$this->assertSame( [], $payload->get_update() );
		$this->assertCount( 1, $payload->get_errors() );
		$this->assertSame( 'updates', $payload->get_errors()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function a_part_that_is_not_an_array_is_rejected_and_the_others_survive(): void {
		$payload = Payload::from_array( [ 'update' => 'nope', 'delete' => [ 3 ] ] );

		$this->assertTrue( $payload->is_valid() );
		$this->assertSame( [], $payload->get_update() );
		$this->assertSame( [ 3 ], $payload->get_delete() );
		$this->assertCount( 1, $payload->get_errors() );
		$this->assertSame( 'update', $payload->get_errors()[0]['part'] );
		$this->assertNull( $payload->get_errors()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function non_integer_ticket_ids_are_rejected_per_entry(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array(
			[
				'update' => [ 'abc' => $data, 0 => $data, -1 => $data, 7 => $data ],
				'delete' => [ 'abc', 0, '', 8, 3.5 ],
				'move'   => [ 'abc' => 1, 9 => 'xyz', 10 => 0, 11 => 2 ],
			]
		);

		$this->assertTrue( $payload->is_valid() );
		$this->assertSame( [ 7 => $data ], $payload->get_update() );
		$this->assertSame( [ 8 ], $payload->get_delete() );
		$this->assertSame( [ 11 => 2 ], $payload->get_move() );
		$this->assertSame( [ 'abc', 0, -1 ], $this->error_keys( $payload, 'update' ) );
		$this->assertSame( [ 'abc', 0, '', 3.5 ], $this->error_keys( $payload, 'delete' ) );
		$this->assertSame( [ 'abc', 9, 10 ], $this->error_keys( $payload, 'move' ) );
	}

	/**
	 * @test
	 */
	public function data_that_is_not_an_array_is_rejected_per_entry(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array(
			[
				'update' => [ 1 => 'string', 2 => $data, 3 => null ],
				'create' => [ $data, 'string', $data, 12 ],
			]
		);

		$this->assertTrue( $payload->is_valid() );
		$this->assertSame( [ 2 => $data ], $payload->get_update() );
		$this->assertSame( [ 0 => $data, 2 => $data ], $payload->get_create() );
		$this->assertSame( [ 1, 3 ], $this->error_keys( $payload, 'update' ) );
		$this->assertSame( [ 1, 3 ], $this->error_keys( $payload, 'create' ) );
	}

	/**
	 * @test
	 */
	public function digit_strings_are_normalised_to_integers(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array(
			[
				'update' => [ '123' => $data ],
				'delete' => [ '456', '456' ],
				'move'   => [ '789' => '42' ],
			]
		);

		$this->assertSame( [ 123 => $data ], $payload->get_update() );
		$this->assertSame( [ 456 ], $payload->get_delete() );
		$this->assertSame( [ 789 => 42 ], $payload->get_move() );
	}

	/**
	 * @test
	 */
	public function create_positions_are_preserved_and_must_be_non_negative_integers(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array( [ 'create' => [ 2 => $data, '5' => $data, 'a' => $data, -1 => $data ] ] );

		$this->assertSame( [ 2 => $data, 5 => $data ], $payload->get_create() );
		$this->assertSame( [ 'a', -1 ], $this->error_keys( $payload, 'create' ) );
	}

	/**
	 * @test
	 */
	public function the_same_ticket_in_update_and_delete_rejects_both_entries(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array(
			[
				'update' => [ 1 => $data, 2 => $data ],
				'delete' => [ 2, 3 ],
			]
		);

		$this->assertTrue( $payload->is_valid() );
		$this->assertSame( [ 1 => $data ], $payload->get_update() );
		$this->assertSame( [ 3 ], $payload->get_delete() );
		$this->assertCount( 1, $payload->get_errors() );
		$this->assertSame( 2, $payload->get_errors()[0]['key'] );
	}

	/**
	 * @test
	 */
	public function with_rejected_returns_a_new_payload_without_the_entry(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array(
			[
				'update' => [ 1 => $data, 2 => $data ],
				'create' => [ $data, $data ],
				'delete' => [ 3, 4 ],
				'move'   => [ 5 => 9, 6 => 9 ],
			]
		);

		$rejected = $payload
			->with_rejected( 'update', 1, 'no' )
			->with_rejected( 'create', 1, 'no' )
			->with_rejected( 'delete', 4, 'no' )
			->with_rejected( 'move', 5, 'no' );

		$this->assertNotSame( $payload, $rejected );
		$this->assertSame( [], $payload->get_errors(), 'The original payload is untouched.' );
		$this->assertSame( [ 2 => $data ], $rejected->get_update() );
		$this->assertSame( [ 0 => $data ], $rejected->get_create() );
		$this->assertSame( [ 3 ], $rejected->get_delete() );
		$this->assertSame( [ 6 => 9 ], $rejected->get_move() );
		$this->assertSame(
			[
				[ 'part' => 'update', 'key' => 1, 'message' => 'no' ],
				[ 'part' => 'create', 'key' => 1, 'message' => 'no' ],
				[ 'part' => 'delete', 'key' => 4, 'message' => 'no' ],
				[ 'part' => 'move', 'key' => 5, 'message' => 'no' ],
			],
			$rejected->get_errors()
		);
	}

	/**
	 * @test
	 */
	public function with_rejected_at_payload_level_empties_every_part(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = Payload::from_array( [ 'update' => [ 1 => $data ], 'delete' => [ 3 ] ] )
			->with_rejected( null, null, 'You cannot edit this post.' );

		$this->assertFalse( $payload->is_valid() );
		$this->assertFalse( $payload->has_changes() );
		$this->assertSame( [], $payload->get_update() );
		$this->assertSame( [], $payload->get_delete() );
		$this->assertSame( [ [ 'part' => null, 'key' => null, 'message' => 'You cannot edit this post.' ] ], $payload->get_errors() );
	}
}
