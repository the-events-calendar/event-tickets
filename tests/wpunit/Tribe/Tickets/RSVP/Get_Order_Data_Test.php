<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Get_Order_Data_Test extends WPTestCase {
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
	 * It should return array with all required keys
	 *
	 * @test
	 */
	public function should_return_array_with_all_required_keys(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertIsArray( $order_data );

		// Verify all required keys are present
		$required_keys = [
			'order_id',
			'purchaser_name',
			'purchaser_email',
			'provider',
			'provider_slug',
			'purchase_time',
		];

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $order_data, "Missing required key: {$key}" );
		}
	}

	/**
	 * It should return correct order ID
	 *
	 * @test
	 */
	public function should_return_correct_order_id(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( $attendee_id, $order_data['order_id'] );
	}

	/**
	 * It should return purchaser name from repository
	 *
	 * @test
	 */
	public function should_return_purchaser_name_from_repository(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set purchaser name
		update_post_meta( $attendee_id, $this->rsvp->full_name, 'Jane Smith' );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( 'Jane Smith', $order_data['purchaser_name'] );
	}

	/**
	 * It should return purchaser email from repository
	 *
	 * @test
	 */
	public function should_return_purchaser_email_from_repository(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set purchaser email
		update_post_meta( $attendee_id, $this->rsvp->email, 'jane.smith@example.com' );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( 'jane.smith@example.com', $order_data['purchaser_email'] );
	}

	/**
	 * It should return correct provider class
	 *
	 * @test
	 */
	public function should_return_correct_provider_class(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( RSVP::class, $order_data['provider'] );
	}

	/**
	 * It should return correct provider slug
	 *
	 * @test
	 */
	public function should_return_correct_provider_slug(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( 'rsvp', $order_data['provider_slug'] );
	}

	/**
	 * It should return purchase time
	 *
	 * @test
	 */
	public function should_return_purchase_time(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertNotEmpty( $order_data['purchase_time'] );
		$this->assertIsString( $order_data['purchase_time'] );
		// Verify it's a valid date format
		$this->assertNotFalse( strtotime( $order_data['purchase_time'] ) );
	}

	/**
	 * It should apply filter tribe_tickets_order_data
	 *
	 * @test
	 */
	public function should_apply_filter_tribe_tickets_order_data(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$filter_called = false;
		$filter = function( $data, $provider_slug, $order_id ) use ( &$filter_called, $attendee_id ) {
			$filter_called = true;

			// Verify parameters
			$this->assertIsArray( $data );
			$this->assertEquals( 'rsvp', $provider_slug );
			$this->assertEquals( $attendee_id, $order_id );

			return $data;
		};

		add_filter( 'tribe_tickets_order_data', $filter, 10, 3 );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertTrue( $filter_called, 'Filter tribe_tickets_order_data should have been called' );

		remove_filter( 'tribe_tickets_order_data', $filter );
	}

	/**
	 * It should allow filter to modify order data
	 *
	 * @test
	 */
	public function should_allow_filter_to_modify_order_data(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$filter = function( $data ) {
			// Add custom field
			$data['custom_field'] = 'custom_value';
			// Modify existing field
			$data['purchaser_name'] = 'Modified Name';

			return $data;
		};

		add_filter( 'tribe_tickets_order_data', $filter );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( 'custom_value', $order_data['custom_field'], 'Filter should add custom field' );
		$this->assertEquals( 'Modified Name', $order_data['purchaser_name'], 'Filter should modify existing field' );

		remove_filter( 'tribe_tickets_order_data', $filter );
	}

	/**
	 * It should handle missing purchaser name gracefully
	 *
	 * @test
	 */
	public function should_handle_missing_purchaser_name_gracefully(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Delete the name meta
		delete_post_meta( $attendee_id, $this->rsvp->full_name );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertArrayHasKey( 'purchaser_name', $order_data );
		// Should be empty or false, but key should exist
		$this->assertTrue( empty( $order_data['purchaser_name'] ) || $order_data['purchaser_name'] === false );
	}

	/**
	 * It should handle missing purchaser email gracefully
	 *
	 * @test
	 */
	public function should_handle_missing_purchaser_email_gracefully(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Delete the email meta
		delete_post_meta( $attendee_id, $this->rsvp->email );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertArrayHasKey( 'purchaser_email', $order_data );
		// Should be empty or false, but key should exist
		$this->assertTrue( empty( $order_data['purchaser_email'] ) || $order_data['purchaser_email'] === false );
	}

	/**
	 * It should handle empty name and email together
	 *
	 * @test
	 */
	public function should_handle_empty_name_and_email_together(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Delete both name and email
		delete_post_meta( $attendee_id, $this->rsvp->full_name );
		delete_post_meta( $attendee_id, $this->rsvp->email );

		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertIsArray( $order_data );
		$this->assertArrayHasKey( 'purchaser_name', $order_data );
		$this->assertArrayHasKey( 'purchaser_email', $order_data );
		// Other fields should still be present
		$this->assertEquals( $attendee_id, $order_data['order_id'] );
		$this->assertEquals( RSVP::class, $order_data['provider'] );
		$this->assertEquals( 'rsvp', $order_data['provider_slug'] );
	}

	/**
	 * It should use repository get_field method for data retrieval
	 *
	 * @test
	 */
	public function should_use_repository_get_field_method_for_data_retrieval(): void {
		$event_id    = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		// Set specific values
		update_post_meta( $attendee_id, $this->rsvp->full_name, 'Repository Test User' );
		update_post_meta( $attendee_id, $this->rsvp->email, 'repository@test.com' );

		// Verify repository can retrieve these values
		$repository = tribe_attendees( 'rsvp' );
		$this->assertEquals( 'Repository Test User', $repository->get_field( $attendee_id, 'full_name' ) );
		$this->assertEquals( 'repository@test.com', $repository->get_field( $attendee_id, 'email' ) );

		// Verify get_order_data uses the same values
		$order_data = $this->rsvp->get_order_data( $attendee_id );

		$this->assertEquals( 'Repository Test User', $order_data['purchaser_name'] );
		$this->assertEquals( 'repository@test.com', $order_data['purchaser_email'] );
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
