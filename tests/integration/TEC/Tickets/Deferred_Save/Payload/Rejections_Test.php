<?php

namespace TEC\Tickets\Deferred_Save\Payload;

use Codeception\TestCase\WPTestCase;

class Rejections_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_start_empty(): void {
		$rejections = new Rejections();

		$this->assertTrue( $rejections->is_empty() );
		$this->assertSame( [], $rejections->all() );
	}

	/**
	 * @test
	 */
	public function it_should_keep_every_rejection_in_the_order_it_was_added(): void {
		$rejections = new Rejections();

		$rejections->add( 'update', 12, 'not yours' );
		$rejections->add( 'create', 0, 'no provider' );
		$rejections->add( null, null, 'too many' );

		$this->assertFalse( $rejections->is_empty() );
		$this->assertSame(
			[
				[ 'part' => 'update', 'key' => 12, 'message' => 'not yours' ],
				[ 'part' => 'create', 'key' => 0, 'message' => 'no provider' ],
				[ 'part' => null, 'key' => null, 'message' => 'too many' ],
			],
			$rejections->all()
		);
	}
}
