<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;

/**
 * Tests for Classic_Editor.
 *
 * These integration tests verify that Classic_Editor filters work correctly
 * by applying the filters rather than calling methods directly.
 */
class Classic_Editor_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_disable_rsvp_form_toggle(): void {
		$enabled = [
			'rsvp'   => true,
			'ticket' => true,
		];

		$result = apply_filters( 'tec_tickets_enabled_ticket_forms', $enabled );

		$this->assertFalse( $result['rsvp'], 'RSVP form toggle should be disabled' );
	}

	public function test_should_preserve_other_ticket_form_toggles(): void {
		$enabled = [
			'rsvp'         => true,
			'ticket'       => true,
			'subscription' => true,
		];

		$result = apply_filters( 'tec_tickets_enabled_ticket_forms', $enabled );

		$this->assertTrue( $result['ticket'], 'Ticket form toggle should remain enabled' );
		$this->assertTrue( $result['subscription'], 'Subscription form toggle should remain enabled' );
	}

	public function test_should_handle_missing_rsvp_key_in_enabled_forms(): void {
		$enabled = [
			'ticket' => true,
		];

		$result = apply_filters( 'tec_tickets_enabled_ticket_forms', $enabled );

		$this->assertFalse( $result['rsvp'], 'RSVP should be set to false even when not present' );
		$this->assertTrue( $result['ticket'], 'Ticket form toggle should remain enabled' );
	}

	public function test_should_remove_rsvp_tickets_from_metabox_list(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create actual tickets using the trait.
		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );

		$rsvp_ticket    = tec_tc_get_ticket( $rsvp_ticket_id );
		$regular_ticket = tec_tc_get_ticket( $regular_ticket_id );

		$ticket_types = [
			'rsvp'   => [ $rsvp_ticket ],
			'ticket' => [ $regular_ticket ],
		];

		$result = apply_filters( 'tec_tickets_editor_list_ticket_types', $ticket_types );

		$this->assertEmpty( $result['rsvp'], 'RSVP tickets should be removed from metabox list' );
	}

	public function test_should_preserve_other_ticket_types_in_metabox_list(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create actual tickets using the trait.
		$rsvp_ticket_id    = $this->create_tc_rsvp_ticket( $post_id );
		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$pass_ticket_id    = $this->create_tc_ticket( $post_id, 20 );

		$rsvp_ticket    = tec_tc_get_ticket( $rsvp_ticket_id );
		$regular_ticket = tec_tc_get_ticket( $regular_ticket_id );
		$pass_ticket    = tec_tc_get_ticket( $pass_ticket_id );

		$ticket_types = [
			'rsvp'   => [ $rsvp_ticket ],
			'ticket' => [ $regular_ticket ],
			'pass'   => [ $pass_ticket ],
		];

		$result = apply_filters( 'tec_tickets_editor_list_ticket_types', $ticket_types );

		$this->assertCount( 1, $result['ticket'], 'Ticket type should preserve its tickets' );
		$this->assertCount( 1, $result['pass'], 'Pass type should preserve its tickets' );
		$this->assertSame( $regular_ticket, $result['ticket'][0], 'Regular ticket should be unchanged' );
		$this->assertSame( $pass_ticket, $result['pass'][0], 'Pass ticket should be unchanged' );
	}

	public function test_should_handle_empty_ticket_types(): void {
		$ticket_types = [
			'rsvp'   => [],
			'ticket' => [],
		];

		$result = apply_filters( 'tec_tickets_editor_list_ticket_types', $ticket_types );

		$this->assertEmpty( $result['rsvp'], 'Empty RSVP array should remain empty' );
		$this->assertEmpty( $result['ticket'], 'Empty ticket array should remain empty' );
	}

	public function test_should_handle_missing_rsvp_key_in_ticket_types(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$regular_ticket_id = $this->create_tc_ticket( $post_id, 10 );
		$regular_ticket    = tec_tc_get_ticket( $regular_ticket_id );

		$ticket_types = [
			'ticket' => [ $regular_ticket ],
		];

		$result = apply_filters( 'tec_tickets_editor_list_ticket_types', $ticket_types );

		$this->assertEmpty( $result['rsvp'], 'RSVP should be set to empty array even when not present' );
		$this->assertCount( 1, $result['ticket'], 'Ticket type should preserve its tickets' );
	}

	public function test_should_handle_multiple_rsvp_tickets_removal(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$rsvp_ticket_ids = $this->create_many_tc_rsvp_tickets( 3, $post_id );
		$rsvp_tickets    = array_map( 'tec_tc_get_ticket', $rsvp_ticket_ids );

		$ticket_types = [
			'rsvp' => $rsvp_tickets,
		];

		$result = apply_filters( 'tec_tickets_editor_list_ticket_types', $ticket_types );

		$this->assertEmpty( $result['rsvp'], 'All RSVP tickets should be removed' );
	}
}
