<?php

namespace Tribe\Tickets\Commerce\PayPal\Stock;

use Codeception\TestCase\WPTestCase;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;

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
		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
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
		$this->assertEquals( $config->shared_config['total_cap'], $config->global_stock->get_stock_level() );
		$this->assertEquals( $config->shared_config['total_cap'], tribe_get_event_capacity( $config->event_id ) );
	}
}
