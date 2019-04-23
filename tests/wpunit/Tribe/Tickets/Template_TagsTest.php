<?php
namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class Template_TagsTest extends \Codeception\TestCase\WPTestCase {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * Wrapper function to create RSVPs
	 *
	 * @since TBD
	 *
	 * @param int $event_id
	 * @param int $capacity
	 * @param int $stock
	 *
	 * @return int $ticket_id
	 */
	protected function make_sales_rsvp( $event_id, $capacity, $stock = false ) {
		$ticket_id = $this->make_ticket( $event_id, $capacity, $stock );

		return $ticket_id;
	}

	/**
	 * Wrapper function to create tribe-commerce tickets
	 *
	 * @since TBD
	 *
	 * @param int $event_id
	 * @param int $capacity
	 * @param int $price
	 * @param int $stock
	 *
	 * @return int $ticket_id
	 */
	protected function make_sales_ticket( $event_id, $capacity, $price, $stock = false ) {
		$ticket_id = $this->make_ticket( $event_id, $capacity, $stock, 'tribe_tpp_tickets', $price );

		return $ticket_id;
	}

	/**
	 * Create a ticket attached to an event
	 *
	 * @param int $event_id
	 * @param string $ticket_type
	 * @param int $capacity
	 * @param int $stock
	 * @param int $price
	 *
	 * @return int $ticket_id
	 */
	protected function make_ticket( $event_id, $capacity, $stock = false, $ticket_type = 'tribe_rsvp_tickets', $price = false ) {
		// set stock to capacity if not passed
		$stock = ( false === $stock ) ? $capacity : $stock;

		$for_event = '_' . str_replace( 'tickets', 'for_event', $ticket_type );

		$args = [
			'post_type'   => $ticket_type,
			'post_status' => 'publish',
			'meta_input'  => [
				$for_event  => $event_id,
				'_tribe_ticket_capacity' => $capacity,
				'_stock'                 => $stock,
			]
		];

		if ( $price ) {
			$args[ 'meta_input' ][ '_price' ] = $price;
		}

		$ticket_id = $this->factory()->post->create( $args );

		return $ticket_id;
	}

	/**
	 * @test
	 * it not should allow tickets on posts by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_not_allow_tickets_on_posts_by_default() {
		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertFalse( $allowed, 'Tickets on posts should be disabled by default' );
	}

	/**
	 * @test
	 * it should allow tickets on posts when enabled
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_allow_tickets_on_posts_when_enabled() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertTrue( $allowed,'Tickets on posts should be enabled' );
	}

	/**
	 * @test
	 * it should return the post id - events support tickets by default
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_post_id_events_support_tickets_by_default() {
		$event_id = $this->factory()->event->create();
		$parent   = tribe_tickets_parent_post( $event_id );

		$this->assertEquals( $event_id, $parent->ID , 'Tickets on events should be enabled by default');
	}

	/**
	 * @test
	 * it should return the non event post id if it supports tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_non_event_post_id_if_it_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertEquals( $non_event_id, $parent );
	}

	/**
	 * @test
	 * it should return null if it does not supports tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_null_if_it_does_not_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
		] );

		$non_event_id = wp_insert_post( ['id' => 1337] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertNull( $parent, 'Tickets on posts should be disabled by default' );
	}

	/**
	 * @test
	 * it should return true if event has tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_true_if_event_has_rsvps() {
		$event_id = $this->factory()->event->create();
		$this->create_rsvp_ticket( $event_id );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets, 'Could not find attached RSVPs' );
	}

	/**
	 * @test
	 * it should return true if event has tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_true_if_event_has_tickets() {
		$event_id = $this->factory()->event->create();
		$this->make_sales_ticket( $event_id, 10, 1 );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets, 'Could not find attached tickets' );
	}

	/**
	 * @test
	 * it should return true if non-event post has tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_true_if_non_event_post_has_rsvps() {
		// Mkae sure it's allowed first!
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );

		$event_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $event_id );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets, 'Could not find attached RSVPs' );
	}

	/**
	 * @test
	 * it should return false if event has no tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_false_if_event_has_no_tickets() {
		$event_id = $this->factory()->post->create();
		$tickets  = tribe_events_has_tickets( $event_id );

		$this->assertFalse( $tickets, 'Found non-existent tickets?' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on sold out event
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_sold_out_event() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->make_sales_rsvp( $event_id, 5, 0 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 0, $count, 'Sold out event should return zero tickets' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with no sales
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_no_sales() {
		$event_id = $this->factory()->event->create();

		// no sales
		$this->make_sales_rsvp( $event_id, 5 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 5, $count, 'RSVP count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with some sales
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_some_sales() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 3, $count, 'Ticket count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with multiple tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_multiple_rsvps() {
		$event_id = $this->factory()->event->create();

		// multiple rsvp
		$this->make_sales_rsvp( $event_id, 5, 4 );
		$this->make_sales_rsvp( $event_id, 5, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count, 'Multiuple RSVP count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with mixed tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_tickets_on_event_with_mixed_tickets() {
		$event_id = $this->factory()->event->create();

		// mixed rsvp/ticket
		$this->make_sales_rsvp( $event_id, 5, 4 );
		$this->make_sales_ticket( $event_id, 5, 2, 3 );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count, 'Mixed ticket count incorrect' );
	}

	/**
	 * @test
	 * it should return true if event has unlimited rsvps
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_true_if_event_has_unlimited_rsvps(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_rsvp( $event_id, -1 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited, 'Unlimited RSVP incorrectly identified as limited' );
	}

	/**
	 * @test
	 * it should return true if event has unlimited tickets
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_true_if_event_has_unlimited_tickets(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_ticket( $event_id, -1, 1 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited, 'Unlimited ticket incorrectly identified as limited' );
	}

	/**
	 * @test
	 * it should return false if event has no unlimited rsvps
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_false_if_event_has_no_unlimited_tickets(){
		$event_id = $this->factory()->event->create();
		$this->make_sales_rsvp( $event_id, 5 );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertFalse( $unlimited, 'Limited RSVP incorrectly identified as unlimited' );
	}

	/**
	 * @test
	 * it should return true when event is sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_true_when_event_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->make_sales_rsvp( $event_id, 5, 0 );
		$this->make_sales_ticket( $event_id, 5, 1, 0 );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertTrue( $soldout, 'Sold-out event appears to have tickets.' );
	}

	/**
	 * @test
	 * it should return false when rsvp is not sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_false_when_rsvp_is_not_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 5 );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertFalse( $soldout, 'Event appears sold out when rsvp has stock available.' );
	}

	/**
	 * @test
	 * it should return false when ticket is not sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_false_when_ticket_is_not_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_ticket( $event_id, 5, 1 );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertFalse( $soldout, 'Event appears sold out when ticket has stock available.' );
	}

	/**
	 * @test
	 * it should return false when event is sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_false_when_event_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 0 );
		$this->make_sales_ticket( $event_id, 5, 1, 0 );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertFalse( $soldout );
	}

	/**
	 * @test
	 * it should return false when no tickets are sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_false_when_no_tickets_are_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 5 );
		$this->make_sales_ticket( $event_id, 5, 1 );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertFalse( $soldout );
	}

	/**
	 * @test
	 * it should return true when rsvp is sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_true_when_rsvp_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->make_sales_rsvp( $event_id, 5, 0 );
		// not sold out
		$this->make_sales_ticket( $event_id, 5, 1 );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertTrue( $soldout );
	}

	/**
	 * @test
	 * it should return true when ticket is sold out
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_true_when_ticket_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->make_sales_rsvp( $event_id, 5, 5 );
		// sold out
		$this->make_sales_ticket( $event_id, 5, 1, 0 );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertFalse( $soldout );
	}

	/**
	 * @test
	 * it should return the correct number of rsvps
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_count_available_tickets
	 */
	public function it_should_return_the_correct_number_of_rsvps() {
		$event_id = $this->factory()->event->create();

		$this->make_sales_rsvp( $event_id, 5, 3 );

		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( '3', $count );
	}

	/**
	 * @test
	 * it should properlyu detect an rsvp as a ticket
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_product_is_ticket
	 */
	public function it_should_properlyu_detect_an_rsvp_as_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$rsvp_id   = $this->make_sales_rsvp( $event_id, 5 );
		$is_ticket = tribe_events_product_is_ticket( $rsvp_id );

		$this->assertTrue( $is_ticket, $rsvp_id );
	}

	/**
	 * @test
	 * it should properly detect a tribe-commerce ticket as a ticket
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_product_is_ticket
	 */
	public function it_should_properly_detect_a_tribe_commerce_ticket_as_a_ticket() {
		$event_id    = $this->factory()->event->create();
		$ticket_id   = $this->make_sales_ticket( $event_id, 5, 1 );
		$is_ticket   = tribe_events_product_is_ticket( $ticket_id );

		$this->assertTrue( $is_ticket, $ticket_id );
	}

	/**
	 * @test
	 * it should find the event for an rsvp
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_get_ticket_event
	 */
	public function it_should_find_the_event_for_an_rsvp(){
		$event_id    = $this->factory()->event->create();
		$ticket_id   = $this->make_sales_rsvp( $event_id, 5 );
		$found_event = tribe_events_get_ticket_event( $ticket_id );

		$this->assertNotEmpty( $found_event, 'Event not found!' );
		$this->assertEquals( $event_id, $found_event->ID );
	}

	/**
	 * @test
	 * it should find the event for a ticket
	 *
	 * @since TBD
	 *
	 * @covers tribe_events_get_ticket_event
	 */
	public function it_should_find_the_event_for_a_ticket(){
		$event_id    = $this->factory()->event->create();
		$ticket_id   = $this->make_sales_ticket( $event_id, 5, 1 );
		$found_event = tribe_events_get_ticket_event( $ticket_id );

		$this->assertNotEmpty( $found_event, 'Event not found!' );
		$this->assertEquals( $event_id, $found_event->ID );
	}

}
