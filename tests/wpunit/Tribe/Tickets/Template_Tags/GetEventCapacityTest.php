<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;

class GetEventCapacityTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;
	use RSVP_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();
		// Set up some reused vars.
		$this->num_tickets = 5;
		$this->capacity    = 5;
		$this->stock       = 3;
		$this->sales       = 2;

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

	/**
	 * @test
	 * It should get the correct global stock for an event.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
					],
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( 50, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with unlimited tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_unlimited_tickets() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => -1,
						'total_sales'                   => 5,
					],
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with shared tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_shared_tickets() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( 30, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with mixed tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_mixed_shared_and_own() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( 50, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with mixed tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_mixed_shared_and_unlimited() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => -1,
						'total_sales'                   => 5,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with mixed tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_rsvps() {

		$rsvp_ticket_id = $this->create_rsvp_ticket(
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'                     => 30,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( 30, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with mixed tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_unlimited_rsvps() {

		$rsvp_ticket_id = $this->create_rsvp_ticket(
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'                     => -1,
					'total_sales'                   => 5,
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( -1, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should get the correct stock for an event with mixed tickets.
	 *
	 * @covers tribe_get_event_capacity()
	 */
	 public function it_should_get_the_correct_stock_for_an_event_with_mixed_tickets_and_rsvps() {
		$ticket_ids = $this->create_distinct_paypal_tickets(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$rsvp_ticket_id = $this->create_rsvp_ticket(
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'                     => 30,
					'total_sales'                   => 5,
					Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
				],
			]
		);

		$test_data = tribe_get_event_capacity( $this->event_id );

		$this->assertEquals( 60, $test_data, 'Incorrect total capacity on global stock tickets.' );
	}
}
