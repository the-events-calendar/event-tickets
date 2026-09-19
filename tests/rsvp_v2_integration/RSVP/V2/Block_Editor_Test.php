<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * Tests for Block_Editor.
 *
 * These integration tests verify that Block_Editor filters work correctly
 * by applying the filters rather than calling methods directly.
 */
class Block_Editor_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_add_rsvp_v2_config_to_editor_config(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertArrayHasKey( 'tickets', $config, 'Config should have tickets key' );
		$this->assertArrayHasKey( 'rsvpV2', $config['tickets'], 'Tickets config should have rsvpV2 key' );
	}

	public function test_rsvp_v2_config_should_be_enabled(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertTrue(
			$config['tickets']['rsvpV2']['enabled'],
			'RSVP V2 should be enabled in editor config'
		);
	}

	public function test_rsvp_v2_config_should_have_tickets_endpoint(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertSame(
			'/tec/v1/tickets',
			$config['tickets']['rsvpV2']['ticketsEndpoint'],
			'RSVP V2 should have correct tickets endpoint'
		);
	}

	public function test_rsvp_v2_config_should_have_correct_ticket_type(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertSame(
			Constants::TC_RSVP_TYPE,
			$config['tickets']['rsvpV2']['ticketType'],
			'RSVP V2 should have correct ticket type'
		);
	}

	public function test_rsvp_v2_config_should_include_initial_ticket_key(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertArrayHasKey(
			'initialTicket',
			$config['tickets']['rsvpV2'],
			'RSVP V2 config should include initialTicket key'
		);
	}

	public function test_rsvp_v2_config_initial_ticket_is_null_without_post(): void {
		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertNull(
			$config['tickets']['rsvpV2']['initialTicket'],
			'initialTicket should be null when no post context exists'
		);
	}

	public function test_rsvp_v2_config_initial_ticket_matches_event_rsvp(): void {
		wp_set_current_user( 1 );

		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'RSVP Event',
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);

		$ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		global $post;
		$post = get_post( $post_id );

		$config = apply_filters( 'tribe_editor_config', [] );

		$this->assertIsArray( $config['tickets']['rsvpV2']['initialTicket'] );
		$this->assertSame( $ticket_id, $config['tickets']['rsvpV2']['initialTicket']['id'] );

		wp_set_current_user( 0 );
	}

	/**
	 * The preloaded RSVP ticket is formatted through WP_REST_Posts_Controller::prepare_item_for_response(),
	 * which calls setup_postdata() and leaves the global $post pointing at the ticket. Verify the global post
	 * is restored to the event so other admin metaboxes (e.g. Virtual Events) keep rendering the event.
	 */
	public function test_rsvp_v2_config_preserves_global_post_when_preloading_initial_ticket(): void {
		wp_set_current_user( 1 );

		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'RSVP Event',
				'post_status' => 'publish',
				'post_type'   => 'page',
			]
		);

		$this->create_tc_rsvp_ticket( $post_id );

		global $post;
		$post = get_post( $post_id );

		apply_filters( 'tribe_editor_config', [] );

		$this->assertSame( $post_id, $post->ID, 'Global $post should still reference the event, not the RSVP ticket' );
		$this->assertSame( 'page', $post->post_type, 'Global $post post type should be unchanged' );

		wp_set_current_user( 0 );
	}

	public function test_should_preserve_existing_config_when_adding_rsvp_v2(): void {
		$existing_config = [
			'someKey'   => 'someValue',
			'tickets'   => [
				'existingTicketConfig' => true,
			],
			'otherData' => [
				'nested' => 'value',
			],
		];

		$config = apply_filters( 'tribe_editor_config', $existing_config );

		$this->assertSame( 'someValue', $config['someKey'], 'Existing top-level config should be preserved' );
		$this->assertTrue(
			$config['tickets']['existingTicketConfig'],
			'Existing tickets config should be preserved'
		);
		$this->assertSame( 'value', $config['otherData']['nested'], 'Other nested config should be preserved' );
		$this->assertArrayHasKey( 'rsvpV2', $config['tickets'], 'RSVP V2 config should be added' );
	}

	public function test_rsvp_block_editor_style_args_should_set_editor_style_handles(): void {
		$args = apply_filters( 'register_block_type_args', [], 'tribe/rsvp' );

		$this->assertArrayHasKey(
			'editor_style_handles',
			$args,
			'RSVP block registration should include editor_style_handles so styles reach the editor canvas iframe'
		);

		$this->assertContains(
			'tec-tickets-commerce-rsvp-style',
			$args['editor_style_handles'],
			'RSVP frontend styles should be part of the editor canvas styles'
		);
		$this->assertContains(
			Block_Editor::EDITOR_MIRROR_STYLE,
			$args['editor_style_handles'],
			'RSVP editor mirror styles should be part of the editor canvas styles'
		);
	}

	public function test_rsvp_block_editor_style_args_should_not_set_camel_case_editor_style(): void {
		$args = apply_filters( 'register_block_type_args', [], 'tribe/rsvp' );

		$this->assertArrayNotHasKey(
			'editorStyle',
			$args,
			'WP_Block_Type ignores the camelCase editorStyle key; use editor_style_handles instead'
		);
	}

	public function test_should_enqueue_tickets_block_assets_for_tickets_block(): void {
		$parsed_block = [
			'blockName' => 'tribe/tickets',
			'attrs'     => [],
		];

		// Apply the pre_render_block filter.
		$result = apply_filters( 'pre_render_block', null, $parsed_block );

		// The filter should return null (not prevent rendering).
		$this->assertNull( $result, 'Filter should not prevent block rendering' );
	}

	public function test_should_not_affect_non_tickets_blocks(): void {
		$parsed_block = [
			'blockName' => 'core/paragraph',
			'attrs'     => [],
		];

		// Apply the pre_render_block filter.
		$result = apply_filters( 'pre_render_block', null, $parsed_block );

		// The filter should return null (pass through).
		$this->assertNull( $result, 'Filter should not affect non-tickets blocks' );
	}
}
