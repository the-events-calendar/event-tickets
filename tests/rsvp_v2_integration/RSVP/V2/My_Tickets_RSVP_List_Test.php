<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Tickets_View as Tickets_View;

/**
 * Regression tests for the classic My Tickets RSVP list flow when the event has TC-RSVP tickets.
 *
 * TC-RSVP attendees must NOT render through the legacy `tickets/orders-rsvp.php` template: they
 * are Tickets Commerce attendees and render through the `tickets/orders-tc-tickets.php` ->
 * `tickets/my-tickets` templates that provide the RSVP V2 UI. The RSVP V1 handler cannot build
 * attendee data for TC-RSVP attendees, so the V2 `Attendees::get_rsvp_attendees_by_user_id`
 * implementation provides it through the `tec_tickets_rsvp_get_attendees_by_user_id_pre` filter.
 * These tests lock that flow.
 */
class My_Tickets_RSVP_List_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * The value of `$GLOBALS['post']` before the test modified it, to restore in `tearDown()`.
	 *
	 * @var \WP_Post|null
	 */
	private $original_post;

	protected function setUp(): void {
		parent::setUp();

		$this->original_post = $GLOBALS['post'] ?? null;
	}

	protected function tearDown(): void {
		wp_set_current_user( 0 );

		$GLOBALS['post'] = $this->original_post;

		parent::tearDown();
	}

	/**
	 * Create a user, a post, a TC-RSVP ticket, an order (owned by the user) and its attendees.
	 *
	 * Creates a regular TC ticket first (so that Order_Maker can generate attendees), then
	 * retroactively sets the _type meta to tc-rsvp.
	 *
	 * @param string $rsvp_status    The RSVP status to set on the attendees ('yes' or 'no').
	 * @param string $purchaser_name The purchaser name for the order.
	 *
	 * @return array{user_id: int, post_id: int, ticket_id: int, order_id: int, attendee_ids: int[]}
	 */
	private function create_rsvp_fixture( string $rsvp_status = 'yes', string $purchaser_name = 'Test User' ): array {
		$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		// Create as a regular TC ticket (price 0) so the cart-based order creation works.
		$ticket_id = $this->create_tc_ticket( $post_id, 0 );

		$order = $this->create_order(
			[ $ticket_id => 2 ],
			[
				'purchaser_user_id'    => $user_id,
				'purchaser_full_name'  => $purchaser_name,
				'purchaser_first_name' => 'Test',
				'purchaser_last_name'  => 'User',
				'purchaser_email'      => 'test@example.com',
			]
		);

		// Retroactively set the ticket type to TC-RSVP.
		update_post_meta( $ticket_id, '_type', Constants::TC_RSVP_TYPE );

		$attendees    = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_ids = array_map( static fn( $attendee ) => (int) $attendee['attendee_id'], $attendees );

		foreach ( $attendee_ids as $attendee_id ) {
			update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, $rsvp_status );
		}

		return [
			'user_id'      => $user_id,
			'post_id'      => $post_id,
			'ticket_id'    => $ticket_id,
			'order_id'     => (int) $order->ID,
			'attendee_ids' => $attendee_ids,
		];
	}

	/**
	 * Renders the My Tickets RSVP template the way the tickets page does: as the current user,
	 * with the post set as the global post.
	 *
	 * @param int $post_id The post ID.
	 * @param int $user_id The user ID to render as.
	 *
	 * @return string The rendered template HTML.
	 */
	private function render_orders_rsvp_template( int $post_id, int $user_id ): string {
		wp_set_current_user( $user_id );

		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		return tribe( 'tickets.editor.template' )->template( 'tickets/orders-rsvp', [], false );
	}

	/**
	 * Renders the My Tickets TC tickets template the way the tickets page does: as the current user,
	 * with the post set as the global post.
	 *
	 * @param int $post_id The post ID.
	 * @param int $user_id The user ID to render as.
	 *
	 * @return string The rendered template HTML.
	 */
	private function render_orders_tc_tickets_template( int $post_id, int $user_id ): string {
		wp_set_current_user( $user_id );

		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		return tribe( 'tickets.editor.template' )->template( 'tickets/orders-tc-tickets', [], false );
	}

	public function test_should_return_tc_rsvp_attendees_for_user_and_event(): void {
		// Arrange.
		$fixture = $this->create_rsvp_fixture( 'yes' );

		// Act.
		$attendees = tribe( 'tickets.rsvp' )->get_attendees_by_user_id( $fixture['user_id'], $fixture['post_id'] );

		// Assert.
		$this->assertCount( 2, $attendees, 'Both TC-RSVP attendees should be returned' );

		$first = $attendees[0];
		$this->assertSame( 'yes', $first['order_status'], 'Going attendees should map to the RSVP "yes" status' );
		$this->assertTrue( $first['ticket_exists'], 'The attendee ticket should exist' );
		$this->assertNotEmpty( $first['purchase_time'], 'The purchase time should be populated' );
		$this->assertSame( $fixture['order_id'], (int) $first['order_id'], 'The order ID should be the Tickets Commerce order' );
		$this->assertSame( get_post( $fixture['ticket_id'] )->post_title, $first['ticket'], 'The ticket name should be the ticket title' );
	}

	/**
	 * Data provider for the RSVP status mapping scenarios.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function rsvp_status_provider(): array {
		return [
			'going'     => [ 'yes', 'yes' ],
			'not going' => [ 'no', 'no' ],
		];
	}

	/**
	 * @dataProvider rsvp_status_provider
	 */
	public function test_should_map_the_rsvp_status( string $rsvp_status, string $expected_order_status ): void {
		// Arrange.
		$fixture = $this->create_rsvp_fixture( $rsvp_status );

		// Act.
		$attendees = tribe( 'tickets.rsvp' )->get_attendees_by_user_id( $fixture['user_id'], $fixture['post_id'] );

		// Assert.
		$this->assertCount( 2, $attendees );
		$this->assertSame( $expected_order_status, $attendees[0]['order_status'], 'The RSVP status meta should map to the order status' );
	}

	public function test_should_return_empty_when_user_has_no_rsvp_attendees(): void {
		// Arrange.
		$fixture       = $this->create_rsvp_fixture( 'yes' );
		$other_user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		// Act.
		$attendees = tribe( 'tickets.rsvp' )->get_attendees_by_user_id( $other_user_id, $fixture['post_id'] );

		// Assert.
		$this->assertSame( [], $attendees, 'A user without RSVPs for the event should get an empty list' );
	}

	public function test_should_group_rsvp_attendees_by_purchaser(): void {
		// Arrange.
		$fixture = $this->create_rsvp_fixture( 'yes', 'Jane Doe' );

		// Act.
		$groups = Tickets_View::instance()->get_event_rsvp_attendees_by_purchaser( $fixture['post_id'], $fixture['user_id'] );

		// Assert.
		$this->assertCount( 1, $groups, 'Both attendees of the same purchaser should be in one group' );

		$group = reset( $groups );
		$this->assertCount( 2, $group );
		$this->assertSame( 'Jane Doe', $group[0]['purchaser_name'] );
	}

	public function test_should_not_render_the_legacy_rsvp_list_for_tc_rsvp_attendees(): void {
		// Arrange.
		$fixture = $this->create_rsvp_fixture( 'yes' );

		// Act.
		$html = $this->render_orders_rsvp_template( $fixture['post_id'], $fixture['user_id'] );

		// Assert.
		$this->assertSame( '', $html, 'TC-RSVP attendees should not render through the legacy RSVP list template' );
	}

	public function test_should_render_tc_rsvp_attendees_through_the_tc_my_tickets_templates(): void {
		// Arrange.
		$fixture = $this->create_rsvp_fixture( 'yes', 'Test User' );

		// Act.
		$html = $this->render_orders_tc_tickets_template( $fixture['post_id'], $fixture['user_id'] );

		// Assert.
		$this->assertStringContainsString( 'tribe-orders-list', $html, 'The Tickets Commerce orders list should be rendered' );
		$this->assertStringContainsString( 'RSVPs', $html, 'The RSVP ticket type title should be rendered' );
		$this->assertStringContainsString( 'Reserved by Test User', $html, 'The RSVP purchaser details should be rendered' );
		$this->assertStringContainsString( 'ticket-status', $html, 'The RSVP response status should be rendered' );
		$this->assertStringContainsString( 'Test User', $html, 'The attendee name should be rendered' );
	}

	public function test_should_not_render_the_rsvp_list_for_users_without_rsvps(): void {
		// Arrange.
		$fixture       = $this->create_rsvp_fixture( 'yes' );
		$other_user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		// Act.
		$html = $this->render_orders_rsvp_template( $fixture['post_id'], $other_user_id );

		// Assert.
		$this->assertSame( '', $html, 'The template should not render for users without RSVPs' );
	}

	public function test_should_detect_tc_rsvp_attendees_for_the_user_and_event(): void {
		// Arrange.
		$fixture       = $this->create_rsvp_fixture( 'yes' );
		$other_user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );

		// Act & Assert.
		$this->assertTrue(
			Tickets_View::instance()->has_rsvp_v2_attendees( $fixture['post_id'], $fixture['user_id'] ),
			'The user with TC-RSVP attendees should be detected as having RSVP V2 attendees'
		);
		$this->assertFalse(
			Tickets_View::instance()->has_rsvp_v2_attendees( $fixture['post_id'], $other_user_id ),
			'A user without RSVP attendees should not be detected as having RSVP V2 attendees'
		);
	}
}
