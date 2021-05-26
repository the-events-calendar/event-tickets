<?php

namespace Tribe\Tickets\Commerce\PayPal\Stock;

use Codeception\TestCase\WPTestCase;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Test_Case;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as Main;
use Tribe__Tickets__Data_API as Data_API;

class ConfigState {

	use PayPal_Ticket_Maker;

	/**
	 * @var \Tribe__Tickets__Commerce__PayPal__Main
	 */
	public $provider;

	/**
	 * @var \Tribe__Tickets__Ticket_Object
	 */
	public $ticket_a;

	/**
	 * @var \Tribe__Tickets__Ticket_Object
	 */
	public $ticket_b;

	/**
	 * @var int Event ID.
	 */
	public $event_id;

	/**
	 * @var array Stock config for test.
	 */
	public $shared_config;

	/**
	 * @var \Tribe__Tickets__Global_Stock Global Stock Object.
	 */
	public $global_stock;

	public function __construct() {
		$maker = new Event();
		$this->event_id = $maker->create();

		$this->provider = tribe( 'tickets.commerce.paypal' );

		$this->shared_config = [
			'total_cap'    => 30,
			'capped_limit' => 15,
		];

		$this->create_shared_cap_tickets();

		$this->global_stock = new \Tribe__Tickets__Global_Stock( $this->event_id );
	}

	public function create_shared_cap_tickets() {
		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => $this->shared_config['total_cap'],
				'capacity'       => $this->shared_config['capped_limit'],
			],
		];
		$ticket_a_id   = $this->create_paypal_ticket( $this->event_id, 10, $overrides );

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => $this->shared_config['total_cap'],
				'capacity'       => $this->shared_config['total_cap'],
			],
		];

		$ticket_b_id   = $this->create_paypal_ticket( $this->event_id, 20, $overrides );

		$this->ticket_a = $this->provider->get_ticket( $this->event_id, $ticket_a_id );
		$this->ticket_b = $this->provider->get_ticket( $this->event_id, $ticket_b_id );
	}
}
/**
 * Test Shared capacity Calculations
 *
 *
 * Class namespace Tribe\Tickets_Plus\Commerce\WooCommerce;
 *
 * @package Tribe\Tickets_Plus\Commerce\WooCommerce\Stock
 */
class SharedCapacityTest extends WPTestCase {

	use PayPal_Ticket_Maker;
	use PayPal_Order_Maker;

	/**
	 * @var ConfigState
	 */
	public static $config_state;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		self::$config_state = new ConfigState();


	}

	public function setUp() {
		// before
//		parent::setUp();

		// let's avoid die()s
//		add_filter( 'tribe_exit', function () {
//			return [ $this, 'dont_die' ];
//		} );
//
//		// let's avoid confirmation emails
//		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );
//
		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
//
//		// Reset Data_API object so it sees Tribe Commerce.
//		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function get_config() {
		return self::$config_state;
	}

	/**
	 * @test
	 */
	public function it_should_create_tickets_correctly() {

		$config = $this->get_config();
		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $config->ticket_a );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $config->ticket_b );

		// Shared cap for Tickets should be set as expected.
		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->inventory() );
		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->available() );
		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->capacity() );
		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->stock() );

		$this->assertEquals( $config->shared_config['total_cap'], $config->ticket_b->inventory() );
		$this->assertEquals( $config->shared_config['total_cap'], $config->ticket_b->available() );
		$this->assertEquals( $config->shared_config['total_cap'], $config->ticket_b->capacity() );
		$this->assertEquals( $config->shared_config['total_cap'], $config->ticket_b->stock() );

		$this->assertEquals( $config->shared_config['total_cap'], tribe_get_event_capacity( $config->event_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_set_event_shared_meta_properly() {
		$config = $this->get_config();

		$this->assertTrue( $config->global_stock->is_enabled() );
		$this->assertEquals( $config->shared_config['total_cap'], tribe_get_event_capacity( $config->event_id ) );


//		$this->assertEquals( $config->shared_config['total_cap'], $config->global_stock->get_stock_level() );
//		$this->assertEquals( $config->shared_config['total_cap'], tribe_get_event_capacity( $config->event_id ) );
	}
//
//	/**
//	 * @test
//	 * @skip
//	 */
//	public function it_should_update_stock_when_order_is_placed() {
//
//		$config = $this->get_config();
//
//		$ticket_a_qty = 5;
//
//		$current_global_stock = $config->global_stock->get_stock_level();
//		$new_global_stock     = $current_global_stock - $ticket_a_qty;
//
//		$remaining_a = (int) $config->ticket_a->stock() - $ticket_a_qty;
//		$order_a     = $this->create_woocommerce_order( $config->ticket_a->ID, $ticket_a_qty, [ 'status' => 'completed' ] );
//
//		$config->ticket_a = $config->provider->get_ticket( $config->event_id, $config->ticket_a->ID );
//
//		$this->assertEquals( $remaining_a, $config->ticket_a->inventory() );
//		$this->assertEquals( $remaining_a, $config->ticket_a->available() );
//		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->capacity() );
//		$this->assertEquals( $remaining_a, $config->ticket_a->stock() );
//
//		$this->assertEquals( $new_global_stock, $config->global_stock->get_stock_level() );
//
//		$ticket_b_qty = 5;
//
//		$remaining_b    = $new_global_stock - $ticket_b_qty;
//		$order_b        = $this->create_woocommerce_order( $config->ticket_b->ID, $ticket_b_qty, [ 'status' => 'completed' ] );
//		$config->ticket_b = $config->provider->get_ticket( $config->event_id, $config->ticket_b->ID );
//
//		$this->assertEquals( $remaining_b, $config->ticket_b->inventory() );
//		$this->assertEquals( $remaining_b, $config->ticket_b->available() );
//		$this->assertEquals( $config->shared_config['total_cap'], $config->ticket_b->capacity() );
//		$this->assertEquals( $remaining_b, $config->ticket_b->stock() );
//
//		$this->assertEquals( $remaining_b, $config->global_stock->get_stock_level() );
//	}
//
//	/**
//	 * @test
//	 * @skip
//	 */
//	public function after_orders_global_stock_and_sales_count_should_match() {
//
//		$config = $this->get_config();
//
//		$ticket_a_sold = $config->ticket_a->qty_sold();
//		$ticket_b_sold = $config->ticket_b->qty_sold();
//
//		$total_sold = $ticket_a_sold + $ticket_b_sold;
//
//		$this->assertEquals( $ticket_a_sold + $ticket_b_sold, $config->global_stock->tickets_sold() );
//		$this->assertEquals( tribe_get_event_capacity( $config->event_id ) - $total_sold, $config->global_stock->get_stock_level() );
//	}
//
//	/**
//	 * @test
//	 * @skip
//	 */
//	public function after_deleting_capped_ticket_attendee_the_available_count_should_increase() {
//		$config = $this->get_config();
//
//		$ticket_a_attendees = $config->provider->get_attendees_by_id( $config->ticket_a->ID );
//
//		// Number of sold tickets should be the same as number of attendees.
//		$this->assertEquals(  $config->ticket_a->qty_sold(), count( $ticket_a_attendees ) );
//
//		$first_attendee_id = $ticket_a_attendees[0]['attendee_id'];
//
//		$this->assertNotEmpty( $first_attendee_id );
//
//		$prev_data = [
//			'inventory' => $config->ticket_a->inventory(),
//			'available' => $config->ticket_a->available(),
//			'capacity'  => $config->ticket_a->capacity(),
//			'stock'     => $config->ticket_a->stock(),
//		];
//
//		// Global stock before deletion.
//		$prev_stock = $config->global_stock->get_stock_level();
//
//		// Deleting the first ticket.
//		$config->provider->delete_ticket( $config->event_id, $first_attendee_id );
//
//		// refresh ticket.
//		$config->ticket_a = $config->provider->get_ticket( $config->event_id, $config->ticket_a->ID );
//
//		// refresh attendee count.
//		$ticket_a_attendees = $config->provider->get_attendees_by_id( $config->ticket_a->ID );
//
//		// Number of attendees should be 1 less then number of tickets sold.
//		$this->assertEquals(  $config->ticket_a->qty_sold() - 1, count( $ticket_a_attendees ) );
//
//		// Ticket Stock data should be updated accordingly.
//		$this->assertEquals( $prev_data['inventory'] + 1, $config->ticket_a->inventory() );
//		$this->assertEquals( $prev_data['available'] + 1, $config->ticket_a->available() );
//		$this->assertEquals( $config->shared_config['capped_limit'], $config->ticket_a->capacity() );
//		$this->assertEquals( $prev_data['stock'] + 1, $config->ticket_a->stock() );
//
//		// Global Stock should be updated by 1. Commented out for now as we need to fix this in the main code.
//		$this->assertEquals( $prev_stock + 1, $config->global_stock->get_stock_level() );
//
//		$ticket_a_product = wc_get_product( $config->ticket_a->ID );
//
//		// Ticket A product stock is synced with Ticket Object stock.
//		$this->assertEquals( $config->ticket_a->stock(), $ticket_a_product->get_stock_quantity() );
//	}
}
