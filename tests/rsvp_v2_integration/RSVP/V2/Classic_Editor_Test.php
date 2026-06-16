<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;

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

	public function test_save_rsvp_on_post_save_skips_non_ticketable_post_type(): void {
		$filter_called = false;
		add_filter(
			'tec_tickets_rsvp_v2_classic_save_data',
			function( array $data ) use ( &$filter_called ): array {
				$filter_called = true;
				return $data;
			}
		);

		$post_id = static::factory()->post->create( [
			'post_type'   => 'non_ticketable_xyz',
			'post_status' => 'publish',
		] );

		tribe( Classic_Editor::class )->save_rsvp_on_post_save( $post_id );

		$this->assertFalse( $filter_called, 'Filter should not fire for non-ticketable post types' );
	}

	public function test_save_rsvp_on_post_save_skips_when_ticket_type_missing(): void {
		$filter_called = false;
		add_filter(
			'tec_tickets_rsvp_v2_classic_save_data',
			function( array $data ) use ( &$filter_called ): array {
				$filter_called = true;
				return $data;
			}
		);

		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$saved_post = $_POST;
		$_POST      = [ 'tec_tickets_rsvp_enable' => '1' ];

		tribe( Classic_Editor::class )->save_rsvp_on_post_save( $post_id );

		$_POST = $saved_post;

		$this->assertFalse( $filter_called, 'Filter should not fire when ticket_type is absent' );
	}

	public function test_save_rsvp_on_post_save_skips_when_ticket_type_is_not_tc_rsvp(): void {
		$filter_called = false;
		add_filter(
			'tec_tickets_rsvp_v2_classic_save_data',
			function( array $data ) use ( &$filter_called ): array {
				$filter_called = true;
				return $data;
			}
		);

		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$saved_post = $_POST;
		$_POST      = [
			'ticket_type'             => 'default',
			'tec_tickets_rsvp_enable' => '1',
		];

		tribe( Classic_Editor::class )->save_rsvp_on_post_save( $post_id );

		$_POST = $saved_post;

		$this->assertFalse( $filter_called, 'Filter should not fire when ticket_type is not tc-rsvp' );
	}

	public function test_save_rsvp_on_post_save_skips_without_rsvp_enable_flag(): void {
		$filter_called = false;
		add_filter(
			'tec_tickets_rsvp_v2_classic_save_data',
			function( array $data ) use ( &$filter_called ): array {
				$filter_called = true;
				return $data;
			}
		);

		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$saved_post = $_POST;
		$_POST      = [ 'ticket_type' => Constants::TC_RSVP_TYPE ];

		tribe( Classic_Editor::class )->save_rsvp_on_post_save( $post_id );

		$_POST = $saved_post;

		$this->assertFalse( $filter_called, 'Filter should not fire when tec_tickets_rsvp_enable is absent' );
	}

	public function test_save_rsvp_maps_capacity_with_limit(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
			'rsvp_limit'              => '50',
		] );

		$this->assertNotNull( $data, 'Save data filter should have been called' );
		$this->assertSame( Global_Stock::OWN_STOCK_MODE, $data['tribe-ticket']['mode'] );
		$this->assertSame( 50, $data['tribe-ticket']['capacity'] );
	}

	public function test_save_rsvp_maps_unlimited_capacity_when_no_limit(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
			'rsvp_limit'              => '',
		] );

		$this->assertNotNull( $data, 'Save data filter should have been called' );
		$this->assertSame( '', $data['tribe-ticket']['mode'] );
	}

	public function test_save_rsvp_maps_show_not_going_when_enabled(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
			'show_not_going'          => '1',
		] );

		$this->assertNotNull( $data, 'Save data filter should have been called' );
		$this->assertTrue( $data['show_not_going'] );
	}

	public function test_save_rsvp_maps_show_not_going_false_when_absent(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
		] );

		$this->assertNotNull( $data, 'Save data filter should have been called' );
		$this->assertFalse( $data['show_not_going'] );
	}

	public function test_save_rsvp_maps_date_fields(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$data = $this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
			'rsvp_start_date'         => '2026-01-01',
			'rsvp_start_time'         => '08:00:00',
			'rsvp_end_date'           => '2026-12-31',
			'rsvp_end_time'           => '23:59:59',
		] );

		$this->assertNotNull( $data, 'Save data filter should have been called' );
		$this->assertSame( '2026-01-01', $data['ticket_start_date'] );
		$this->assertSame( '08:00:00', $data['ticket_start_time'] );
		$this->assertSame( '2026-12-31', $data['ticket_end_date'] );
		$this->assertSame( '23:59:59', $data['ticket_end_time'] );
	}

	public function test_save_rsvp_applies_classic_save_data_filter(): void {
		$filter_called = false;
		add_filter(
			'tec_tickets_rsvp_v2_classic_save_data',
			function( array $data, int $post_id, array $post_data ) use ( &$filter_called ): array {
				$filter_called = true;
				return $data;
			},
			10,
			3
		);

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->capture_save_data( $post_id, [
			'ticket_type'             => Constants::TC_RSVP_TYPE,
			'tec_tickets_rsvp_enable' => '1',
		] );

		$this->assertTrue( $filter_called, 'tec_tickets_rsvp_v2_classic_save_data filter should be applied on save' );
	}

	private function capture_save_data( int $post_id, array $post_data ): ?array {
		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$captured = null;
		$callback = function( array $data ) use ( &$captured ): array {
			$captured = $data;
			return $data;
		};
		add_filter( 'tec_tickets_rsvp_v2_classic_save_data', $callback );

		$saved_post = $_POST;
		$_POST      = $post_data;

		tribe( Classic_Editor::class )->save_rsvp_on_post_save( $post_id );

		$_POST = $saved_post;
		remove_filter( 'tec_tickets_rsvp_v2_classic_save_data', $callback );

		return $captured;
	}
}
