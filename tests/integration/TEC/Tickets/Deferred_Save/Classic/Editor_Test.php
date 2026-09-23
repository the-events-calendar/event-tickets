<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Classic_Save;

class Editor_Test extends WPTestCase {
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

	protected function metabox_end_output( int $post_id ): string {
		ob_start();
		do_action( 'tribe_tickets_metabox_end', $post_id, null );

		return (string) ob_get_clean();
	}

	/**
	 * @test
	 */
	public function it_prints_nothing_for_a_post_that_does_not_use_deferred_save(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->assertSame( '', $this->metabox_end_output( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_prints_the_nonce_the_container_and_the_templates_for_a_deferred_post(): void {
		$post_id                = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->deferred_posts[] = $post_id;
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$html = $this->metabox_end_output( $post_id );

		$this->assertStringContainsString( 'name="' . Classic_Save::NONCE_FIELD . '"', $html );
		$this->assertStringContainsString( 'id="tec-tickets-deferred-save"', $html );
		$this->assertStringContainsString( 'data-post-id="' . $post_id . '"', $html );
		$this->assertStringContainsString( '<template id="tec-tickets-deferred-save-row"', $html );
		$this->assertStringContainsString( '<template id="tec-tickets-deferred-save-table"', $html );
		$this->assertStringContainsString( '<template id="tec-tickets-deferred-save-marker"', $html );
		$this->assertStringContainsString( 'not saved yet', strtolower( $html ) );
		$this->assertStringContainsString( 'screen-reader-text', $html );
		preg_match( '/value="([^"]+)"[^>]*name="' . Classic_Save::NONCE_FIELD . '"|name="' . Classic_Save::NONCE_FIELD . '"[^>]*value="([^"]+)"/', $html, $m );
		$nonce = $m[1] ?: ( $m[2] ?? '' );
		$this->assertNotEmpty( $nonce );
		$this->assertTrue( (bool) wp_verify_nonce( $nonce, Classic_Save::NONCE_ACTION ) );
	}

	/**
	 * @test
	 */
	public function the_templates_carry_no_unescaped_placeholders(): void {
		$post_id                = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->deferred_posts[] = $post_id;

		$html = $this->metabox_end_output( $post_id );

		// The module fills the row through DOM text nodes, so the template has slots, not moustaches.
		$this->assertStringNotContainsString( '{{', $html );
		$this->assertStringContainsString( 'data-tec-slot="name"', $html );
		$this->assertStringContainsString( 'data-tec-slot="price"', $html );
		$this->assertStringContainsString( 'data-tec-slot="capacity"', $html );
	}
}
