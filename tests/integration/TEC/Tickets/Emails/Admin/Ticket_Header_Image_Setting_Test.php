<?php

namespace TEC\Tickets\Emails\Admin;

use Codeception\TestCase\WPTestCase;

class Ticket_Header_Image_Setting_Test extends WPTestCase {
	/**
	 * @test
	 * it should hide the classic editor header image setting when Tickets Emails is enabled
	 */
	public function it_should_hide_the_classic_editor_header_image_setting_when_tickets_emails_is_enabled(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );
		$post_id = static::factory()->post->create();

		$html = tribe( 'tickets.admin.views' )->template( 'editor/panel/header-image', [ 'post_id' => $post_id ], false );

		$this->assertFalse( $html );
	}

	/**
	 * @test
	 * it should render the classic editor header image setting when Tickets Emails is disabled
	 */
	public function it_should_render_the_classic_editor_header_image_setting_when_tickets_emails_is_disabled(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_false' );
		$post_id = static::factory()->post->create();

		$html = tribe( 'tickets.admin.views' )->template( 'editor/panel/header-image', [ 'post_id' => $post_id ], false );

		$this->assertIsString( $html );
		$this->assertStringContainsString( 'tribe-tickets-image', $html );
	}

	/**
	 * @test
	 * it should tell the block editor to hide the header image setting when Tickets Emails is enabled
	 */
	public function it_should_tell_the_block_editor_to_hide_the_header_image_setting_when_tickets_emails_is_enabled(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_true' );

		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertFalse( $config['tickets']['ticketHeaderImageEnabled'] );
	}

	/**
	 * @test
	 * it should tell the block editor to show the header image setting when Tickets Emails is disabled
	 */
	public function it_should_tell_the_block_editor_to_show_the_header_image_setting_when_tickets_emails_is_disabled(): void {
		add_filter( 'tec_tickets_emails_is_enabled', '__return_false' );

		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertTrue( $config['tickets']['ticketHeaderImageEnabled'] );
	}

	/**
	 * @test
	 * it should keep the stored header image when the settings are saved without the header image field
	 */
	public function it_should_keep_the_stored_header_image_when_the_settings_are_saved_without_the_header_image_field(): void {
		$post_id       = static::factory()->post->create();
		$attachment_id = static::factory()->attachment->create();
		$handler       = tribe( 'tickets.handler' );
		update_post_meta( $post_id, $handler->key_image_header, $attachment_id );

		$handler->save_form_settings( $post_id, [ 'event_capacity' => 10 ] );

		$this->assertEquals( $attachment_id, get_post_meta( $post_id, $handler->key_image_header, true ) );
	}

	/**
	 * @test
	 * it should remove the stored header image when the header image field is sent empty
	 */
	public function it_should_remove_the_stored_header_image_when_the_header_image_field_is_sent_empty(): void {
		$post_id       = static::factory()->post->create();
		$attachment_id = static::factory()->attachment->create();
		$handler       = tribe( 'tickets.handler' );
		update_post_meta( $post_id, $handler->key_image_header, $attachment_id );

		$handler->save_form_settings( $post_id, [ 'header_image_id' => '' ] );

		$this->assertSame( '', get_post_meta( $post_id, $handler->key_image_header, true ) );
	}
}
