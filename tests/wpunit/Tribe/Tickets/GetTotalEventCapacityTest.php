<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Tickets_Handler as Handler;
use Tribe__Cache as Cache;

class GetTotalEventCapacityTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;

	private $cap_key     = Global_Stock::TICKET_STOCK_MODE;
	private $global_mode = Global_Stock::GLOBAL_STOCK_MODE;
	private $capped_mode = Global_Stock::CAPPED_STOCK_MODE;
	private $cap         = Global_Stock::TICKET_STOCK_CAP;
	private $global_cap  = 50;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		// Tribe__Tickets__Tickets_Handler handler for easier access
		$this->handler = new Handler;

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
		// refresh the event ID for each test.
		unset( $this->event_id );

		// then
		parent::tearDown();
	}

	private function setupGlobalStock( $cap = null ) {
		add_post_meta( $this->event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		$cap = is_null( $cap ) ? $this->global_cap : $cap;
		add_post_meta( $this->event_id, Global_Stock::GLOBAL_STOCK_LEVEL, $cap );
	}

	/**
	 * @test
	 * It should get correct capacity with "own" tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_own_tickets() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( ( $num_tickets * $capacity ), $test_data, 'Incorrect capacity with own tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with unlimited tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_unlimited_tickets() {
		$num_tickets = 5;
		$capacity    = -1;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect capacity with own tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with "global" tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_global_tickets() {
		$global_cap = 25;
		$this->setupGlobalStock();

		$num_tickets = 5;
		$capacity    = $global_cap / $num_tickets;
		$stock       = 3;
		$sales       = 2;
		$ticket_data = [];

		for ( $i = 0; $i < $num_tickets; $i++ ) {
			$ticket_data[] = [
				'meta_input' => [
					'_capacity'    => $capacity,
					'_stock'       => $stock,
					'total_sales'  => $sales,
					$this->cap_key => $this->global_mode
				],
			];
		}

		// create 5 tickets
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			$ticket_data,
			$this->global_cap
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( $this->global_cap, $test_data, 'Incorrect capacity with global tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with "capped" tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_capped_tickets() {
		$num_tickets = 5;
		$capacity    = $this->global_cap / $num_tickets;
		$stock       = 3;
		$sales       = 2;
		$ticket_data = [];

		for ( $i = 0; $i < $num_tickets; $i++ ) {
			$ticket_data[] = [
				'meta_input' => [
					'_capacity'    => $capacity,
					'_stock'       => $stock,
					'total_sales'  => $sales,
					$this->cap_key => $this->capped_mode
				],
			];
		}

		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			$ticket_data,
			$this->global_cap
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( $this->global_cap, $test_data, 'Incorrect capacity with capped tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with mixed tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_mixed_tickets() {
		$capacity    = 10;
		$sales       = 2;

		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'    => $capacity,
						'total_sales'  => $sales,
					],
				],
				[
					'meta_input' => [
						'_capacity'    => $capacity,
						'total_sales'  => $sales,
						$this->cap_key => $this->global_mode,
					],
				],
				[
					'meta_input' => [
						'_capacity'    => $capacity,
						'total_sales'  => $sales,
						$this->cap_key => $this->capped_mode,
					],
				]
			],
			$capacity
		);

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( ( $capacity * 3 ), $test_data, 'Incorrect capacity with mixed tickets.' );
	}

	/**
	 * @test
	 * It should get correct unlimited capacity with mixed tickets.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_total_event_capacity()
	 */
	public function it_should_get_correct_unlimited_capacity_with_mixed_tickets() {
		$this->setupGlobalStock();

		// Add a "standard" ticket.
		$ticket_a_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 10,
					'total_sales'   => 2,
				],
			]
		);

		// Add a "global" ticket.
		$ticket_a_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 10,
					'total_sales'   => 2,
					$this->cap_key => $this->global_mode
				],
			]
		);

		// Add an unlimited ticket.
		$ticket_a_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => -1,
					'total_sales'   => 2,
				],
			]
		);

		// Add a "capped" ticket.
		$ticket_a_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 10,
					'total_sales'   => 2,
					$this->cap_key => $this->capped_mode
				],
			]
		);

		$test_data = $this->handler->get_total_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect capacity with mixed unlimited tickets.' );
	}

}
