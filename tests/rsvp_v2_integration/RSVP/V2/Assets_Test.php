<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;

class Assets_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_instantiable(): void {
		$assets = tribe( Assets::class );

		$this->assertInstanceOf( Assets::class, $assets );
	}

	/**
	 * @test
	 */
	public function it_should_register_without_errors(): void {
		$assets = tribe( Assets::class );
		$assets->register();

		// If we get here without exception, test passes.
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_not_enqueue_classic_editor_assets_when_no_global_post(): void {
		global $post;
		$saved_post = $post;
		$post       = null;

		$result = tribe( Assets::class )->should_enqueue_classic_editor_assets();

		$post = $saved_post;

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function it_should_enqueue_classic_editor_assets_for_ticketable_post_type(): void {
		global $post;
		$saved_post = $post;
		$post_id    = static::factory()->post->create( [ 'post_type' => 'post', 'post_status' => 'publish' ] );
		$post       = get_post( $post_id );

		$result = tribe( Assets::class )->should_enqueue_classic_editor_assets();

		$post = $saved_post;

		$this->assertTrue( $result );
	}

	/**
	 * @test
	 */
	public function it_should_not_enqueue_classic_editor_assets_for_non_ticketable_post_type(): void {
		global $post;
		$saved_post = $post;
		$post       = (object) [ 'post_type' => 'non_ticketable_post_type_xyz' ];

		$result = tribe( Assets::class )->should_enqueue_classic_editor_assets();

		$post = $saved_post;

		$this->assertFalse( $result );
	}
}
