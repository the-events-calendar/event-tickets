<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class GetTicketTest extends WPTestCase {
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
	 * It should return Ticket_Object for valid ticket
	 *
	 * @test
	 */
	public function should_return_ticket_object_for_valid_ticket(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title'   => 'Test Ticket',
			'post_excerpt' => 'Test Description',
			'meta_input'   => [
				'_price'     => 25,
				'_capacity'  => 100,
				'total_sales' => 0,
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertEquals( $ticket_id, $ticket->ID );
		$this->assertEquals( 'Test Ticket', $ticket->name );
		$this->assertEquals( 'Test Description', $ticket->description );
		$this->assertEquals( '25', $ticket->price );
		$this->assertEquals( 100, $ticket->stock );
	}

	/**
	 * It should return null for non-existent ticket
	 *
	 * @test
	 */
	public function should_return_null_for_non_existent_ticket(): void {
		$ticket = $this->rsvp->get_ticket( 0, 999999 );

		$this->assertNull( $ticket );
	}

	/**
	 * It should populate all Ticket_Object fields correctly
	 *
	 * @test
	 */
	public function should_populate_all_ticket_object_fields_correctly(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title'   => 'VIP Ticket',
			'post_excerpt' => 'VIP Access',
			'meta_input'   => [
				'_price'             => 50,
				'_capacity'          => 20,
				'total_sales'        => 5,
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertInstanceOf( Ticket_Object::class, $ticket );
		$this->assertEquals( $ticket_id, $ticket->ID );
		$this->assertEquals( 'VIP Ticket', $ticket->name );
		$this->assertEquals( 'VIP Access', $ticket->description );
		$this->assertEquals( '50', $ticket->price );
		$this->assertEquals( 5, $ticket->qty_sold );
		$this->assertEquals( 15, $ticket->stock );
		$this->assertEquals( 20, $ticket->capacity );
		$this->assertNotEmpty( $ticket->start_date );
		$this->assertNotEmpty( $ticket->end_date );
		$this->assertEquals( get_class( $this->rsvp ), $ticket->provider_class );
	}

	/**
	 * It should use cached ticket if available
	 *
	 * @test
	 */
	public function should_use_cached_ticket_if_available(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_price' => 25,
			],
		] );

		// Flush cache to start fresh
		wp_cache_flush();

		// First call should set cache
		$ticket1 = $this->rsvp->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( '25', $ticket1->price );

		// Verify cache is set
		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertNotEmpty( $cached );
		$this->assertIsArray( $cached );

		// Modify cached value to test cache usage
		$cached['price'] = 99;
		wp_cache_set( $ticket_id, $cached, 'tec_tickets' );

		// Second call should use cache
		$ticket2 = $this->rsvp->get_ticket( $event_id, $ticket_id );
		$this->assertEquals( 99, $ticket2->price );
	}

	/**
	 * It should set cache after fetch
	 *
	 * @test
	 */
	public function should_set_cache_after_fetch(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Flush cache
		wp_cache_flush();

		// Verify cache is empty
		$this->assertFalse( wp_cache_get( $ticket_id, 'tec_tickets' ) );

		// Fetch ticket
		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		// Verify cache is now set
		$cached = wp_cache_get( $ticket_id, 'tec_tickets' );
		$this->assertNotEmpty( $cached );
		$this->assertIsArray( $cached );
		$this->assertEquals( $ticket_id, $cached['ID'] );
	}

	/**
	 * It should apply filter tribe_tickets_rsvp_get_ticket
	 *
	 * @test
	 */
	public function should_apply_filter_tribe_tickets_rsvp_get_ticket(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_price' => 25,
			],
		] );

		$filter_called = false;
		$filter = function( $ticket, $event_id_param, $ticket_id_param ) use ( &$filter_called, $event_id, $ticket_id ) {
			$filter_called = true;
			$this->assertEquals( $event_id, $event_id_param );
			$this->assertEquals( $ticket_id, $ticket_id_param );
			$this->assertInstanceOf( Ticket_Object::class, $ticket );

			// Modify ticket in filter
			$ticket->price = 99;

			return $ticket;
		};

		add_filter( 'tribe_tickets_rsvp_get_ticket', $filter, 10, 3 );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertTrue( $filter_called, 'Filter should have been called' );
		$this->assertEquals( 99, $ticket->price, 'Filter should have modified price' );

		remove_filter( 'tribe_tickets_rsvp_get_ticket', $filter );
	}

	/**
	 * It should handle stock management mode
	 *
	 * @test
	 */
	public function should_handle_stock_management_mode(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 50,
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertTrue( $ticket->manage_stock() );
		$this->assertEquals( 50, $ticket->stock );
	}

	/**
	 * It should handle date fields
	 *
	 * @test
	 */
	public function should_handle_date_fields(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );

		$start_datetime = date( 'Y-m-d H:i:s', strtotime( '+1 day' ) );
		$end_datetime   = date( 'Y-m-d H:i:s', strtotime( '+7 days' ) );

		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_ticket_start_date' => $start_datetime,
				'_ticket_end_date'   => $end_datetime,
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertNotEmpty( $ticket->start_date );
		$this->assertNotEmpty( $ticket->start_time );
		$this->assertNotEmpty( $ticket->end_date );
		$this->assertNotEmpty( $ticket->end_time );
	}

	/**
	 * It should calculate capacity correctly
	 *
	 * @test
	 */
	public function should_calculate_capacity_correctly(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 100,
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( 100, $ticket->capacity );
	}

	/**
	 * It should handle sales field
	 *
	 * @test
	 */
	public function should_handle_sales_field(): void {
		$event_id  = static::factory()->post->create( [ 'post_type' => 'tribe_events' ] );
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 100,
				'total_sales' => 15,
			],
		] );

		$ticket = $this->rsvp->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( 15, $ticket->qty_sold );
		$this->assertEquals( 85, $ticket->stock );
	}
}
