<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;

class Assets_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_register_the_script_and_the_style(): void {
		$this->assertTrue( wp_script_is( Assets::SCRIPT, 'registered' ) );
		$this->assertTrue( wp_style_is( Assets::STYLE, 'registered' ) );
		$this->assertContains( 'event-tickets-admin-js', wp_scripts()->registered[ Assets::SCRIPT ]->deps );
	}

	/**
	 * @test
	 */
	public function it_should_enqueue_only_for_a_ticketable_post(): void {
		global $post;
		$assets = tribe( Assets::class );

		$post = get_post( static::factory()->post->create( [ 'post_type' => 'page' ] ) );
		$this->assertTrue( $assets->should_enqueue() );

		$post = get_post( static::factory()->attachment->create_object( 'image.jpg', 0, [ 'post_mime_type' => 'image/jpeg' ] ) );
		$this->assertFalse( $assets->should_enqueue() );

		$post = null;
		$this->assertFalse( $assets->should_enqueue() );
	}
}
