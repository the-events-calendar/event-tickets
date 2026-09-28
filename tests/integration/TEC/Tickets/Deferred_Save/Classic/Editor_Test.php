<?php

namespace TEC\Tickets\Deferred_Save\Classic;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Deferred_Save\Classic_Save;

class Editor_Test extends WPTestCase {
	protected function metabox_end_output( int $post_id ): string {
		ob_start();
		do_action( 'tribe_tickets_metabox_end', $post_id, null );

		return (string) ob_get_clean();
	}

	/**
	 * @test
	 */
	public function it_should_print_nothing_without_a_post(): void {
		$this->assertSame( '', $this->metabox_end_output( 0 ) );
	}

	/**
	 * @test
	 */
	public function it_should_print_the_nonce_the_container_and_the_templates(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );
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
	public function it_should_print_templates_without_unescaped_placeholders(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$html = $this->metabox_end_output( $post_id );

		// The module fills the row through DOM text nodes, so the template has slots, not moustaches.
		$this->assertStringNotContainsString( '{{', $html );
		$this->assertStringContainsString( 'data-tec-slot="name"', $html );
		$this->assertStringContainsString( 'data-tec-slot="price"', $html );
		$this->assertStringContainsString( 'data-tec-slot="capacity"', $html );
	}

	/**
	 * @test
	 */
	public function it_should_mirror_the_saved_list_markup_in_the_staged_row_and_table(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$html = $this->metabox_end_output( $post_id );

		// The row carries the wrappers, labels and icon buttons a saved row has.
		$this->assertStringContainsString( 'tribe-tickets__tickets-editor-ticket-name-title', $html );
		$this->assertStringContainsString( 'class="tec-tickets-price amount" data-tec-slot="price"', $html );
		$this->assertStringContainsString( 'class="ticket_edit_text" data-tec-slot="name"', $html );
		$this->assertStringContainsString( 'class="ticket_delete_text" data-tec-slot="name"', $html );
		$this->assertRegExp( '/<td class="ticket_price" data-label="[^"]+">/', $html );
		// The table for a post with no tickets yet is the real list table, wrapper and header icon included.
		$this->assertStringContainsString( '<div class="ticket_list_wrapper">', $html );
		$this->assertStringContainsString( 'tribe_ticket_list_table tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed', $html );
		$this->assertStringContainsString( 'tec-tickets-icon__ticket-type', $html );
		$this->assertStringContainsString( '<th class="ticket_price">', $html );
	}
}
