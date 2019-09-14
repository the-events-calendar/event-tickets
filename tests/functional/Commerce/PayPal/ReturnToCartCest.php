<?php

namespace Commerce\PayPal;


use FunctionalTester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

/**
 * Class ReturnToCartCest
 *
 * This test is currently not working because we changed the templates to be built out by JS and the links are now different markup.
 *
 * @skip
 *
 * @package Commerce\PayPal
 */
class ReturnToCartCest {
	use PayPal_Ticket_Maker;

	/**
	 * @var \WP_UnitTest_Factory
	 */
	protected $factory;

	/**
	 * @var string
	 */
	private $return_to_cart_link = '.tribe-commerce.return-to-cart';


	public function _before( FunctionalTester $I ) {
		// set the factory to satisfy the PayPal_Ticket_Maker trait
		$this->factory = $I->factory();
		// let's make sure we're not redirected to the welcome page when accessing admin
		$I->haveOptionInDatabase( 'tribe_skip_welcome', '1' );
		// let's make sure posts can be ticketed
		$I->setTribeOption( 'ticket-enabled-post-types', [ 'post', 'page' ] );
		// let's make sure Tribe Commerce is enabled and configured
		$I->setTribeOption( 'ticket-paypal-enable', 'yes' );
		$I->setTribeOption( 'ticket-paypal-email', 'admin@tribe.localhost' );
		$I->setTribeOption( 'ticket-paypal-sandbox', 'yes' );
		$I->setTribeOption( 'ticket-paypal-configure', 'yes' );
		$I->setTribeOption( 'ticket-paypal-ipn-config-status', 'yes' );
		$I->setTribeOption( 'ticket-paypal-ipn-enabled', 'yes' );
		$I->setTribeOption( 'ticket-paypal-ipn-address-set', 'yes' );
	}

	/**
	 * It should not display a return to cart link if there are no PayPal tickets in the cart
	 *
	 * @test
	 */
	public function should_not_display_a_return_to_cart_link_if_there_are_no_pay_pal_tickets_in_the_cart( FunctionalTester $I ) {
		$post_id = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$this->create_paypal_ticket( $post_id, 1 );

		$I->amOnPage( "/?p={$post_id}" );

		$I->dontSeeElement( $this->return_to_cart_link );
	}

	/**
	 * It should display a return to cart link if there are PayPal tickets in the cart
	 *
	 * @test
	 */
	public function should_display_a_return_to_cart_link_if_there_are_pay_pal_tickets_in_the_cart( FunctionalTester $I ) {
		$post_id   = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$ticket_id = $this->create_paypal_ticket( $post_id, 1 );
		$transient = \Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged::get_transient_name( '123foo' );
		$I->haveTransientInDatabase( $transient, [ $ticket_id => 2 ] );

		$I->setCookie( \Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name, '123foo' );
		$I->amOnPage( "/?p={$post_id}" );

		$I->seeElement( $this->return_to_cart_link );
	}

	/**
	 * It should display the return to cart link on page of another ticketed post
	 *
	 * @test
	 */
	public function should_display_the_return_to_cart_link_on_page_of_another_ticketed_post( FunctionalTester $I ) {
		$post_one_id   = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$post_two_id   = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$ticket_one_id = $this->create_paypal_ticket( $post_one_id, 1 );
		$ticket_two_id = $this->create_paypal_ticket( $post_two_id, 1 );
		$transient     = \Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged::get_transient_name( '123foo' );
		$I->haveTransientInDatabase( $transient, [ $ticket_one_id => 2 ] );

		$I->setCookie( \Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name, '123foo' );
		$I->amOnPage( "/?p={$post_two_id}" );

		$I->seeElement( $this->return_to_cart_link );
	}

	/**
	 * It should display the return to cart link on non ticketed ticket-able post
	 *
	 * @test
	 */
	public function should_display_the_return_to_cart_link_on_non_ticketed_ticket_able_post( FunctionalTester $I ) {
		$post_id       = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$page_id       = $I->havePostInDatabase( [ 'post_status' => 'publish' ] );
		$ticket_one_id = $this->create_paypal_ticket( $post_id, 1 );
		$ticket_two_id = $this->create_paypal_ticket( $page_id, 1 );
		$transient     = \Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged::get_transient_name( '123foo' );
		$I->haveTransientInDatabase( $transient, [ $ticket_one_id => 2 ] );

		$I->setCookie( \Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name, '123foo' );
		$I->amOnPage( "/?p={$page_id}" );

		$I->seeElement( $this->return_to_cart_link );
	}
}
