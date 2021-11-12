<?php

namespace TEC\Tickets\Commerce;

use AcceptanceTester;

/**
 * Class ReturnToCartCest
 *
 * @package TEC\Tickets\Commerce
 */
class CartCest {

	/**
	 * @var \WP_UnitTest_Factory
	 */
	protected $factory;

	/**
	 * @var string
	 */
	private $return_to_cart_link = '.tribe-commerce.return-to-cart';

	public function _before( AcceptanceTester $I ) {
		/*
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
		*/
	}

	/**
	 * It should not display a return to cart link if there are no PayPal tickets in the cart
	 *
	 * This test is currently not working because we changed the templates to be built out by JS and the links are now
	 * different markup.
	 *
	 * @test
	 */
	public function should_not_display_a_return_to_cart_link_if_there_are_no_paypal_tickets_in_the_cart( AcceptanceTester $I ) {
		/*
		$this->create_paypal_ticket_basic( $post_id, 1 );

		$I->amOnPage( "/?p={$post_id}" );

		$I->dontSeeElement( $this->return_to_cart_link );
		*/

		// Go to the Event page.
		$I->amOnPage( '/event/rsvp-test/' );

		// Confirm that we see the RSVP for the Event.
		$I->waitForText( 'Job & Career Fair' );
		$I->seeElement( ".tribe-tickets__rsvp-wrapper" );

		// Click on "Going".
		$I->click( ".tribe-tickets__rsvp-actions-button-going" );
	}
}
