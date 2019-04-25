<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Status__Manager as Manager;
use Tribe__Tickets__Commerce__PayPal__Status_Manager as TTPManager;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe__Tickets__Commerce__PayPal__Orders__Report as TPPOrderReport;

/**
 * Test Status TTPManager
 *
 * @group   core
 *
 * @package Tribe__Tickets__Commerce__PayPal__Status_Manager
 */
class TTPManagerTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Order_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {

		// before
		parent::setUp();

		$this->factory()->event = new Event();

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		/**
		 * Enable TTP
		 */
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
	}

	public function dont_die() {
		// no-op, go on
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( TTPManager::class, $sut );
	}

	/**
	 * @return TTPManager
	 */
	private function make_instance() {
		tribe_update_option( 'ticket-paypal-enable', true );

		return new TTPManager();
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_statues_names() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'status_names', $sut );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_status_classes() {

		$sut = $this->make_instance();
		$this->assertObjectHasAttribute( 'statuses', $sut );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_status_completed() {

		$sut = $this->make_instance();

		$this->assertArrayHasKey( 'Completed', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Completed']->count_completed );
	}

	public function it_has_status_denied() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Denied', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Denied']->incomplete );
	}

	public function it_has_status_not_completed() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Not_Completed', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Not_Completed']->incomplete );
	}

	public function it_has_status_pending() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Pending', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Pending']->count_sales );
	}

	public function it_has_status_refunded() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Refunded', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Refunded']->count_refunded );
	}

	public function it_has_status_undefined() {

		$sut = $this->make_instance();
		$this->assertArrayHasKey( 'Undefined', $sut->statuses );
		$this->assertEquals( true, $sut->statuses['Undefined']->incomplete );
	}

	/**
	 * @test
	 * @since 4.10
	 */
	public function it_has_all_ttp_statues() {

		//run setup again to get the active modules that will include Tribe Commerce
		Manager::get_instance()->setup();
		$this->assertSame( array(
			'completed',
			'denied',
			'not-completed',
			'pending-payment',
			'refunded',
			'undefined',
		), Manager::get_instance()->get_statuses_by_action( 'all', 'tpp' ) );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_tpp_status_object_named_going() {

		$sut = $this->make_instance();
		$completed = $sut->get_completed_status_class();

		$this->assertSame( 'Completed', $completed->name );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_tpp_correct_order_counts() {

		$sut   = $this->make_instance();
		$sales = tribe( 'tickets.commerce.paypal.orders.sales' );

		$event_id  = $this->factory()->event->create();
		$ticket_id = $this->create_paypal_ticket( $event_id, 5, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );
		$this->generate_orders( $event_id, [ $ticket_id ], 5, 4, 'completed' );
		$this->generate_orders( $event_id, [ $ticket_id ], 2, 1, 'pending-payment' );
		$this->generate_orders( $event_id, [ $ticket_id ], 3, 1, 'refunded' );
		$this->generate_orders( $event_id, [ $ticket_id ], 2, 1, 'denied' );
		$this->generate_orders( $event_id, [ $ticket_id ], 1, 1, 'undefined' );
		$paypal_tickets = Tickets::get_event_tickets( $event_id );

		$report = new TPPOrderReport();
		$report->get_all_counts_per_ticket( $paypal_tickets, $sut, $sales );

		$this->assertSame( 28, $sut->get_qty(), 'Matches Total Quantity Ordered for an Event' );
		$this->assertSame( 140, $sut->get_line_total(), 'Matches the Line Total for an Event' );

		$this->assertSame( 20, $sut->statuses['Completed']->get_qty(), 'Matches Completed Quantity Ordered for an Event' );
		$this->assertSame( 100, $sut->statuses['Completed']->get_line_total(), 'Matches the Completed Line Total for an Event' );

		$this->assertSame( 2, $sut->statuses['Pending']->get_qty(), 'Matches Pending Quantity Ordered for an Event' );
		$this->assertSame( 10, $sut->statuses['Pending']->get_line_total(), 'Matches the Pending Line Total for an Event' );

		$this->assertSame( 3, $sut->statuses['Refunded']->get_qty(), 'Matches Refunded Quantity Ordered for an Event' );
		$this->assertSame( 15, $sut->statuses['Refunded']->get_line_total(), 'Matches the Refunded Line Total for an Event' );

		$this->assertSame( 2, $sut->statuses['Denied']->get_qty(), 'Matches Denied Quantity Ordered for an Event' );
		$this->assertSame( 10, $sut->statuses['Denied']->get_line_total(), 'Matches the Denied Line Total for an Event' );

		$this->assertSame( 1, $sut->statuses['Undefined']->get_qty(), 'Matches Undefined Quantity Ordered for an Event' );
		$this->assertSame( 5, $sut->statuses['Undefined']->get_line_total(), 'Matches the Undefined Line Total for an Event' );
	}

	/**
	 * @test
	 * @since TBD
	 */
	public function it_has_tpp_correct_order_counts_for_multiple_tickets() {

		$sut   = $this->make_instance();
		$sales = tribe( 'tickets.commerce.paypal.orders.sales' );

		$event_id  = $this->factory()->event->create();
		$ticket_id_1 = $this->create_paypal_ticket( $event_id, 5, [
			'meta_input' => [
				'_stock'    => 50,
				'_capacity' => 50,
			]
		] );
		$ticket_id_2 = $this->create_paypal_ticket( $event_id, 4, [
			'meta_input' => [
				'_stock'    => 20,
				'_capacity' => 20,
			]
		] );
		$this->generate_orders( $event_id, [ $ticket_id_1 ], 5, 3, 'completed' );
		$this->generate_orders( $event_id, [ $ticket_id_2 ], 3, 2, 'completed' );
		$paypal_tickets = Tickets::get_event_tickets( $event_id );

		$report = new TPPOrderReport();
		$report->get_all_counts_per_ticket( $paypal_tickets, $sut, $sales );

		$this->assertSame( 21, $sut->get_qty(), 'Matches Total Quantity Ordered of all tickets for an Event' );
		$this->assertSame( 99, $sut->get_line_total(), 'Matches the Line Total of all tickets for an Event' );

		$this->assertSame( 21, $sut->statuses['Completed']->get_qty(), 'Matches Completed Quantity Ordered of all tickets for an Event' );
		$this->assertSame( 99, $sut->statuses['Completed']->get_line_total(), 'Matches the Completed Line Total of all tickets for an Event' );
	}

}
