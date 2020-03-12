<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

class Tickets_HandlerTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( $this->get_paypal_ticket_provider() )->plugin_name;

			return $modules;
		} );

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post', 'tribe_events' ];
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Tickets_Handler::class, $sut );
	}

	/**
	 * @return Tickets_Handler
	 */
	private function make_instance() {
		return new Tickets_Handler();
	}

	/**
	 * @test
	 * it should get the default ticket max purchase
	 */
	public function it_should_get_default_ticket_max_purchase() {
		$sut = $this->make_instance();

		$post_id = $this->factory()->post->create();

		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 500,
			],
		] );

		$max_quantity = $sut->get_ticket_max_purchase( $ticket_id );

		// Default max is 100, but capacity is 500 so it's limited to 100.
		$this->assertEquals( 100, $max_quantity );
	}

	/**
	 * @test
	 * it should get the default ticket max purchase for unlimited ticket
	 */
	public function it_should_get_default_ticket_max_purchase_for_unlimited_ticket() {
		$sut = $this->make_instance();

		$post_id = $this->factory()->post->create();

		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'tribe-ticket' => [
				'mode'     => '',
				'capacity' => '',
			],
		] );

		$max_quantity = $sut->get_ticket_max_purchase( $ticket_id );

		// Default max is 100, but capacity is 500 so it's limited to 100.
		$this->assertEquals( 100, $max_quantity );
	}

	/**
	 * @test
	 * it should get the lesser available ticket max purchase
	 */
	public function it_should_get_lesser_available_ticket_max_purchase() {
		$sut = $this->make_instance();

		$post_id = $this->factory()->post->create();

		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'tribe-ticket' => [
				'mode'     => Global_Stock::OWN_STOCK_MODE,
				'capacity' => 50,
			],
		] );

		$max_quantity = $sut->get_ticket_max_purchase( $ticket_id );

		// Default max is 100, but capacity is 50 so it's less and that's returned.
		$this->assertEquals( 50, $max_quantity );
	}
}
