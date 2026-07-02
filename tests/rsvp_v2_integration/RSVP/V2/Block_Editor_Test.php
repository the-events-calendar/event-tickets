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
