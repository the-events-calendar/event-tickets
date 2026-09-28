<?php

namespace TEC\Tickets\Deferred_Save;

use Codeception\TestCase\WPTestCase;

class Payload_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_hold_the_four_parts_it_was_built_with(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = new Payload( [ 1 => $data ], [ 0 => $data, 2 => $data ], [ 3 ], [ 4 => 9 ] );

		$this->assertTrue( $payload->has_changes() );
		$this->assertSame( [ 1 => $data ], $payload->get_update() );
		$this->assertSame( [ 0 => $data, 2 => $data ], $payload->get_create() );
		$this->assertSame( [ 3 ], $payload->get_delete() );
		$this->assertSame( [ 4 => 9 ], $payload->get_move() );
	}

	/**
	 * @test
	 */
	public function it_should_have_no_changes_when_every_part_is_empty(): void {
		$this->assertFalse( ( new Payload() )->has_changes() );
		$this->assertFalse( ( new Payload( [], [], [], [] ) )->has_changes() );
		$this->assertTrue( ( new Payload( [], [], [ 3 ], [] ) )->has_changes() );
	}

	/**
	 * @test
	 */
	public function it_should_return_a_copy_without_one_entry_and_leave_the_original_untouched(): void {
		$data    = [ 'ticket_name' => 'x' ];
		$payload = new Payload( [ 1 => $data, 2 => $data ], [ 0 => $data, 1 => $data ], [ 3, 4 ], [ 5 => 9, 6 => 9 ] );

		$smaller = $payload
			->without( Payload::UPDATE, 1 )
			->without( Payload::CREATE, 1 )
			->without( Payload::DELETE, 4 )
			->without( Payload::MOVE, 5 );

		$this->assertNotSame( $payload, $smaller );
		$this->assertSame( [ 1 => $data, 2 => $data ], $payload->get_update(), 'The original payload is untouched.' );
		$this->assertSame( [ 3, 4 ], $payload->get_delete() );
		$this->assertSame( [ 2 => $data ], $smaller->get_update() );
		$this->assertSame( [ 0 => $data ], $smaller->get_create() );
		$this->assertSame( [ 3 ], $smaller->get_delete(), 'The delete list is reindexed.' );
		$this->assertSame( [ 6 => 9 ], $smaller->get_move() );
	}

	/**
	 * @test
	 */
	public function it_should_ignore_a_key_that_is_not_in_the_part(): void {
		$payload = new Payload( [ 1 => [ 'ticket_name' => 'x' ] ], [], [ 3 ], [] );

		$same = $payload->without( Payload::UPDATE, 99 )->without( Payload::DELETE, 99 );

		$this->assertSame( $payload->get_update(), $same->get_update() );
		$this->assertSame( $payload->get_delete(), $same->get_delete() );
	}
}
