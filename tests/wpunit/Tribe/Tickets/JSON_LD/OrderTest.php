<?php

namespace Tribe\Tickets\JSON_LD;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Settings;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe__Tickets__JSON_LD__Order as JSON_LD_Order;

class OrderTest extends \Codeception\TestCase\WPTestCase {

	use TC_Ticket_Maker;

	/**
	 * @var int
	 */
	protected $post_id;

	/**
	 * @var \Tribe__Tickets__Ticket_Object
	 */
	protected $ticket;

	/**
	 * @before
	 */
	public function set_up_ticket() {
		$this->post_id   = $this->factory()->post->create();
		$ticket_id       = $this->create_tc_ticket( $this->post_id, 10 );
		$this->ticket    = tribe( Module::class )->get_ticket( $this->post_id, $ticket_id );
	}

	/**
	 * @after
	 */
	public function reset_currency() {
		tribe_update_option( Settings::$option_currency_code, 'USD' );
	}

	/**
	 * @return JSON_LD_Order
	 */
	private function make_instance() {
		return JSON_LD_Order::instance();
	}

	/**
	 * @test
	 */
	public function it_should_be_instantiatable() {
		$this->assertInstanceOf( JSON_LD_Order::class, $this->make_instance() );
	}

	/**
	 * @test
	 * it should use the currency configured in Tickets Commerce settings
	 *
	 * @dataProvider currency_code_provider
	 */
	public function it_should_use_the_configured_tickets_commerce_currency( $currency_code ) {
		tribe_update_option( Settings::$option_currency_code, $currency_code );

		$currency = $this->make_instance()->get_price_currency( $this->ticket );

		$this->assertEquals( $currency_code, $currency, 'The schema currency should follow the Tickets Commerce currency setting.' );
	}

	/**
	 * Provides a set of currency codes to verify the schema follows the configured setting.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function currency_code_provider() {
		return [
			'Euro'            => [ 'EUR' ],
			'British Pound'   => [ 'GBP' ],
			'Japanese Yen'    => [ 'JPY' ],
			'Canadian Dollar' => [ 'CAD' ],
			'Brazilian Real'  => [ 'BRL' ],
		];
	}

	/**
	 * @test
	 * it should reflect changes to the Tickets Commerce currency setting
	 */
	public function it_should_reflect_a_non_default_currency() {
		tribe_update_option( Settings::$option_currency_code, 'GBP' );

		$currency = $this->make_instance()->get_price_currency( $this->ticket );

		$this->assertEquals( 'GBP', $currency );
		$this->assertNotEquals( 'USD', $currency, 'The currency should no longer be hardcoded to USD.' );
	}

	/**
	 * @test
	 * it should fall back to the provider currency when the option is empty
	 */
	public function it_should_fall_back_to_the_provider_currency_when_option_is_empty() {
		tribe_update_option( Settings::$option_currency_code, '' );

		$currency = $this->make_instance()->get_price_currency( $this->ticket );

		$this->assertEquals( $this->ticket->get_provider()->get_currency(), $currency );
	}
}
