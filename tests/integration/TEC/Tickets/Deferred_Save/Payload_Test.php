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
}
