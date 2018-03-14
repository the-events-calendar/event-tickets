<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Handler__Invalid_PDT as Invalid;
use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Notices as Notices;

class GatewayTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var string
	 */
	protected $identity_token = '';

	/**
	 * @var Notices
	 */
	protected $notices;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->notices = $this->prophesize( Notices::class );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @return Gateway
	 */
	private function make_instance() {
		tribe_update_option( 'ticket-paypal-identity-token', $this->identity_token );

		return new Gateway( $this->notices->reveal() );
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Gateway::class, $sut );
	}

	/**
	 * It should instance PDT handler if identity token set and request looks like PDT request
	 *
	 * @test
	 */
	public function should_instance_pdt_handler_if_identity_token_set_and_request_looks_like_pdt_request() {
		$this->set_pdt_as_default_payment_gateway();
		$_GET['tx']           = '223423424234234';
		$this->identity_token = 'foobar';

		$sut = $this->make_instance();

		$this->assertInstanceOf( PDT::class, $sut->build_handler() );
	}

	/**
	 * It should instance IPN handler if identity token not set and requests does not look like PDT
	 *
	 * @test
	 */
	public function should_instance_ipn_handler_if_identity_token_not_set_and_request_does_not_look_like_pdt() {
		unset( $_GET['tx'] );
		$this->identity_token = '';

		$sut = $this->make_instance();

		$this->assertInstanceOf( IPN::class, $sut->build_handler() );
	}

	/**
	 * It should show admin notice if request looks like PDT but identity token not set
	 *
	 * @test
	 */
	public function should_show_admin_notice_if_request_looks_like_pdt_but_identity_token_not_set() {
		$this->set_pdt_as_default_payment_gateway();
		$_GET['tx']           = '223423424234234';
		$this->identity_token = '';
		$this->notices->show_missing_identity_token_notice()->shouldBeCalled();

		$sut = $this->make_instance();

		$this->assertInstanceOf( Invalid::class, $sut->build_handler() );
	}

	protected function set_pdt_as_default_payment_gateway() {
		add_filter( 'tribe_tickets_commerce_paypal_handler', function () {
			return 'pdt';
		} );
	}
}