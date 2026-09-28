<?php

namespace TEC\Tickets\Deferred_Save\Payload;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Payload;

class Outcome_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_hold_the_payload_and_the_rejections_it_was_built_with(): void {
		$payload    = new Payload( [ 1 => [ 'ticket_name' => 'x' ] ] );
		$rejections = ( new Rejections() )->with( 'update', 2, 'no' );

		$outcome = new Outcome( $payload, $rejections );

		$this->assertSame( $payload, $outcome->payload() );
		$this->assertSame( $rejections, $outcome->rejections() );
	}
}
