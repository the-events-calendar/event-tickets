<?php

namespace Tribe\Tickets\Repositories\Ticket\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Test the duplicate() method in the RSVP Ticket Repository.
 *
 * @package Tribe\Tickets\Repositories\Ticket\RSVP
 */
class Duplicate_Test extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );
	}

	/**
	 * It should duplicate a ticket with all metadata.
	 *
	 * @test
	 */
	public function should_duplicate_ticket_with_all_metadata() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id, [
			'post_title' => 'Original Ticket',
			'meta_input' => [
				'_price'      => 25.00,
				'_capacity'   => 100,
				'total_sales' => 10,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id );

		$this->assertIsInt( $duplicate_id );
		$this->assertGreaterThan( 0, $duplicate_id );
		$this->assertNotEquals( $original_id, $duplicate_id );

		// Check that metadata was copied
		$this->assertEquals( 25.00, get_post_meta( $duplicate_id, '_price', true ) );
		$this->assertEquals( 100, get_post_meta( $duplicate_id, '_tribe_ticket_capacity', true ) );
		$this->assertEquals( 10, get_post_meta( $duplicate_id, 'total_sales', true ) );
		$this->assertEquals( $post_id, get_post_meta( $duplicate_id, '_tribe_rsvp_for_event', true ) );
	}

	/**
	 * It should duplicate with field overrides.
	 *
	 * @test
	 */
	public function should_duplicate_with_field_overrides() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id, [
			'post_title' => 'Original Ticket',
			'meta_input' => [
				'_price'    => 25.00,
				'_capacity' => 100,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id, [
			'title'    => 'Duplicate Ticket',
			'price'    => 30.00,
			'capacity' => 50,
		] );

		$this->assertIsInt( $duplicate_id );
		$this->assertNotEquals( $original_id, $duplicate_id );

		// Check that overrides were applied
		$duplicate_post = get_post( $duplicate_id );
		$this->assertEquals( 'Duplicate Ticket', $duplicate_post->post_title );
		$this->assertEquals( 30.00, get_post_meta( $duplicate_id, '_price', true ) );
		$this->assertEquals( 50, get_post_meta( $duplicate_id, '_tribe_ticket_capacity', true ) );

		// Other fields should be copied
		$this->assertEquals( $post_id, get_post_meta( $duplicate_id, '_tribe_rsvp_for_event', true ) );
	}

	/**
	 * It should return false for non-existent ticket.
	 *
	 * @test
	 */
	public function should_return_false_for_nonexistent_ticket() {
		$repository = tribe_tickets( 'rsvp' );
		$result = $repository->duplicate( 99999 );

		$this->assertFalse( $result );
	}

	/**
	 * It should create a new ticket with different ID.
	 *
	 * @test
	 */
	public function should_create_new_ticket_with_different_id() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id );

		$this->assertIsInt( $duplicate_id );
		$this->assertNotEquals( $original_id, $duplicate_id );

		// Both tickets should exist
		$original_post = get_post( $original_id );
		$duplicate_post = get_post( $duplicate_id );

		$this->assertInstanceOf( 'WP_Post', $original_post );
		$this->assertInstanceOf( 'WP_Post', $duplicate_post );
	}

	/**
	 * It should copy post title, excerpt, and content.
	 *
	 * @test
	 */
	public function should_copy_post_title_excerpt_and_content() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id, [
			'post_title'   => 'Test Ticket Title',
			'post_excerpt' => 'Test excerpt',
			'post_content' => 'Test content',
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id );

		$duplicate_post = get_post( $duplicate_id );
		$this->assertEquals( 'Test Ticket Title', $duplicate_post->post_title );
		$this->assertEquals( 'Test excerpt', $duplicate_post->post_excerpt );
		$this->assertEquals( 'Test content', $duplicate_post->post_content );
	}

	/**
	 * It should reset sales to zero when duplicating.
	 *
	 * @test
	 */
	public function should_reset_sales_to_zero_when_duplicating() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 100,
				'total_sales' => 50,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id, [
			'sales' => 0,
			'stock' => 100,
		] );

		$this->assertEquals( 0, get_post_meta( $duplicate_id, 'total_sales', true ) );
		$this->assertEquals( 100, get_post_meta( $duplicate_id, '_stock', true ) );
	}

	/**
	 * It should duplicate all custom fields.
	 *
	 * @test
	 */
	public function should_duplicate_all_custom_fields() {
		$post_id = $this->factory->post->create();
		$start_date = '2025-01-01 10:00:00';
		$end_date = '2025-12-31 23:59:59';

		$original_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date'             => $start_date,
				'_ticket_end_date'               => $end_date,
				'_tribe_ticket_show_description' => 'yes',
				'_tribe_rsvp_show_not_going'     => 'yes',
				'_manage_stock'                  => 'yes',
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id );

		$this->assertEquals( $start_date, get_post_meta( $duplicate_id, '_ticket_start_date', true ) );
		$this->assertEquals( $end_date, get_post_meta( $duplicate_id, '_ticket_end_date', true ) );
		$this->assertEquals( 'yes', get_post_meta( $duplicate_id, '_tribe_ticket_show_description', true ) );
		$this->assertEquals( 'yes', get_post_meta( $duplicate_id, '_tribe_rsvp_show_not_going', true ) );
		$this->assertEquals( 'yes', get_post_meta( $duplicate_id, '_manage_stock', true ) );
	}

	/**
	 * It should use repository methods not direct WordPress functions.
	 *
	 * @test
	 */
	public function should_use_repository_methods_for_duplication() {
		$post_id = $this->factory->post->create();
		$original_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_price'      => '25.00',
				'_capacity'   => 10,
				'total_sales' => 5,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$duplicate_id = $repository->duplicate( $original_id );

		$this->assertNotFalse( $duplicate_id );
		$this->assertNotEquals( $original_id, $duplicate_id );

		// Verify it's a proper RSVP ticket (created via repository)
		$duplicate = get_post( $duplicate_id );
		$this->assertEquals( 'tribe_rsvp_tickets', $duplicate->post_type );

		// Verify metadata was copied
		$this->assertEquals( '25.00', get_post_meta( $duplicate_id, '_price', true ) );
		$this->assertEquals( 10, get_post_meta( $duplicate_id, '_tribe_ticket_capacity', true ) );

		// Verify sales was copied (not reset automatically)
		$this->assertEquals( 5, get_post_meta( $duplicate_id, 'total_sales', true ) );
	}
}
