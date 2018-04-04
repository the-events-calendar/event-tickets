<?php

namespace Commerce\PayPal;


use FunctionalTester;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class ReturnToCartCest {
	use PayPal_Ticket_Maker;

	/**
	 * @var \WP_UnitTest_Factory
	 */
	protected $factory;

	public function _before( FunctionalTester $I ) {
		// set the factory to satisfy the PayPal_Ticket_Maker trait
		$this->factory = $I->factory();
		// let's make sure we're not redirected to the welcome page when accessing admin
		update_option( 'tribe_skip_welcome', '1' );
		// let's make sure posts can be ticketed
		tribe_update_option( 'ticket-enabled-post-types', [ 'post' ] );
	}

	public function _after( FunctionalTester $I ) {
	}

	/**
	 * It should not display a return to cart link if there are no PayPal tickets in the cart
	 *
	 * @test
	 */
	public function should_not_display_a_return_to_cart_link_if_there_are_no_pay_pal_tickets_in_the_cart(FunctionalTester $I) {
		$post_id   = $I->havePostInDatabase( [ 'post_status' => 'public' ] );
		$this->make_ticket( $post_id, 1 );
		$I->amOnPage( "/?p={$post_id}" );
		$I->seeElement( '.tribe-commerce.return-to-cart-link' );
	}

	/**
	 * It should display a return to cart link if there are PayPal tickets in the cart
	 *
	 * @test
	 */
	public function should_display_a_return_to_cart_link_if_there_are_pay_pal_tickets_in_the_cart(FunctionalTester $I) {
		$post_id = $I->havePostInDatabase( [ 'post_status' => 'public' ] );
		$I->amOnPage( "/?p={$post_id}" );
		$I->dontSeeElement( '.tribe-commerce.return-to-cart-link' );
	}
}
