<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;

class Template_TagsTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	protected function allow_posts() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );
	}

	/**
	 * @test
	 * it not should allow tickets on posts by default
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
	 * @covers tribe_tickets_post_type_enabled()
	 */
	public function it_should_allow_tickets_on_posts_when_enabled() {
		$this->allow_posts();

		$allowed = tribe_tickets_post_type_enabled( 'post' );

		$this->assertTrue( $allowed, 'Tickets on posts should be enabled' );
	}

	/**
	 * @test
	 * it should return the post id - events support tickets by default
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_post_id_events_support_tickets_by_default() {
		$event_id = $this->factory()->event->create();
		$parent   = tribe_tickets_parent_post( $event_id );

		$this->assertEquals( $event_id, $parent->ID, 'Tickets on events should be enabled by default' );
	}

	/**
	 * @test
	 * it should return the non event post id if it supports tickets
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_the_non_event_post_id_if_it_supports_tickets() {
		$this->allow_posts();

		$non_event_id = wp_insert_post( [ 'id' => 1337 ] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertEquals( $non_event_id, $parent );
	}

	/**
	 * @test
	 * it should return null if it does not supports tickets
	 *
	 * @covers tribe_tickets_parent_post()
	 */
	public function it_should_return_null_if_it_does_not_supports_tickets() {
		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
		] );

		$non_event_id = wp_insert_post( [ 'id' => 1337 ] );
		$parent       = tribe_tickets_parent_post( $non_event_id );

		$this->assertNull( $parent, 'Tickets on posts should be disabled by default' );
	}

	/**
	 * @test
	 * it should return true if event has tickets
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
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_true_if_event_has_tickets() {
		$rsvp_event_id = $this->factory()->event->create();

		$this->create_rsvp_ticket( $rsvp_event_id );

		$this->assertTrue( tribe_events_has_tickets( $rsvp_event_id ), 'Could not find attached RSVP tickets' );

		$paypal_event_id = $this->factory()->event->create();

		$this->create_paypal_ticket( $paypal_event_id, 1 );

		$this->assertTrue( tribe_events_has_tickets( $paypal_event_id ), 'Could not find attached Tribe Commerce tickets' );
	}

	/**
	 * @test
	 * it should return true if non-event post has tickets
	 *
	 * @covers tribe_events_has_tickets()
	 */
	public function it_should_return_true_if_non_event_post_has_rsvps() {
		// Make sure it's allowed first!
		$this->allow_posts();

		$event_id = $this->factory()->post->create();
		$this->create_rsvp_ticket( $event_id );

		$tickets = tribe_events_has_tickets( $event_id );

		$this->assertTrue( $tickets, 'Could not find attached RSVPs' );
	}

	/**
	 * @test
	 * it should return false if event has no tickets
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
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_sold_out_event() {
		$event_id = $this->factory()->event->create();

		// sold out
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 0, $count, 'Sold out event should return zero tickets' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with no sales
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_no_sales() {
		$event_id = $this->factory()->event->create();

		// no sales
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 5, $count, 'RSVP count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with some sales
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_some_sales() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 3,
				'total_sales' => 2,
			],
		] );

		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 3, $count, 'Ticket count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with multiple tickets
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_rsvps_on_event_with_multiple_rsvps() {
		$event_id = $this->factory()->event->create();

		// multiple rsvp
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 4,
				'total_sales' => 1,
			],
		] );
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 3,
				'total_sales' => 2,
			],
		] );

		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count, 'Multiple RSVP count incorrect' );
	}

	/**
	 * @test
	 * it should return correct number of tickets on event with mixed tickets & RSVPs
	 *
	 * @covers tribe_events_count_available_tickets()
	 */
	public function it_should_return_correct_number_of_tickets_on_event_with_mixed_tickets() {
		$event_id = $this->factory()->event->create();

		// mixed rsvp/ticket
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 4,
				'total_sales' => 1,
			],
		] );

		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 3,
				'total_sales' => 2,
			],
		] );
		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( 7, $count, 'Mixed ticket count incorrect' );
	}

	/**
	 * @test
	 * it should return true if event has unlimited rsvps
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_true_if_event_has_unlimited_rsvps() {
		$event_id = $this->factory()->event->create();

		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => - 1,
			],
		] );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited, 'Unlimited RSVP incorrectly identified as limited' );
	}

	/**
	 * @test
	 * it should return true if event has unlimited tickets
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_true_if_event_has_unlimited_tickets() {
		$event_id = $this->factory()->event->create();

		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => - 1,
			],
		] );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertTrue( $unlimited, 'Unlimited ticket incorrectly identified as limited' );
	}

	/**
	 * @test
	 * it should return false if event has no unlimited rsvps
	 *
	 * @covers tribe_events_has_unlimited_stock_tickets()
	 */
	public function it_should_return_false_if_event_has_no_unlimited_tickets() {
		$event_id = $this->factory()->event->create();
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );

		$unlimited = tribe_events_has_unlimited_stock_tickets( $event_id );

		$this->assertFalse( $unlimited, 'Limited RSVP incorrectly identified as unlimited' );
	}

	/**
	 * @test
	 * it should return true when event is sold out
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_true_when_event_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertTrue( $soldout, 'Sold-out event appears to have tickets.' );
	}

	/**
	 * @test
	 * it should return false when rsvp is not sold out
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_false_when_rsvp_is_not_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertFalse( $soldout, 'Event appears sold out when rsvp has stock available.' );
	}

	/**
	 * @test
	 * it should return false when ticket is not sold out
	 *
	 * @covers tribe_events_has_soldout
	 */
	public function it_should_return_false_when_ticket_is_not_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );

		$soldout = tribe_events_has_soldout( $event_id );

		$this->assertFalse( $soldout, 'Event appears sold out when ticket has stock available.' );
	}

	/**
	 * @test
	 * it should return false when event is sold out
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_false_when_event_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		// sold out
		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertFalse( $soldout );
	}

	/**
	 * @test
	 * it should return false when no tickets are sold out
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_false_when_no_tickets_are_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );
		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertFalse( $soldout );
	}

	/**
	 * @test
	 * it should return true when rsvp is sold out
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_true_when_rsvp_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );
		// not sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 1,
				'total_sales' => 4,
			],
		] );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertTrue( $soldout );
	}

	/**
	 * @test
	 * it should return true when ticket is sold out
	 *
	 * @covers tribe_events_partially_soldout
	 */
	public function it_should_return_true_when_ticket_is_sold_out() {
		$event_id = $this->factory()->event->create();

		// not sold out
		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
			],
		] );

		// sold out
		$this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 0,
				'total_sales' => 5,
			],
		] );

		$soldout = tribe_events_partially_soldout( $event_id );

		$this->assertTrue( $soldout );
	}

	/**
	 * @test
	 * it should return the correct number of rsvps
	 *
	 * @covers tribe_events_count_available_tickets
	 */
	public function it_should_return_the_correct_number_of_rsvps() {
		$event_id = $this->factory()->event->create();

		$this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 5,
				'_stock'      => 3,
				'total_sales' => 2,
			],
		] );

		$count = tribe_events_count_available_tickets( $event_id );

		$this->assertEquals( '3', $count );
	}

	/**
	 * @test
	 * it should properly detect an rsvp as a ticket
	 *
	 * @covers tribe_events_product_is_ticket
	 */
	public function it_should_properly_detect_an_rsvp_as_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$rsvp_id   = $this->create_rsvp_ticket( $event_id );
		$is_ticket = tribe_events_product_is_ticket( $rsvp_id );

		$this->assertTrue( $is_ticket, $rsvp_id );
	}

	/**
	 * @test
	 * it should properly detect a tribe-commerce ticket as a ticket
	 *
	 * @covers tribe_events_product_is_ticket
	 */
	public function it_should_properly_detect_a_tribe_commerce_ticket_as_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1 );
		$is_ticket = tribe_events_product_is_ticket( $ticket_id );

		$this->assertTrue( $is_ticket, $ticket_id );
	}

	/**
	 * @test
	 * it should find the event for an rsvp
	 *
	 * @covers tribe_events_get_ticket_event
	 */
	public function it_should_find_the_event_for_an_rsvp() {
		$event_id    = $this->factory()->event->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$found_event = tribe_events_get_ticket_event( $ticket_id );

		$this->assertNotEmpty( $found_event, 'Event not found!' );
		$this->assertEquals( $event_id, $found_event->ID );
	}

	/**
	 * @test
	 * it should find the event for a ticket
	 *
	 * @covers tribe_events_get_ticket_event
	 */
	public function it_should_find_the_event_for_a_ticket() {
		$event_id    = $this->factory()->event->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$found_event = tribe_events_get_ticket_event( $ticket_id );

		$this->assertNotEmpty( $found_event, 'Event not found!' );
		$this->assertEquals( $event_id, $found_event->ID );
	}

	/**
	 * @test
	 * it should return false when event has no tickets
	 *
	 * @covers tribe_events_has_tickets_on_sale
	 */
	public function it_should_return_false_when_event_has_no_tickets() {
		$event_id = $this->factory()->event->create();

		$on_sale = tribe_events_has_tickets_on_sale( $event_id );

		$this->assertFalse( $on_sale, 'No tickets should return false on check for tickets on sale' );
	}

	/**
	 * @test
	 * rsvps and tickets with no date are on sale
	 *
	 * @covers tribe_events_ticket_is_on_sale
	 */
	public function rsvps_and_tickets_with_no_date_are_on_sale() {
		$event_id       = $this->factory()->event->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_on_sale   = tribe_events_ticket_is_on_sale( tribe( 'tickets.rsvp' )->get_ticket( $event_id, $rsvp_ticket_id ) );

		$this->assertTrue( $rsvp_on_sale, 'RSVP with no date should show as on sale' );

		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 1 );
		$ticket_on_sale   = tribe_events_ticket_is_on_sale( tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $paypal_ticket_id ) );

		$this->assertTrue( $ticket_on_sale, 'Ticket with no date should show as on sale' );
	}

	/**
	 * @test
	 * rsvps and tickets with future end dates are on sale
	 *
	 * @covers tribe_events_ticket_is_on_sale
	 */
	public function rsvps_and_tickets_with_future_end_date_are_on_sale() {
		$event_id       = $this->factory()->event->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		update_post_meta( $rsvp_ticket_id, '_ticket_end_date', date( 'Y-m-d H:i:s', strtotime( '+10 days' ) ) );
		$rsvp_on_sale = tribe_events_ticket_is_on_sale( tribe( 'tickets.rsvp' )->get_ticket( $event_id, $rsvp_ticket_id ) );

		$this->assertTrue( $rsvp_on_sale, 'RSVP with future end date should show as on sale' );

		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 1 );
		update_post_meta( $paypal_ticket_id, '_ticket_end_date', date( 'Y-m-d H:i:s', strtotime( '+10 days' ) ) );
		$ticket_on_sale = tribe_events_ticket_is_on_sale( tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $paypal_ticket_id ) );

		$this->assertTrue( $ticket_on_sale, 'Ticket with with future end date should show as on sale' );
	}

	/**
	 * @test
	 * rsvps and tickets with past end dates are not on sale
	 *
	 * @covers tribe_events_ticket_is_on_sale
	 */
	public function rsvps_and_tickets_with_past_end_date_are_not_on_sale() {
		$event_id = $this->factory()->event->create();
		$rsvp_id  = $this->create_rsvp_ticket( $event_id );
		update_post_meta( $rsvp_id, '_ticket_end_date', date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ) );
		$rsvp_on_sale = tribe_events_ticket_is_on_sale( tribe( 'tickets.rsvp' )->get_ticket( $event_id, $rsvp_id ) );

		$this->assertFalse( $rsvp_on_sale, 'RSVP with past end date should show as not on sale' );

		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );
		update_post_meta( $ticket_id, '_ticket_end_date', date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ) );
		$ticket_on_sale = tribe_events_ticket_is_on_sale( tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id ) );

		$this->assertFalse( $ticket_on_sale, 'Ticket with with past end date should show as not on sale' );
	}

	/**
	 * @test
	 * it should return true for event post type by default
	 *
	 * @covers tribe_tickets_resource_url
	 */
	public function it_should_return_true_for_event_post_type_by_default() {
		$event_enabled = tribe_tickets_post_type_enabled( 'tribe_events' );

		$this->assertTrue( $event_enabled );
	}

	/**
	 * @test
	 * it should return true for post types we set
	 *
	 * @covers tribe_tickets_resource_url
	 */
	public function it_should_return_true_for_post_types_we_set() {
		$this->allow_posts();

		$event_enabled = tribe_tickets_post_type_enabled( 'post' );

		$this->assertTrue( $event_enabled );
	}

	/**
	 * @test
	 * it should return the event for an rsvp
	 *
	 * @covers tribe_tickets_get_event_ids
	 */
	public function it_should_return_the_event_for_an_rsvp() {
		$event_id       = $this->factory()->event->create();
		$rsvp_id        = $this->create_rsvp_ticket( $event_id );
		$test_event_ids = tribe_tickets_get_event_ids( $rsvp_id );
		$this->assertContains( $event_id, $test_event_ids );
	}

	/**
	 * @test
	 * it should return the event for a tribe-commerce ticket
	 *
	 * @covers tribe_tickets_get_event_ids
	 */
	public function it_should_return_the_event_for_a_tribe_commerce_ticket() {
		$event_id       = $this->factory()->event->create();
		$ticket_id      = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );
		$test_event_ids = tribe_tickets_get_event_ids( $ticket_id );

		$this->assertContains( $event_id, $test_event_ids );
	}

	/**
	 * @test
	 * it should return the correct providers
	 *
	 * @covers tribe_tickets_get_ticket_provider
	 */
	public function it_should_return_the_correct_providers() {
		$event_id         = $this->factory()->event->create();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $event_id );
		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity' => 5,
			],
		] );

		$rsvp_provider   = tribe_tickets_get_ticket_provider( $rsvp_ticket_id );
		$paypal_provider = tribe_tickets_get_ticket_provider( $paypal_ticket_id );

		$this->assertInstanceOf( RSVP::class, $rsvp_provider, 'RSVP provider identified incorrectly' );
		$this->assertInstanceOf( PayPal::class, $paypal_provider, 'Tribe Commerce provider identified incorrectly' );
	}

	/**
	 * @test
	 * it should get the correct number of rsvp attendees
	 *
	 * @covers tribe_tickets_get_attendees
	 */
	public function it_should_get_the_correct_number_of_rsvp_attendees() {
		$event_id = $this->factory()->event->create();
		$rsvp_id  = $this->create_rsvp_ticket( $event_id );

		$created_attendees = $this->create_many_attendees_for_ticket( 10, $rsvp_id, $event_id );
		$tested_attendees  = tribe_tickets_get_attendees( $event_id );

		$this->assertEquals( count( $created_attendees ), count( $tested_attendees ) );
	}

	/**
	 * @test
	 * it should get the correct number of ticket attendees
	 *
	 * @covers tribe_tickets_get_attendees
	 */
	public function it_should_get_the_correct_number_of_ticket_attendees() {
		$event_id       = $this->factory()->event->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id );
		$rsvp_attendees = $this->create_many_attendees_for_ticket( 15, $rsvp_ticket_id, $event_id );

		$this->assertCount( count( $rsvp_attendees ), tribe_tickets_get_attendees( $event_id ) );

		$paypal_ticket_id = $this->create_paypal_ticket( $event_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $event_id );

		// Confirm that caching is not in play as a result of a potential failure below.
		tribe( 'tickets.rsvp' )->clear_attendees_cache( $event_id );

		$this->assertCount( count( $paypal_attendees ) + count( $rsvp_attendees ), tribe_tickets_get_attendees( $event_id ) );
	}

	/**
	 * @test
	 * it should get capacity from an rsvp
	 *
	 * @covers tribe_tickets_get_capacity
	 */
	public function it_should_get_capacity_from_an_rsvp() {
		$event_id = $this->factory()->event->create();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 10,
				'_stock'      => 5,
				'total_sales' => 5,
			],
		] );

		$capacity = tribe_tickets_get_capacity( $rsvp_ticket_id );

		$this->assertEquals( '10', $capacity );
	}

	/**
	 * @test
	 * it should get capacity from a ticket
	 *
	 * @covers tribe_tickets_get_capacity
	 */

	public function it_should_get_capacity_from_a_ticket() {
		$event_id = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
				'_stock'      => 5,
				'total_sales' => 5,
			],
		] );

		$capacity = tribe_tickets_get_capacity( $ticket_id );

		$this->assertEquals( '10', $capacity );
	}

	/**
	 * @test
	 * it should delete capacity from a ticket
	 *
	 * @covers tribe_tickets_delete_capacity
	 */
	public function it_should_delete_capacity_from_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$deleted_ticket = tribe_tickets_delete_capacity( $ticket_id );
		$deleted_rsvp   = tribe_tickets_delete_capacity( $rsvp_ticket_id );

		$this->assertTrue( $deleted_ticket, 'Could not delete capacity for ticket' );
		$this->assertTrue( $deleted_rsvp, 'Could not delete capacity for RSVP' );
	}

	/**
	 * @test
	 * it should delete capacity from an event
	 * Worth noting that this does NOT change the ticket capacity!
	 *
	 * @covers tribe_tickets_delete_capacity
	 */
	public function it_should_delete_capacity_from_an_event() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$deleted_event = tribe_tickets_delete_capacity( $event_id );

		$this->assertTrue( $deleted_event, 'Could not delete capacity for event' );
	}

	/**
	 * @test
	 * it should delete capacity from a post
	 * Worth noting that this does NOT change the ticket capacity!
	 *
	 * @covers tribe_tickets_delete_capacity
	 */
	public function it_should_delete_capacity_from_a_post() {
		$this->allow_posts();
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$deleted_event = tribe_tickets_delete_capacity( $post_id );

		$this->assertTrue( $deleted_event, 'Could not delete capacity for post' );
	}

	/**
	 * @test
	 * it should update capacity for a ticket
	 *
	 * @covers tribe_tickets_update_capacity
	 */
	public function it_should_update_capacity_for_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$updated_ticket = tribe_tickets_update_capacity( $ticket_id, 8 );
		$updated_rsvp   = tribe_tickets_update_capacity( $rsvp_ticket_id, 7 );

		$this->assertEquals( 8, $updated_ticket, 'Could not update capacity for ticket' );
		$this->assertEquals( 7, $updated_rsvp, 'Could not update capacity for RSVP' );
	}

	/**
	 * @test
	 * it should update capacity for an event
	 * Worth noting that this does NOT change the ticket capacity!
	 *
	 * @covers tribe_tickets_update_capacity
	 */
	public function it_should_update_capacity_for_an_event() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$updated_event = tribe_tickets_update_capacity( $event_id, 10 );

		$this->assertEquals( 10, $updated_event, 'Could not update capacity for event' );
	}

	/**
	 * @test
	 * it should update capacity for a post
	 * Worth noting that this does NOT change the ticket capacity!
	 *
	 * @covers tribe_tickets_update_capacity
	 */
	public function it_should_update_capacity_for_a_post() {
		$this->allow_posts();
		$post_id   = $this->factory()->post->create();
		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$updated_event = tribe_tickets_update_capacity( $post_id, 10 );

		$this->assertEquals( 10, $updated_event, 'Could not update capacity for post' );
	}

	/**
	 * @test
	 * it should get readable amount for ticket with own capacity
	 *
	 * @covers tribe_tickets_get_readable_amount
	 */
	public function it_should_get_readable_amount_for_ticket_with_own_capacity() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => 10,
			],
		] );

		$ticket_count = tribe_tickets_get_capacity( $ticket_id );

		$capacity = tribe_tickets_get_readable_amount( $ticket_count );

		$this->assertEquals( $ticket_count, $capacity );
	}

	/**
	 * @test
	 * it should get readable amount for ticket with unlimited capacity
	 *
	 * @covers tribe_tickets_get_readable_amount
	 */
	public function it_should_get_readable_amount_for_ticket_with_unlimited_capacity() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'   => -1,
			],
		] );

		$ticket_count = tribe_tickets_get_capacity( $ticket_id );

		$capacity = tribe_tickets_get_readable_amount( $ticket_count );

		$this->assertEquals( 'Unlimited', $capacity );
	}

	/**
	 * @test
	 * it should find meta fields for a ticket
	 *
	 * @covers tribe_tickets_has_meta_fields
	 */
	public function it_should_find_meta_fields_for_a_ticket() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1, [
			'meta_input' => [
				'_capacity'                   => 10,
				'_tribe_tickets_meta_enabled' => true,
				'_tribe_tickets_meta'         => 'a:1:{i:0;a:5:{s:4:"type";s:4:"text";s:8:"required";s:0:"";s:5:"label";s:0:"";s:4:"slug";s:0:"";s:5:"extra";a:0:{}}}',
			],
		] );
		$rsvp_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_capacity'                   => 10,
				'_tribe_tickets_meta_enabled' => true,
				'_tribe_tickets_meta'         => 'a:1:{i:0;a:5:{s:4:"type";s:4:"text";s:8:"required";s:0:"";s:5:"label";s:0:"";s:4:"slug";s:0:"";s:5:"extra";a:0:{}}}',
			],
		] );

		$ticket_meta = tribe_tickets_has_meta_fields( $ticket_id );
		$rsvp_meta = tribe_tickets_has_meta_fields( $rsvp_id );

		$this->assertTrue( $ticket_meta, 'Ticket meta not found' );
		$this->assertTrue( $rsvp_meta, 'RSVP meta not found' );
	}

	/**
	 * @test
	 * it should not find meta fields for a ticket when there isn't any
	 *
	 * @covers tribe_tickets_has_meta_fields
	 */
	public function it_should_not_find_meta_fields_for_a_ticket_when_there_isnt_any() {
		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 1 );
		$rsvp_id   = $this->create_rsvp_ticket( $event_id );

		$ticket_meta = tribe_tickets_has_meta_fields( $ticket_id );
		$rsvp_meta = tribe_tickets_has_meta_fields( $rsvp_id );

		$this->assertFalse( $ticket_meta, 'Nonexistent ticket meta found' );
		$this->assertFalse( $rsvp_meta, 'Nonexistent RSVP meta found' );
	}
}
