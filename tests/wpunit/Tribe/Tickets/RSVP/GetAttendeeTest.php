<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class GetAttendeeTest extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @var RSVP
	 */
	protected $rsvp;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( RSVP::class );
	}

	/**
	 * It should return array for valid attendee
	 *
	 * @test
	 */
	public function should_return_array_for_valid_attendee(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertIsArray( $attendee );
		$this->assertEquals( $attendee_id, $attendee['attendee_id'] );
		$this->assertEquals( $ticket_id, $attendee['product_id'] );
	}

	/**
	 * It should return false for non-existent attendee
	 *
	 * @test
	 */
	public function should_return_false_for_non_existent_attendee(): void {
		$attendee = $this->rsvp->get_attendee( 999999 );

		$this->assertFalse( $attendee );
	}

	/**
	 * It should return false for wrong post type
	 *
	 * @test
	 */
	public function should_return_false_for_wrong_post_type(): void {
		$post_id = static::factory()->post->create( [ 'post_type' => 'post' ] );

		$attendee = $this->rsvp->get_attendee( $post_id );

		$this->assertFalse( $attendee );
	}

	/**
	 * It should include all required array keys
	 *
	 * @test
	 */
	public function should_include_all_required_array_keys(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		// Verify all required keys exist
		$required_keys = [
			'attendee_id',
			'security',
			'product_id',
			'check_in',
			'order_status',
			'order_status_label',
			'user_id',
			'ticket_sent',
			'optout',
			'ticket',
			'event_id',
			'ticket_name',
			'holder_name',
			'holder_email',
			'order_id',
			'ticket_id',
			'qr_ticket_id',
			'security_code',
			'attendee_meta',
			'is_subscribed',
			'is_purchaser',
			'order_id',
			'purchaser_name',
			'purchaser_email',
			'provider',
			'provider_slug',
			'purchase_time',
		];

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $attendee, "Missing key: {$key}" );
		}
	}

	/**
	 * It should merge order data correctly
	 *
	 * @test
	 */
	public function should_merge_order_data_correctly(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set order data
		update_post_meta( $attendee_id, $this->rsvp->full_name, 'John Doe' );
		update_post_meta( $attendee_id, $this->rsvp->email, 'john@example.com' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( 'John Doe', $attendee['purchaser_name'] );
		$this->assertEquals( 'john@example.com', $attendee['purchaser_email'] );
		$this->assertEquals( 'John Doe', $attendee['holder_name'] );
		$this->assertEquals( 'john@example.com', $attendee['holder_email'] );
	}

	/**
	 * It should apply filter tribe_tickets_attendee_data
	 *
	 * @test
	 */
	public function should_apply_filter_tribe_tickets_attendee_data(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$filter_called = false;
		$filter = function( $data, $provider_slug, $attendee_post, $post_id ) use ( &$filter_called, $attendee_id ) {
			$filter_called = true;
			$this->assertEquals( 'rsvp', $provider_slug );
			$this->assertInstanceOf( \WP_Post::class, $attendee_post );
			$this->assertEquals( $attendee_id, $attendee_post->ID );

			// Modify data in filter
			$data['custom_field'] = 'custom_value';

			return $data;
		};

		add_filter( 'tribe_tickets_attendee_data', $filter, 10, 4 );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertTrue( $filter_called, 'Filter should have been called' );
		$this->assertEquals( 'custom_value', $attendee['custom_field'], 'Filter should have added custom field' );

		remove_filter( 'tribe_tickets_attendee_data', $filter );
	}

	/**
	 * It should handle check-in status
	 *
	 * @test
	 */
	public function should_handle_check_in_status(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Check in the attendee
		update_post_meta( $attendee_id, $this->rsvp->checkin_key, 1 );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( '1', $attendee['check_in'] );
	}

	/**
	 * It should handle security code
	 *
	 * @test
	 */
	public function should_handle_security_code(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$security_code = 'abc123def456';
		update_post_meta( $attendee_id, $this->rsvp->security_code, $security_code );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( $security_code, $attendee['security'] );
		$this->assertEquals( $security_code, $attendee['security_code'] );
	}

	/**
	 * It should handle optout flag
	 *
	 * @test
	 */
	public function should_handle_optout_flag(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set optout to true
		update_post_meta( $attendee_id, RSVP::ATTENDEE_OPTOUT_KEY, '1' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertTrue( $attendee['optout'] );
	}

	/**
	 * It should get product details
	 *
	 * @test
	 */
	public function should_get_product_details(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title' => 'VIP Ticket',
		] );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( $ticket_id, $attendee['product_id'] );
		$this->assertEquals( 'VIP Ticket', $attendee['ticket'] );
		$this->assertEquals( 'VIP Ticket', $attendee['ticket_name'] );
	}

	/**
	 * It should handle attendee without product (deleted ticket)
	 *
	 * @test
	 */
	public function should_handle_attendee_without_product_deleted_ticket(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title' => 'Original Ticket',
		] );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Delete the ticket
		wp_delete_post( $ticket_id, true );

		// Mark the attendee with deleted product name
		update_post_meta( $attendee_id, $this->rsvp->deleted_product, 'Original Ticket' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertStringContainsString( 'Original Ticket', $attendee['ticket'] );
		$this->assertStringContainsString( '(deleted)', $attendee['ticket'] );
		$this->assertFalse( $attendee['ticket_name'] );
	}

	/**
	 * It should handle RSVP status
	 *
	 * @test
	 */
	public function should_handle_rsvp_status(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set RSVP status to 'yes'
		update_post_meta( $attendee_id, RSVP::ATTENDEE_RSVP_KEY, 'yes' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( 'yes', $attendee['order_status'] );
		$this->assertNotEmpty( $attendee['order_status_label'] );
	}

	/**
	 * It should handle unique ID
	 *
	 * @test
	 */
	public function should_handle_unique_id(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set unique ID
		$unique_id = 'EVENT-001';
		update_post_meta( $attendee_id, '_unique_id', $unique_id );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( $unique_id, $attendee['ticket_id'] );
	}

	/**
	 * It should use attendee ID as ticket ID when unique ID is empty
	 *
	 * @test
	 */
	public function should_use_attendee_id_as_ticket_id_when_unique_id_is_empty(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Make sure unique ID is empty
		update_post_meta( $attendee_id, '_unique_id', '' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( $attendee_id, $attendee['ticket_id'] );
	}

	/**
	 * It should handle subscribed flag
	 *
	 * @test
	 */
	public function should_handle_subscribed_flag(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set subscribed flag
		update_post_meta( $attendee_id, $this->rsvp->attendee_subscribed, '1' );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertTrue( $attendee['is_subscribed'] );
	}

	/**
	 * It should always mark as purchaser for RSVP
	 *
	 * @test
	 */
	public function should_always_mark_as_purchaser_for_rsvp(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertTrue( $attendee['is_purchaser'] );
	}

	/**
	 * It should handle ticket sent counter
	 *
	 * @test
	 */
	public function should_handle_ticket_sent_counter(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set ticket sent counter
		update_post_meta( $attendee_id, $this->rsvp->attendee_ticket_sent, 2 );

		$attendee = $this->rsvp->get_attendee( $attendee_id );

		$this->assertEquals( 2, $attendee['ticket_sent'] );
	}

	/**
	 * It should accept WP_Post object as parameter
	 *
	 * @test
	 */
	public function should_accept_wp_post_object_as_parameter(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$attendee_post = get_post( $attendee_id );
		$attendee = $this->rsvp->get_attendee( $attendee_post );

		$this->assertIsArray( $attendee );
		$this->assertEquals( $attendee_id, $attendee['attendee_id'] );
	}

	/**
	 * Helper method to create an attendee for a ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $event_id  Event ID.
	 * @return int Attendee ID.
	 */
	protected function create_attendee_for_ticket( $ticket_id, $event_id ) {
		$attendee_id = static::factory()->post->create( [
			'post_type' => RSVP::ATTENDEE_OBJECT,
		] );

		// Set required meta
		update_post_meta( $attendee_id, RSVP::ATTENDEE_PRODUCT_KEY, $ticket_id );
		update_post_meta( $attendee_id, RSVP::ATTENDEE_EVENT_KEY, $event_id );
		update_post_meta( $attendee_id, RSVP::ATTENDEE_RSVP_KEY, 'yes' );
		update_post_meta( $attendee_id, $this->rsvp->security_code, md5( $attendee_id ) );
		update_post_meta( $attendee_id, $this->rsvp->full_name, 'Test User' );
		update_post_meta( $attendee_id, $this->rsvp->email, 'test@example.com' );

		return $attendee_id;
	}
}
