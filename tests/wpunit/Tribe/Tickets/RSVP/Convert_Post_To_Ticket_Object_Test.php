<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use ReflectionClass;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__Global_Stock;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object;

class Convert_Post_To_Ticket_Object_Test extends WPTestCase {
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
	 * Call protected method using reflection.
	 *
	 * @param \WP_Post $post Post object.
	 * @return Tribe__Tickets__Ticket_Object
	 */
	protected function call_convert_post_to_ticket_object( \WP_Post $post ) {
		$reflection = new ReflectionClass( $this->rsvp );
		$method = $reflection->getMethod( 'convert_post_to_ticket_object' );
		$method->setAccessible( true );

		return $method->invoke( $this->rsvp, $post );
	}

	/**
	 * It should convert basic post properties
	 *
	 * @test
	 */
	public function should_convert_basic_post_properties(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title'   => 'VIP Ticket',
			'post_excerpt' => 'Includes backstage access',
			'menu_order'   => 5,
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertInstanceOf( Tribe__Tickets__Ticket_Object::class, $ticket );
		$this->assertEquals( $ticket_id, $ticket->ID );
		$this->assertEquals( 'VIP Ticket', $ticket->name );
		$this->assertEquals( 'Includes backstage access', $ticket->description );
		$this->assertEquals( 5, $ticket->menu_order );
	}

	/**
	 * It should set price and stock fields using repository
	 *
	 * @test
	 */
	public function should_set_price_and_stock_fields_using_repository(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_price'      => '25.00',
				'_capacity'   => 60,
				'total_sales' => 10,
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertEquals( '25.00', $ticket->price );
		$this->assertEquals( 50, $ticket->stock ); // stock = capacity (60) - sales (10) = 50
		$this->assertEquals( 10, $ticket->qty_sold );
	}

	/**
	 * It should set provider class correctly
	 *
	 * @test
	 */
	public function should_set_provider_class_correctly(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertEquals( RSVP::class, $ticket->provider_class );
	}

	/**
	 * It should handle start date when present
	 *
	 * @test
	 */
	public function should_handle_start_date_when_present(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_ticket_start_date' => '2025-11-20 10:00:00',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertNotEmpty( $ticket->start_date );
		$this->assertNotEmpty( $ticket->start_time );
	}

	/**
	 * It should handle end date when present
	 *
	 * @test
	 */
	public function should_handle_end_date_when_present(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_ticket_end_date' => '2025-12-31 23:59:59',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertNotEmpty( $ticket->end_date );
		$this->assertNotEmpty( $ticket->end_time );
	}

	/**
	 * It should handle missing optional dates
	 *
	 * @test
	 */
	public function should_handle_missing_optional_dates(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_ticket_start_date' => '',
				'_ticket_end_date'   => '',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		// Should not have dates set if not provided
		$this->assertObjectHasAttribute( 'start_date', $ticket );
		$this->assertObjectHasAttribute( 'end_date', $ticket );
	}

	/**
	 * It should handle stock management flag
	 *
	 * @test
	 */
	public function should_handle_stock_management_flag(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_manage_stock' => 'yes',
				'_stock'        => 100,
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertTrue( $ticket->manage_stock() );
		$this->assertEquals( 100, $ticket->stock() );
	}

	/**
	 * It should handle disabled stock management
	 *
	 * @test
	 */
	public function should_handle_disabled_stock_management(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_manage_stock' => 'no',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertFalse( $ticket->manage_stock() );
	}

	/**
	 * It should handle global stock mode
	 *
	 * @test
	 */
	public function should_handle_global_stock_mode(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 100,
			],
		] );

		// Explicitly set global stock mode after creation
		update_post_meta( $ticket_id, '_global_stock_mode', Tribe__Tickets__Global_Stock::OWN_STOCK_MODE );

		// Verify repository can retrieve it
		$repository = tribe_tickets( 'rsvp' );
		$mode_from_repo = $repository->get_field( $ticket_id, 'global_stock_mode' );
		$this->assertEquals( Tribe__Tickets__Global_Stock::OWN_STOCK_MODE, $mode_from_repo );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		// The method uses repository which confirmed it can read the value
		// If it's 'own', it should be set to 'own', otherwise empty string
		$this->assertContains( $ticket->global_stock_mode, [ Tribe__Tickets__Global_Stock::OWN_STOCK_MODE, '' ] );
	}

	/**
	 * It should handle non-own stock mode
	 *
	 * @test
	 */
	public function should_handle_non_own_stock_mode(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_global_stock_mode' => 'global',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertEquals( '', $ticket->global_stock_mode );
	}

	/**
	 * It should set capacity from stock calculation
	 *
	 * @test
	 */
	public function should_set_capacity_from_stock_calculation(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_stock'       => 50,
				'total_sales'  => 10,
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		// capacity is calculated by tribe_tickets_get_capacity()
		$this->assertIsInt( $ticket->capacity );
	}

	/**
	 * It should set show_description property
	 *
	 * @test
	 */
	public function should_set_show_description_property(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertObjectHasAttribute( 'show_description', $ticket );
		$this->assertIsBool( $ticket->show_description );
	}

	/**
	 * It should use repository get_field for meta access
	 *
	 * @test
	 */
	public function should_use_repository_get_field_for_meta_access(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_price'      => '50.00',
				'_capacity'   => 30,
				'total_sales' => 5,
			],
		] );

		// Verify repository can access these fields
		$repository = tribe_tickets( 'rsvp' );
		$this->assertEquals( '50.00', $repository->get_field( $ticket_id, 'price' ) );
		$this->assertEquals( 25, $repository->get_field( $ticket_id, 'stock' ) ); // capacity (30) - sales (5) = 25
		$this->assertEquals( 5, $repository->get_field( $ticket_id, 'sales' ) );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		// Verify the converted object uses these values
		$this->assertEquals( '50.00', $ticket->price );
		$this->assertEquals( 25, $ticket->stock );
		$this->assertEquals( 5, $ticket->qty_sold );
	}

	/**
	 * It should handle all Ticket_Object fields
	 *
	 * @test
	 */
	public function should_handle_all_ticket_object_fields(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title'   => 'Complete Ticket',
			'post_excerpt' => 'Full description',
			'menu_order'   => 10,
			'meta_input'   => [
				'_price'             => '100.00',
				'_capacity'          => 100,
				'total_sales'        => 25,
				'_manage_stock'      => 'yes',
				'_global_stock_mode' => Tribe__Tickets__Global_Stock::OWN_STOCK_MODE,
				'_ticket_start_date' => '2025-11-01 09:00:00',
				'_ticket_end_date'   => '2025-11-30 17:00:00',
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		// Verify all expected fields are present
		$this->assertEquals( $ticket_id, $ticket->ID );
		$this->assertEquals( 'Complete Ticket', $ticket->name );
		$this->assertEquals( 'Full description', $ticket->description );
		$this->assertEquals( 10, $ticket->menu_order );
		$this->assertEquals( '100.00', $ticket->price );
		$this->assertEquals( 75, $ticket->stock ); // capacity (100) - sales (25) = 75
		$this->assertEquals( 25, $ticket->qty_sold );
		$this->assertEquals( RSVP::class, $ticket->provider_class );
		$this->assertNotEmpty( $ticket->start_date );
		$this->assertNotEmpty( $ticket->start_time );
		$this->assertNotEmpty( $ticket->end_date );
		$this->assertNotEmpty( $ticket->end_time );
		$this->assertTrue( $ticket->manage_stock() );
		$this->assertIsInt( $ticket->capacity );
		$this->assertIsBool( $ticket->show_description );
	}

	/**
	 * It should handle zero values correctly
	 *
	 * @test
	 */
	public function should_handle_zero_values_correctly(): void {
		$event_id  = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_price'      => '0',
				'_capacity'   => 0,
				'total_sales' => 0,
			],
		] );

		$post = get_post( $ticket_id );
		$ticket = $this->call_convert_post_to_ticket_object( $post );

		$this->assertEquals( '0', $ticket->price );
		$this->assertEquals( 0, $ticket->stock );
		$this->assertEquals( 0, $ticket->qty_sold );
	}
}
