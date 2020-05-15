<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Test_Case;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Handler;

class GetTotalEventCapacityTest extends Test_Case {


	private $cap_key = Global_Stock::TICKET_STOCK_MODE;
	private $global_mode = Global_Stock::GLOBAL_STOCK_MODE;
	private $capped_mode = Global_Stock::CAPPED_STOCK_MODE;
	private $global_cap = 50;

	/**
	 * @var Handler
	 */
	private $handler;

	/**
	 * ID of a created TEC Event.
	 *
	 * @see \Tribe\Events\Test\Factories\Event::create_object()
	 *
	 * @var int
	 */
	private $event_id;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		$this->handler = tribe( 'tickets.handler' );
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
		add_post_meta( $this->event_id, $this->handler->key_capacity, $cap );
	}

	/**
	 * @test
	 * It should get correct capacity with "own" tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
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
					$this->handler->key_capacity   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( ( $num_tickets * $capacity ), $test_data, 'Incorrect capacity with own tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with unlimited tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
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
					$this->handler->key_capacity   => $capacity,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect capacity with own tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with "global" tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_global_tickets() {
		$this->setupGlobalStock();

		$num_tickets = 5;
		$capacity    = $this->global_cap / $num_tickets; // Evenly spaced capacities, although not required to be.
		$sales       = 3;
		$stock       = $capacity - $sales;
		$ticket_data = [];

		for ( $i = 0; $i < $num_tickets; $i ++ ) {
			$ticket_data[] = [
				'meta_input' => [
					'_stock'       => $stock,
					'total_sales'  => $sales,
					$this->cap_key => $this->global_mode,
				],
			];
		}

		// create all of our tickets
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			$ticket_data,
			$this->global_cap
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( $this->global_cap, $test_data, 'Incorrect capacity with global tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with "capped" tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_capped_tickets() {
		$num_tickets = 5;
		$capacity    = $this->global_cap / $num_tickets; // Evenly spaced capacities, although not required to be.
		$sales       = 3;
		$stock       = $capacity - $sales;
		$ticket_data = [];

		for ( $i = 0; $i < $num_tickets; $i ++ ) {
			$ticket_data[] = [
				'meta_input' => [
					$this->handler->key_capacity => $capacity,
					'_stock'                     => $stock,
					'total_sales'                => $sales,
				],
			];
		}

		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			$ticket_data,
			$this->global_cap
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( $this->global_cap, $test_data, 'Incorrect capacity with capped tickets.' );
	}

	/**
	 * @test
	 * It should get correct capacity with mixed tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
	 */
	public function it_should_get_correct_capacity_with_mixed_tickets() {
		$capacity    = 10;
		$sales       = 2;

		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						$this->handler->key_capacity => $capacity,
						'total_sales'                => $sales,
					],
				],
				[
					'meta_input' => [
						'total_sales'  => $sales,
						$this->cap_key => $this->global_mode,
					],
				],
				[
					'meta_input' => [
						'total_sales'  => $sales,
						$this->cap_key => $this->capped_mode,
					],
				]
			],
			$capacity
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		// 1 ticket with unshared 10 and 2 tickets both sharing 10 = 20 expected, not 30.
		$this->assertEquals( ( $capacity * 2 ), $test_data, 'Incorrect capacity with mixed tickets.' );
	}

	/**
	 * @test
	 * It should get correct unlimited capacity with mixed tickets.
	 *
	 * @covers ::tribe_get_event_capacity()
	 */
	public function it_should_get_correct_unlimited_capacity_with_mixed_tickets() {
		$this->setupGlobalStock();

		// Add a "standard" ticket.
		$ticket_a_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					$this->handler->key_capacity     => 10,
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
					$this->handler->key_capacity     => 10,
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
					$this->handler->key_capacity     => -1,
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
					$this->handler->key_capacity     => 10,
					'total_sales'   => 2,
					$this->cap_key => $this->capped_mode
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect capacity with mixed unlimited tickets.' );
	}

}
