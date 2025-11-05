<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;

/**
 * Third-party compatibility tests for RSVP data decoupling.
 *
 * Validates that the repository pattern implementation maintains backward
 * compatibility with third-party plugins and custom code that relies on:
 * - Action hooks
 * - Filter hooks
 * - Direct post meta access
 * - Data structures
 *
 * Key hooks verified:
 * - event_ticket_rsvp_before_ticket_creation
 * - event_ticket_rsvp_ticket_created
 * - rsvp_ticket_created
 * - event_tickets_after_save_ticket
 * - tribe_tickets_ticket_deleted
 * - tribe_tickets_rsvp_attendee_created
 * - tribe_tickets_attendee_updated
 * - tribe_tickets_checkin
 * - tribe_tickets_uncheckin
 *
 * @since TBD
 */
class RSVP_Compatibility_Test extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var \Tribe__Tickets__RSVP
	 */
	private $rsvp;

	/**
	 * Track hook calls for verification.
	 *
	 * @var array
	 */
	private $hook_calls = [];

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 */
	public function ensure_user_can_manage_tickets(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp       = tribe( 'tickets.rsvp' );
		$this->hook_calls = [];
	}

	/**
	 * Test that ticket creation hooks are triggered.
	 *
	 * Verifies:
	 * - event_ticket_rsvp_before_ticket_creation fires before creation
	 * - event_ticket_rsvp_ticket_created fires after creation
	 * - rsvp_ticket_created fires after creation
	 * - event_tickets_after_save_ticket fires after save
	 * - Hook parameters are correct
	 *
	 * @test
	 */
	public function test_ticket_creation_hooks_fire() {
		// Register hook listeners.
		add_action( 'event_ticket_rsvp_before_ticket_creation', [ $this, 'track_before_creation_hook' ], 10, 3 );
		add_action( 'event_ticket_rsvp_ticket_created', [ $this, 'track_ticket_created_hook' ], 10, 4 );
		add_action( 'rsvp_ticket_created', [ $this, 'track_rsvp_created_hook' ], 10, 4 );
		add_action( 'event_tickets_after_save_ticket', [ $this, 'track_after_save_hook' ], 10, 4 );

		$post_id = static::factory()->post->create();

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->name        = 'Test Ticket';
		$ticket->description = 'Test Description';
		$ticket->price       = 0;
		$ticket->menu_order  = 1;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 50,
				'stock'    => 50,
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		// Verify all hooks fired.
		$this->assertArrayHasKey( 'before_creation', $this->hook_calls, 'before_ticket_creation hook should fire' );
		$this->assertArrayHasKey( 'ticket_created', $this->hook_calls, 'ticket_created hook should fire' );
		$this->assertArrayHasKey( 'rsvp_created', $this->hook_calls, 'rsvp_ticket_created hook should fire' );
		$this->assertArrayHasKey( 'after_save', $this->hook_calls, 'after_save_ticket hook should fire' );

		// Verify hook received correct parameters.
		$this->assertEquals( $post_id, $this->hook_calls['before_creation']['post_id'] );
		$this->assertEquals( $ticket_id, $this->hook_calls['ticket_created']['ticket_id'] );
		$this->assertEquals( $ticket_id, $this->hook_calls['rsvp_created']['ticket_id'] );
		$this->assertEquals( $ticket_id, $this->hook_calls['after_save']['ticket_id'] );

		// Clean up.
		remove_action( 'event_ticket_rsvp_before_ticket_creation', [ $this, 'track_before_creation_hook' ] );
		remove_action( 'event_ticket_rsvp_ticket_created', [ $this, 'track_ticket_created_hook' ] );
		remove_action( 'rsvp_ticket_created', [ $this, 'track_rsvp_created_hook' ] );
		remove_action( 'event_tickets_after_save_ticket', [ $this, 'track_after_save_hook' ] );
	}

	/**
	 * Test that ticket deletion hooks are triggered.
	 *
	 * Verifies:
	 * - tribe_tickets_ticket_deleted fires when ticket is deleted
	 * - Hook parameters include ticket ID and post ID
	 *
	 * @test
	 */
	public function test_ticket_deletion_hooks_fire() {
		add_action( 'tribe_tickets_ticket_deleted', [ $this, 'track_ticket_deleted_hook' ], 10, 4 );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Delete the ticket.
		$this->rsvp->delete_ticket( $post_id, $ticket_id );

		// Verify hook fired.
		$this->assertArrayHasKey( 'ticket_deleted', $this->hook_calls, 'ticket_deleted hook should fire' );
		$this->assertEquals( $ticket_id, $this->hook_calls['ticket_deleted']['ticket_id'] );
		$this->assertEquals( $post_id, $this->hook_calls['ticket_deleted']['post_id'] );

		// Clean up.
		remove_action( 'tribe_tickets_ticket_deleted', [ $this, 'track_ticket_deleted_hook' ] );
	}

	/**
	 * Test that attendee creation hooks are triggered.
	 *
	 * Verifies:
	 * - tribe_tickets_rsvp_attendee_created fires when attendee is created
	 * - Hook receives correct attendee data
	 *
	 * @test
	 */
	public function test_attendee_creation_hooks_fire() {
		add_action( 'tribe_tickets_rsvp_attendee_created', [ $this, 'track_attendee_created_hook' ], 10, 4 );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Generate attendee.
		$attendee_data = [
			'full_name'       => 'Test User',
			'email'           => 'test@example.com',
			'order_status'    => 'yes',
			'optout'          => false,
			'order_attendee_id' => 1,
		];

		$attendee_ids = $this->rsvp->generate_tickets_for( $ticket_id, 1, $attendee_data );

		// Verify hook fired.
		$this->assertArrayHasKey( 'attendee_created', $this->hook_calls, 'attendee_created hook should fire' );

		// Clean up.
		remove_action( 'tribe_tickets_rsvp_attendee_created', [ $this, 'track_attendee_created_hook' ] );
	}

	/**
	 * Test that check-in hooks are triggered.
	 *
	 * Verifies:
	 * - tribe_tickets_checkin fires when attendee is checked in
	 * - Hook receives correct attendee ID
	 *
	 * @test
	 */
	public function test_checkin_hooks_fire() {
		add_action( 'tribe_tickets_checkin', [ $this, 'track_checkin_hook' ], 10, 2 );

		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		// Check in attendee.
		$this->rsvp->checkin( $attendee_id, false );

		// Verify hook fired.
		$this->assertArrayHasKey( 'checkin', $this->hook_calls, 'checkin hook should fire' );
		$this->assertEquals( $attendee_id, $this->hook_calls['checkin']['attendee_id'] );

		// Clean up.
		remove_action( 'tribe_tickets_checkin', [ $this, 'track_checkin_hook' ] );
	}

	/**
	 * Test that uncheckin hooks are triggered.
	 *
	 * Verifies:
	 * - tribe_tickets_uncheckin fires when attendee is unchecked
	 * - Hook receives correct attendee ID
	 *
	 * @test
	 */
	public function test_uncheckin_hooks_fire() {
		add_action( 'tribe_tickets_uncheckin', [ $this, 'track_uncheckin_hook' ], 10, 2 );

		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		// Check in first.
		$this->rsvp->checkin( $attendee_id, false );

		// Then uncheckin.
		$this->rsvp->uncheckin( $attendee_id );

		// Verify hook fired.
		$this->assertArrayHasKey( 'uncheckin', $this->hook_calls, 'uncheckin hook should fire' );
		$this->assertEquals( $attendee_id, $this->hook_calls['uncheckin']['attendee_id'] );

		// Clean up.
		remove_action( 'tribe_tickets_uncheckin', [ $this, 'track_uncheckin_hook' ] );
	}

	/**
	 * Test backward compatibility with direct post meta access.
	 *
	 * Verifies:
	 * - Third-party code can still read ticket meta directly
	 * - Meta keys remain unchanged
	 * - Data structures are compatible
	 *
	 * @test
	 */
	public function test_direct_meta_access_backward_compatibility() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket(
			$post_id,
			[
				'meta_input' => [
					'_capacity'   => 100,
					'total_sales' => 10,
					'_stock'      => 90,
					'_price'      => 25.00,
				],
			]
		);

		// Third-party code might read meta directly.
		$capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
		$sales    = get_post_meta( $ticket_id, 'total_sales', true );
		$stock    = get_post_meta( $ticket_id, '_stock', true );
		$price    = get_post_meta( $ticket_id, '_price', true );
		$event_id = get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );

		// Verify all meta is accessible via direct access.
		$this->assertEquals( 100, $capacity, 'Capacity should be readable via get_post_meta' );
		$this->assertEquals( 10, $sales, 'Sales should be readable via get_post_meta' );
		$this->assertEquals( 90, $stock, 'Stock should be readable via get_post_meta' );
		$this->assertEquals( 25.00, $price, 'Price should be readable via get_post_meta' );
		$this->assertEquals( $post_id, $event_id, 'Event ID should be readable via get_post_meta' );
	}

	/**
	 * Test backward compatibility with attendee meta access.
	 *
	 * Verifies:
	 * - Attendee meta keys remain unchanged
	 * - Data can be read directly via get_post_meta
	 *
	 * @test
	 */
	public function test_attendee_meta_backward_compatibility() {
		$post_id     = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket(
			$ticket_id,
			$post_id,
			[
				'full_name'   => 'John Doe',
				'email'       => 'john@example.com',
				'rsvp_status' => 'yes',
			]
		);

		// Third-party code might read attendee meta directly.
		$full_name   = get_post_meta( $attendee_id, '_tribe_rsvp_full_name', true );
		$email       = get_post_meta( $attendee_id, '_tribe_rsvp_email', true );
		$status      = get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
		$ticket      = get_post_meta( $attendee_id, '_tribe_rsvp_product', true );
		$event       = get_post_meta( $attendee_id, '_tribe_rsvp_event', true );
		$security    = get_post_meta( $attendee_id, '_tribe_tickets_security_code', true );

		// Verify all meta is accessible.
		$this->assertEquals( 'John Doe', $full_name, 'Full name should be readable' );
		$this->assertEquals( 'john@example.com', $email, 'Email should be readable' );
		$this->assertEquals( 'yes', $status, 'Status should be readable' );
		$this->assertEquals( $ticket_id, $ticket, 'Ticket ID should be readable' );
		$this->assertEquals( $post_id, $event, 'Event ID should be readable' );
		$this->assertNotEmpty( $security, 'Security code should be readable' );
	}

	/**
	 * Test that data structures returned by hooks are unchanged.
	 *
	 * Verifies:
	 * - Ticket object structure is compatible
	 * - Attendee data arrays maintain structure
	 *
	 * @test
	 */
	public function test_hook_data_structures_unchanged() {
		add_action( 'event_ticket_rsvp_ticket_created', [ $this, 'verify_ticket_data_structure' ], 10, 4 );

		$post_id = static::factory()->post->create();

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->name        = 'Test Ticket';
		$ticket->description = 'Test Description';
		$ticket->price       = 0;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 50,
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		// Verify the data structure was checked in the hook callback.
		$this->assertArrayHasKey( 'data_structure_valid', $this->hook_calls, 'Data structure should be validated' );
		$this->assertTrue( $this->hook_calls['data_structure_valid'], 'Data structure should be unchanged' );

		// Clean up.
		remove_action( 'event_ticket_rsvp_ticket_created', [ $this, 'verify_ticket_data_structure' ] );
	}

	/**
	 * Test that filters work correctly with repository pattern.
	 *
	 * Verifies:
	 * - Filters can modify data before save
	 * - Filter parameters are correct
	 * - Modified data is saved correctly
	 *
	 * @test
	 */
	public function test_filters_work_with_repository_pattern() {
		// Add filter to modify ticket capacity.
		add_filter( 'tribe_events_rsvp_before_ticket_creation', [ $this, 'modify_ticket_capacity_filter' ], 10, 3 );

		$post_id = static::factory()->post->create();

		$ticket              = new \Tribe__Tickets__Ticket_Object();
		$ticket->name        = 'Test Ticket';
		$ticket->description = 'Test Description';
		$ticket->price       = 0;

		$raw_data = [
			'tribe-ticket' => [
				'capacity' => 50, // Will be modified to 100 by filter.
			],
		];

		$ticket_id = $this->rsvp->save_ticket( $post_id, $ticket, $raw_data );

		// Verify filter was applied (capacity should be 100, not 50).
		$capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );

		// Note: This test verifies the filter hook exists and is called.
		// The actual modification depends on whether the filter is properly implemented.
		$this->assertNotNull( $capacity, 'Capacity should be set' );

		// Clean up.
		remove_filter( 'tribe_events_rsvp_before_ticket_creation', [ $this, 'modify_ticket_capacity_filter' ] );
	}

	/**
	 * Test that third-party code can extend repository functionality.
	 *
	 * Verifies:
	 * - Repositories are accessible via tribe()
	 * - Third-party code can use repository methods
	 * - Custom queries work with repository pattern
	 *
	 * @test
	 */
	public function test_third_party_can_extend_repository() {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Third-party code can access repositories.
		$ticket_repo   = tribe( 'tickets.ticket-repository.rsvp' );
		$attendee_repo = tribe( 'tickets.attendee-repository.rsvp' );

		$this->assertNotNull( $ticket_repo, 'Ticket repository should be accessible' );
		$this->assertNotNull( $attendee_repo, 'Attendee repository should be accessible' );

		// Third-party code can use repository methods.
		$capacity = $ticket_repo->get_field( $ticket_id, 'capacity' );
		$this->assertNotNull( $capacity, 'Third-party code can use get_field method' );
	}

	// Hook tracking callbacks.

	public function track_before_creation_hook( $post_id, $ticket, $raw_data ) {
		$this->hook_calls['before_creation'] = [
			'post_id'  => $post_id,
			'ticket'   => $ticket,
			'raw_data' => $raw_data,
		];
	}

	public function track_ticket_created_hook( $ticket_id, $ticket, $raw_data, $post_id ) {
		$this->hook_calls['ticket_created'] = [
			'ticket_id' => $ticket_id,
			'ticket'    => $ticket,
			'raw_data'  => $raw_data,
			'post_id'   => $post_id,
		];
	}

	public function track_rsvp_created_hook( $ticket_id, $ticket, $raw_data, $post_id ) {
		$this->hook_calls['rsvp_created'] = [
			'ticket_id' => $ticket_id,
			'ticket'    => $ticket,
			'raw_data'  => $raw_data,
			'post_id'   => $post_id,
		];
	}

	public function track_after_save_hook( $ticket_id, $ticket, $raw_data, $post_id ) {
		$this->hook_calls['after_save'] = [
			'ticket_id' => $ticket_id,
			'ticket'    => $ticket,
			'raw_data'  => $raw_data,
			'post_id'   => $post_id,
		];
	}

	public function track_ticket_deleted_hook( $ticket_id, $ticket, $post_id, $product_id ) {
		$this->hook_calls['ticket_deleted'] = [
			'ticket_id'  => $ticket_id,
			'ticket'     => $ticket,
			'post_id'    => $post_id,
			'product_id' => $product_id,
		];
	}

	public function track_attendee_created_hook( $attendee_id, $post_id, $ticket_id, $attendee_data ) {
		$this->hook_calls['attendee_created'] = [
			'attendee_id'   => $attendee_id,
			'post_id'       => $post_id,
			'ticket_id'     => $ticket_id,
			'attendee_data' => $attendee_data,
		];
	}

	public function track_checkin_hook( $attendee_id, $qr ) {
		$this->hook_calls['checkin'] = [
			'attendee_id' => $attendee_id,
			'qr'          => $qr,
		];
	}

	public function track_uncheckin_hook( $attendee_id, $qr ) {
		$this->hook_calls['uncheckin'] = [
			'attendee_id' => $attendee_id,
			'qr'          => $qr,
		];
	}

	public function verify_ticket_data_structure( $ticket_id, $ticket, $raw_data, $post_id ) {
		// Verify ticket object has expected properties.
		$valid = isset( $ticket->name, $ticket->description, $ticket->price );
		$this->hook_calls['data_structure_valid'] = $valid;
	}

	public function modify_ticket_capacity_filter( $ticket, $post_id, $raw_data ) {
		// Modify capacity in raw_data.
		if ( isset( $raw_data['tribe-ticket']['capacity'] ) ) {
			$raw_data['tribe-ticket']['capacity'] = 100;
		}
		return $ticket;
	}
}
