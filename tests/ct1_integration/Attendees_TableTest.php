<?php

namespace Tribe\Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Models\Event;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Events\Test\Traits\CT1\CT1_Fixtures;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Attendees_Table as Attendees_Table;

class Attendees_TableTest extends WPTestCase {
	use CT1_Fixtures;
	use Attendee_Maker;
	use Ticket_Maker;

	private $wp_screen_backup;

	private function set_wp_screen(): void {
		global $wp_screen, $hook_suffix;
		$this->wp_screen_backup = $wp_screen;
		$hook_suffix            = 'edit.php';
		$wp_screen              = \WP_Screen::get();
	}

	public function _setUp() {
		parent::_setUp();
		$this->set_wp_screen();
		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	public function _tearDown() {
		$GLOBALS['wp_screen'] = $this->wp_screen_backup;
		parent::_tearDown();
	}

	private function make_instance() {
		return new Attendees_Table();
	}

	/**
	 * This method _should_ be provided by the base CT1 test utility, but it's currently bugged.
	 *
	 * @todo Remove this when the base CT1 test utility is fixed.
	 */
	private function given_a_migrated_single_event( $args = [] ) {
		$post = $this->given_a_non_migrated_single_event( $args );
		Event::upsert( [ 'post_id' ], Event::data_from_post( $post->ID ) );
		$event = Event::find( $post->ID, 'post_id' );
		$this->assertInstanceOf( Event::class, $event );
		$event->occurrences()->save_occurrences();
		$this->assertEquals( 1, Occurrence::where( 'post_id', '=', $post->ID )->count() );

		return $post;
	}

	public function create_order_for_ticket( $ticket_id, $quantity = 5 ) {
		// create order.
		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_id, $quantity );

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test' . uniqid() . '@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$completed = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		return $order;
	}

	/**
	 * It should allow fetching ticket attendees by event.
	 *
	 * @test
	 */
	public function should_allow_fetching_attendees_by_provisional_id() {
		$post       = $this->given_a_migrated_single_event();
		$post_id    = $post->ID;
		$quantity   = 4;
		$occurrence = Occurrence::find_by_post_id( $post_id );

		// Create a faux provisional id.
		$provisional_id = $occurrence->provisional_id;
		$ticket_a_id    = $this->create_tc_ticket( $post_id, 10 );

		$this->create_order_for_ticket( $ticket_a_id, $quantity );

		$_GET['event_id'] = $provisional_id;
		$table            = $this->make_instance();

		$table->prepare_items();
		$attendee_ids = wp_list_pluck( $table->items, 'attendee_id' );

		$this->assertNotEmpty( $attendee_ids );
		$this->assertEquals( $quantity, count( $attendee_ids ) );
		$this->assertEquals( $table->get_pagination_arg( 'total_items' ), count( $attendee_ids ) );
	}

	/**
	 * @test
	 * It should return false when the provider is Tickets Commerce but the module is disabled.
	 * 
	 * This test simulates a scenario where a user creates Tickets Commerce tickets,
	 * then switches to a different provider and disables Tickets Commerce.
	 * The code should prevent fatal errors by returning false for the provider.
	 */
	public function it_should_return_false_when_tickets_commerce_is_disabled() {
		// First, create an event with a Tickets Commerce ticket.
		$post = $this->given_a_migrated_single_event();
		$event_id = $post->ID;
		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		
		// Verify ticket was created properly.
		$this->assertNotEmpty( $ticket_id, 'Failed to create TC ticket' );
		
		// Handle the ways tec_tickets_commerce_is_enabled() might determine if commerce is enabled:
		$original_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=0' );
		
		// Verify Tickets Commerce is disabled.
		$this->assertFalse( tec_tickets_commerce_is_enabled(), 'Tickets Commerce should be disabled.' );
		
		// Reset any cached values.
		tribe_singleton( 'tickets.data_api', new \Tribe__Tickets__Data_API );

		// This is the key function we're testing - it should return false and not cause a fatal.
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		// Clean up.
		remove_all_filters( 'tec_tickets_commerce_is_enabled' );
		
		// Reset environment variable.
		if ( $original_env !== false ) {
			putenv( "TEC_TICKETS_COMMERCE=$original_env" );
		} else {
			putenv( 'TEC_TICKETS_COMMERCE' );  
		}
		
		// Assert the provider is false when Tickets Commerce is disabled.
		$this->assertFalse( $provider, 'Provider should be false when Tickets Commerce is disabled.' );
	}

	/**
	 * @test
	 * It should return the Tickets Commerce provider when the module is enabled.
	 * 
	 * This test verifies the normal, expected behavior when Tickets Commerce is enabled.
	 */
	public function it_should_return_tickets_commerce_provider_when_enabled() {
		// First, create an event with a Tickets Commerce ticket.
		$post = $this->given_a_migrated_single_event();
		$event_id = $post->ID;
		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		
		// Verify ticket was created properly.
		$this->assertNotEmpty( $ticket_id, 'Failed to create TC ticket' );
		
		// Set Tickets Commerce to enabled.
		$original_env = getenv( 'TEC_TICKETS_COMMERCE' );
		putenv( 'TEC_TICKETS_COMMERCE=1' );
		
		// Verify Tickets Commerce is enabled.
		$this->assertTrue( tec_tickets_commerce_is_enabled(), 'Tickets Commerce should be enabled.' );
		
		// Reset any cached values.
		tribe_singleton( 'tickets.data_api', new \Tribe__Tickets__Data_API );
		
		// Get the provider.
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );
		
		// Clean up.
		remove_all_filters( 'tec_tickets_commerce_is_enabled' );
		remove_all_filters( 'tribe_get_option' );
		
		// Reset environment variable.
		if ( $original_env !== false ) {
			putenv( "TEC_TICKETS_COMMERCE=$original_env" );
		} else {
			putenv( 'TEC_TICKETS_COMMERCE' );  // Unset
		}
		
		// Assert the provider is returned correctly when Tickets Commerce is enabled.
		$this->assertNotFalse( $provider, 'Provider should not be false when Tickets Commerce is enabled.' );
		$this->assertInstanceOf( \TEC\Tickets\Commerce\Module::class, $provider, 'Provider should be a Tickets Commerce Module instance.' );
	}
}
