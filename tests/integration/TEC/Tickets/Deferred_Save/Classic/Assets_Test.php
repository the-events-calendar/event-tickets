<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;

class Assets_Test extends WPTestCase {
	private array $deferred_posts = [];

	public function setUp(): void {
		parent::setUp();
		add_filter(
			'tec_tickets_deferred_save_enabled',
			function ( bool $enabled, int $post_id ): bool {
				return in_array( $post_id, $this->deferred_posts, true ) ? true : $enabled;
			},
			10,
			2
		);
	}

	public function tearDown(): void {
		$this->deferred_posts = [];
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function the_script_and_style_are_registered(): void {
		$this->assertTrue( wp_script_is( Assets::SCRIPT, 'registered' ) );
		$this->assertTrue( wp_style_is( Assets::STYLE, 'registered' ) );
		$this->assertContains( 'event-tickets-admin-js', wp_scripts()->registered[ Assets::SCRIPT ]->deps );
	}

	/**
	 * @test
	 */
	public function they_enqueue_only_for_a_post_that_uses_deferred_save(): void {
		global $post;
		$assets = tribe( Assets::class );

		$post = get_post( static::factory()->post->create( [ 'post_type' => 'page' ] ) );
		$this->assertFalse( $assets->should_enqueue() );

		$this->deferred_posts[] = $post->ID;
		$this->assertTrue( $assets->should_enqueue() );

		$post = null;
		$this->assertFalse( $assets->should_enqueue() );
	}
}
