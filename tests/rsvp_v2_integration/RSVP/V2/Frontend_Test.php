<?php

namespace TEC\Tickets\RSVP\V2;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;

/**
 * Tests for Frontend.
 *
 * These integration tests verify that Frontend filters work correctly
 * by applying the filters rather than calling methods directly.
 */
class Frontend_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;
	use SnapshotAssertions;

	/**
	 * Get the tickets editor template instance.
	 *
	 * @return Tickets_Editor_Template
	 */
	private function get_template(): Tickets_Editor_Template {
		return tribe( 'tickets.editor.template' );
	}

	/**
	 * Helper to create a user, a post, a TC-RSVP ticket, an order (owned by the user),
	 * and return the attendee ID.
	 *
	 * Creates a regular TC ticket first (so that Order_Maker can generate attendees),
	 * then retroactively sets the _type meta to tc-rsvp.
	 *
	 * @param string $rsvp_status The RSVP status to set on the attendee ('yes' or 'no').
	 *
	 * @return array{user_id: int, post_id: int, ticket_id: int, attendee_id: int}
	 */
	private function create_rsvp_order_with_attendee( string $rsvp_status = 'yes' ): array {
		$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		// Create as a regular TC ticket (price 0) so the cart-based order creation works.
		$ticket_id = $this->create_tc_ticket( $post_id, 0 );

		$order = $this->create_order(
			[ $ticket_id => 1 ],
			[ 'purchaser_user_id' => $user_id ]
		);

		// Retroactively set the ticket type to TC-RSVP.
		update_post_meta( $ticket_id, '_type', Constants::TC_RSVP_TYPE );

		$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_id = $attendees[0]['attendee_id'];

		// Set the RSVP status meta on the attendee.
		update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, $rsvp_status );

		wp_set_current_user( $user_id );

		return [
			'user_id'     => $user_id,
			'post_id'     => $post_id,
			'ticket_id'   => $ticket_id,
			'attendee_id' => $attendee_id,
		];
	}

	public function test_should_return_original_content_when_no_tc_rsvp_tickets(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );

		// Create a regular TC ticket (not RSVP).
		$this->create_tc_ticket( $post_id, 10 );

		$template = $this->get_template();
		$args     = [
			'active_rsvps' => [],
		];

		$result = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'original content',
			$args,
			$template,
			$post,
			false
		);

		$this->assertSame( 'original content', $result, 'Should return original content when no TC-RSVP tickets' );
	}

	public function test_should_append_content_when_tc_rsvp_ticket_exists(): void {
		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$post    = get_post( $post_id );

		// Create a TC-RSVP ticket.
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );
		$rsvp_ticket    = tribe( Module::class )->get_ticket( $post_id, $rsvp_ticket_id );

		$template = $this->get_template();
		// This would be set in the global context in the real application.
		$template->set( 'threshold', 10 );
		$args     = [
			'active_rsvps' => [ $rsvp_ticket ],
		];

		$result = apply_filters(
			'tec_tickets_front_end_rsvp_form_template_content',
			'original content',
			$args,
			$template,
			$post,
			false
		);

		$this->assertStringStartsWith( 'original content', $result, 'Should preserve original content' );
	}

	public function test_do_not_display_rsvp_v1_form_should_remove_hooks_for_rsvp_handler(): void {
		$frontend = tribe( Frontend::class );

		// Get the RSVP V1 handler.
		$rsvp_handler     = tribe( 'tickets.rsvp' );
		$ticket_form_hook = 'test_ticket_form_hook';

		// Add the hooks that should be removed.
		add_action( $ticket_form_hook, [ $rsvp_handler, 'maybe_add_front_end_tickets_form' ], 5 );
		add_filter( $ticket_form_hook, [ $rsvp_handler, 'show_tickets_unavailable_message' ], 6 );
		add_filter( 'the_content', [ $rsvp_handler, 'front_end_tickets_form_in_content' ], 11 );
		add_filter( 'the_content', [ $rsvp_handler, 'show_tickets_unavailable_message_in_content' ], 12 );

		// Call the method.
		$frontend->do_not_display_rsvp_v1_tickets_form( $rsvp_handler, $ticket_form_hook );

		// Verify hooks were removed.
		$this->assertFalse(
			has_action( $ticket_form_hook, [ $rsvp_handler, 'maybe_add_front_end_tickets_form' ] ),
			'maybe_add_front_end_tickets_form should be removed'
		);
		$this->assertFalse(
			has_filter( $ticket_form_hook, [ $rsvp_handler, 'show_tickets_unavailable_message' ] ),
			'show_tickets_unavailable_message should be removed'
		);
		$this->assertFalse(
			has_filter( 'the_content', [ $rsvp_handler, 'front_end_tickets_form_in_content' ] ),
			'front_end_tickets_form_in_content should be removed'
		);
		$this->assertFalse(
			has_filter( 'the_content', [ $rsvp_handler, 'show_tickets_unavailable_message_in_content' ] ),
			'show_tickets_unavailable_message_in_content should be removed'
		);
	}

	public function test_do_not_display_rsvp_v1_form_should_not_affect_non_rsvp_handlers(): void {
		$frontend = tribe( Frontend::class );

		// Use a non-RSVP tickets handler (e.g., TC module).
		$tc_handler       = tribe( Module::class );
		$ticket_form_hook = 'test_ticket_form_hook_2';

		// Add a hook to verify it's not removed.
		add_action( $ticket_form_hook, [ $tc_handler, 'get_tickets' ], 5 );

		// Call the method with non-RSVP handler.
		$frontend->do_not_display_rsvp_v1_tickets_form( $tc_handler, $ticket_form_hook );

		// Verify hook was NOT removed (method should return early).
		$this->assertNotFalse(
			has_action( $ticket_form_hook, [ $tc_handler, 'get_tickets' ] ),
			'Hooks should not be removed for non-RSVP handlers'
		);
	}

	/**
	 * Data provider for update_attendee_data early return scenarios.
	 *
	 * Each closure sets up its own scenario and returns the attendee_data,
	 * attendee_id, and expected RSVP status meta value (or null if no meta should exist).
	 */
	public function update_attendee_data_early_return_provider(): Generator {
		yield 'returns early when order_status is empty' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				$attendee_data = [ 'order_status' => '' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					'yes', // Status should remain unchanged.
				];
			},
		];

		yield 'returns early when user is not logged in' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				// Log out the user.
				wp_set_current_user( 0 );

				$attendee_data = [ 'order_status' => 'not-going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					'yes', // Status should remain unchanged because user is not logged in.
				];
			},
		];

		yield 'returns early for non-RSVP ticket type' => [
			function () {
				$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
				wp_set_current_user( $user_id );

				$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

				// Create a regular TC ticket (not RSVP) with a price.
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );

				$order = $this->create_order(
					[ $ticket_id => 1 ],
					[
						'purchaser_user_id'    => $user_id,
						'purchaser_full_name'  => 'Test User',
						'purchaser_first_name' => 'Test',
						'purchaser_last_name'  => 'User',
						'purchaser_email'      => 'test@example.com',
					]
				);

				$attendees   = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
				$attendee_id = $attendees[0]['attendee_id'];

				// Set some initial meta to verify it does not get changed.
				update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'yes' );

				$attendee_data = [ 'order_status' => 'not-going' ];

				return [
					$attendee_data,
					$attendee_id,
					'yes', // Status should remain unchanged because the ticket is not TC-RSVP.
				];
			},
		];

		yield 'returns early when order belongs to a different user' => [
			function () {
				// Create order as user A.
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				// Now switch to a different user.
				$other_user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
				wp_set_current_user( $other_user_id );

				$attendee_data = [ 'order_status' => 'not-going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					'yes', // Status should remain unchanged because the current user does not own the order.
				];
			},
		];
	}

	/**
	 * @dataProvider update_attendee_data_early_return_provider
	 */
	public function test_update_attendee_data_returns_early( Closure $scenario ): void {
		[ $attendee_data, $attendee_id, $expected_status ] = $scenario();

		$frontend = tribe( Frontend::class );
		$frontend->update_attendee_data( $attendee_data, $attendee_id );

		$this->assertSame(
			$expected_status,
			get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true ),
			'RSVP status meta should not be changed when the method returns early.'
		);
	}

	/**
	 * Data provider for update_attendee_data status change scenarios.
	 *
	 * Each closure creates a scenario with a known initial status and an attempted update,
	 * and returns the expected final status.
	 */
	public function update_attendee_data_status_change_provider(): Generator {
		yield 'does not update when status has not changed (going -> going)' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				$attendee_data = [ 'order_status' => 'going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					$fixture['user_id'],
					'yes', // Already 'yes', so 'going' (mapped to 'yes') should cause no change.
					false,  // Expect no update.
				];
			},
		];

		yield 'updates status from going to not-going' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				$attendee_data = [ 'order_status' => 'not-going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					$fixture['user_id'],
					'no', // Should be updated from 'yes' to 'no'.
					true,  // Expect update.
				];
			},
		];

		yield 'updates status from not-going to going' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'no' );

				$attendee_data = [ 'order_status' => 'going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					$fixture['user_id'],
					'yes', // Should be updated from 'no' to 'yes'.
					true,   // Expect update.
				];
			},
		];

		yield 'defaults to yes when no meta exists and going is submitted (no change)' => [
			function () {
				$fixture = $this->create_rsvp_order_with_attendee( 'yes' );

				// Remove the meta entirely to simulate an attendee that never had it set.
				delete_post_meta( $fixture['attendee_id'], Constants::RSVP_STATUS_META_KEY );

				$attendee_data = [ 'order_status' => 'going' ];

				return [
					$attendee_data,
					$fixture['attendee_id'],
					$fixture['user_id'],
					'', // No meta exists yet and method should not create it (default is 'yes', submitted is 'yes').
					false, // Expect no update since default='yes' and submitted='yes' are the same.
				];
			},
		];
	}

	/**
	 * @dataProvider update_attendee_data_status_change_provider
	 */
	public function test_update_attendee_data_status_changes( Closure $scenario ): void {
		[ $attendee_data, $attendee_id, $user_id, $expected_status, $expect_update ] = $scenario();

		// Ensure the correct user is set (the order owner).
		wp_set_current_user( $user_id );

		$frontend = tribe( Frontend::class );
		$frontend->update_attendee_data( $attendee_data, $attendee_id );

		$actual_status = get_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, true );

		if ( $expect_update ) {
			$this->assertSame(
				$expected_status,
				$actual_status,
				'RSVP status meta should be updated to the new value.'
			);
		} else {
			$this->assertSame(
				$expected_status,
				$actual_status,
				'RSVP status meta should not have been changed.'
			);
		}
	}

	/**
	 * Data provider for render_my_tickets_ticket_status early return scenarios.
	 */
	public function render_my_tickets_ticket_status_early_return_provider(): Generator {
		yield 'returns early when ticket_type is empty' => [
			function () {
				return [
					'ticket_type' => '',
					'product_id'  => 1,
					'ID'          => 1,
				];
			},
		];

		yield 'returns early when ticket_type is not tc-rsvp' => [
			function () {
				return [
					'ticket_type' => 'default',
					'product_id'  => 1,
					'ID'          => 1,
				];
			},
		];
	}

	/**
	 * @dataProvider render_my_tickets_ticket_status_early_return_provider
	 */
	public function test_render_my_tickets_ticket_status_returns_early( Closure $scenario ): void {
		$attendee = $scenario();

		$frontend = tribe( Frontend::class );

		ob_start();
		$frontend->render_my_tickets_ticket_status( $attendee );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Should produce no output for non-TC-RSVP attendee.' );
	}

	/**
	 * Replaces dynamic IDs with placeholders for stable snapshots.
	 *
	 * @param string $html The HTML to process.
	 * @param array  $ids  Associative array of placeholder => ID.
	 *
	 * @return string
	 */
	private function placehold_ids( string $html, array $ids ): string {
		$ids = array_filter( $ids, static fn( $id ) => $id !== null );

		return str_replace(
			array_map( 'strval', array_values( $ids ) ),
			array_map( static fn( string $name ) => "{{ $name }}", array_keys( $ids ) ),
			$html
		);
	}

	/**
	 * Data provider for render_my_tickets_ticket_status rendering scenarios.
	 */
	public function render_my_tickets_ticket_status_render_provider(): Generator {
		yield 'going with show_not_going enabled' => [
			'yes',
			true,
		];

		yield 'not going with show_not_going enabled' => [
			'no',
			true,
		];

		yield 'going with show_not_going disabled' => [
			'yes',
			false,
		];
	}

	/**
	 * @dataProvider render_my_tickets_ticket_status_render_provider
	 */
	public function test_render_my_tickets_ticket_status_renders_output( string $rsvp_status, bool $show_not_going ): void {
		$fixture = $this->create_rsvp_order_with_attendee( $rsvp_status );

		if ( $show_not_going ) {
			update_post_meta( $fixture['ticket_id'], Constants::SHOW_NOT_GOING_META_KEY, '1' );
		}

		$attendee = [
			'ticket_type' => Constants::TC_RSVP_TYPE,
			'product_id'  => $fixture['ticket_id'],
			'ID'          => $fixture['attendee_id'],
		];

		$frontend = tribe( Frontend::class );

		ob_start();
		$frontend->render_my_tickets_ticket_status( $attendee );
		$output = ob_get_clean();

		$this->assertNotEmpty( $output, 'Should produce output for a TC-RSVP attendee.' );

		$output = $this->placehold_ids( $output, [
			'ATTENDEE_ID' => $fixture['attendee_id'],
			'TICKET_ID'   => $fixture['ticket_id'],
			'POST_ID'     => $fixture['post_id'],
		] );

		$this->assertMatchesHtmlSnapshot( $output );
	}
}
