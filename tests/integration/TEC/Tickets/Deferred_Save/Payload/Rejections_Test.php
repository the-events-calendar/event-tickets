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
	public function it_should_return_a_copy_with_the_rejection_added_and_leave_the_original_untouched(): void {
		$empty = new Rejections();

		$one = $empty->with( 'update', 12, 'not yours' );
		$two = $one->with( null, null, 'too many' );

		$this->assertTrue( $empty->is_empty(), 'The original is untouched.' );
		$this->assertSame( [ [ 'part' => 'update', 'key' => 12, 'message' => 'not yours' ] ], $one->all() );
		$this->assertSame(
			[
				[ 'part' => 'update', 'key' => 12, 'message' => 'not yours' ],
				[ 'part' => null, 'key' => null, 'message' => 'too many' ],
			],
			$two->all()
		);
	}

	/**
	 * @test
	 */
	public function it_should_merge_another_set_after_its_own(): void {
		$mine   = ( new Rejections() )->with( 'update', 1, 'a' );
		$theirs = ( new Rejections() )->with( 'delete', 2, 'b' )->with( 'move', 3, 'c' );

		$merged = $mine->merge( $theirs );

		$this->assertCount( 1, $mine->all(), 'The original is untouched.' );
		$this->assertSame( [ 1, 2, 3 ], array_column( $merged->all(), 'key' ) );
	}
}
