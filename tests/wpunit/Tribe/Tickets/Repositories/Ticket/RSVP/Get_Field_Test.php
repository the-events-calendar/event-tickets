<?php

namespace Tribe\Tickets\Repositories\Ticket\RSVP;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

/**
 * Test the get_field() method in the RSVP Ticket Repository.
 *
 * @package Tribe\Tickets\Repositories\Ticket\RSVP
 */
class Get_Field_Test extends \Codeception\TestCase\WPTestCase {

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
	 * It should get field value using alias.
	 *
	 * @test
	 */
	public function should_get_field_value_using_alias() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_price' => 25.00,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$price = $repository->get_field( $ticket_id, 'price' );

		$this->assertEquals( 25.00, $price );
	}

	/**
	 * It should get event_id field.
	 *
	 * @test
	 */
	public function should_get_event_id_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$event_id = $repository->get_field( $ticket_id, 'event_id' );

		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * It should get stock field.
	 *
	 * @test
	 */
	public function should_get_stock_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 100,
				'total_sales' => 20,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$stock = $repository->get_field( $ticket_id, 'stock' );

		$this->assertEquals( 80, $stock );
	}

	/**
	 * It should get sales field.
	 *
	 * @test
	 */
	public function should_get_sales_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => 42,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$sales = $repository->get_field( $ticket_id, 'sales' );

		$this->assertEquals( 42, $sales );
	}

	/**
	 * It should get capacity field.
	 *
	 * @test
	 */
	public function should_get_capacity_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity' => 200,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$capacity = $repository->get_field( $ticket_id, 'capacity' );

		$this->assertEquals( 200, $capacity );
	}

	/**
	 * It should get start_date field.
	 *
	 * @test
	 */
	public function should_get_start_date_field() {
		$post_id = $this->factory->post->create();
		$start_date = '2025-01-01 10:00:00';
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_start_date' => $start_date,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$retrieved_date = $repository->get_field( $ticket_id, 'start_date' );

		$this->assertEquals( $start_date, $retrieved_date );
	}

	/**
	 * It should get end_date field.
	 *
	 * @test
	 */
	public function should_get_end_date_field() {
		$post_id = $this->factory->post->create();
		$end_date = '2025-12-31 23:59:59';
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_ticket_end_date' => $end_date,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$retrieved_date = $repository->get_field( $ticket_id, 'end_date' );

		$this->assertEquals( $end_date, $retrieved_date );
	}

	/**
	 * It should get manage_stock field.
	 *
	 * @test
	 */
	public function should_get_manage_stock_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$manage_stock = $repository->get_field( $ticket_id, 'manage_stock' );

		$this->assertEquals( 'yes', $manage_stock );
	}

	/**
	 * It should get show_description field.
	 *
	 * @test
	 */
	public function should_get_show_description_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_tribe_ticket_show_description' => 'yes',
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$show_description = $repository->get_field( $ticket_id, 'show_description' );

		$this->assertEquals( 'yes', $show_description );
	}

	/**
	 * It should get show_not_going field.
	 *
	 * @test
	 */
	public function should_get_show_not_going_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_tribe_rsvp_show_not_going' => 'yes',
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$show_not_going = $repository->get_field( $ticket_id, 'show_not_going' );

		$this->assertEquals( 'yes', $show_not_going );
	}

	/**
	 * It should return empty string for non-existent field.
	 *
	 * @test
	 */
	public function should_return_empty_string_for_nonexistent_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		$repository = tribe_tickets( 'rsvp' );
		$value = $repository->get_field( $ticket_id, 'non_existent_field' );

		$this->assertSame( '', $value );
	}

	/**
	 * It should return empty string for non-existent ticket.
	 *
	 * @test
	 */
	public function should_return_empty_string_for_nonexistent_ticket() {
		$repository = tribe_tickets( 'rsvp' );
		$value = $repository->get_field( 99999, 'price' );

		$this->assertSame( '', $value );
	}

	/**
	 * It should get field by direct meta key (no alias).
	 *
	 * @test
	 */
	public function should_get_field_by_direct_meta_key() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_price' => 30.00,
			],
		] );

		$repository = tribe_tickets( 'rsvp' );
		$price = $repository->get_field( $ticket_id, '_price' );

		$this->assertEquals( 30.00, $price );
	}

	/**
	 * It should get post_title field.
	 *
	 * @test
	 */
	public function should_get_post_title_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'post_title' => 'My Custom Ticket Title',
		] );

		$repository = tribe_tickets( 'rsvp' );
		$title = $repository->get_field( $ticket_id, 'post_title' );

		$this->assertEquals( 'My Custom Ticket Title', $title );
	}

	/**
	 * It should distinguish empty string from nonexistent field.
	 *
	 * @test
	 */
	public function should_distinguish_empty_string_from_nonexistent_field() {
		$post_id = $this->factory->post->create();
		$ticket_id = $this->create_rsvp_ticket( $post_id );

		// Set a field to empty string
		update_post_meta( $ticket_id, '_ticket_description', '' );

		$repository = tribe_tickets( 'rsvp' );

		// Empty string should return empty string, not null
		$this->assertSame( '', $repository->get_field( $ticket_id, '_ticket_description' ) );

		// Nonexistent field should return empty string
		$this->assertSame( '', $repository->get_field( $ticket_id, 'nonexistent_field' ) );
	}
}
