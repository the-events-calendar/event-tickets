<?php

namespace Commerce\RSVP;

use AcceptanceTester;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class FrontEndFormCest {
	use RSVP_Ticket_Maker;

	protected $factory;

	public function _before( AcceptanceTester $I ) {
		// the RSVP_Ticket_Maker will need this property to work
		$this->factory = $I->factory();
		// make sure the `post` post type is ticket-able
		$I->haveTicketablePostTypes( ['post'] );
	}

	/**
	 * It should show the RSVP form on the frontend of posts that have RSVP tickets
	 *
	 * @test
	 */
	public function should_show_the_rsvp_form_on_the_frontend_of_posts_that_have_rsvp_tickets( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->seeElement( 'form#rsvp-now' );
	}

	/**
	 * It should not show the ticket frontend form if RSVP tickets are not available
	 *
	 * @test
	 */
	public function should_not_show_the_ticket_frontend_form_if_rsvp_tickets_are_not_available( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3, [
			'meta_input' => [
				'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) )
			]
		] );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->dontSeeElement( 'form#rsvp-now' );
	}

	/**
	 * It should show frontend ticket form if post has at least one RSVP ticket available
	 *
	 * @test
	 */
	public function should_show_frontend_ticket_form_if_post_has_at_least_one_rsvp_ticket_available( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );
		$this->make_ticket( $post_id, 3, [
			'meta_input' => [
				'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) )
			]
		] );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->seeElement( 'form#rsvp-now' );
	}

	/**
	 * It should not show the ticket frontend form if tickets are disabled for post type
	 *
	 * TODO: #105930 will allow tickets-enabled-post-types to be empty, we can use empty instead of 'none_existing' post type
	 *
	 * @test
	 */
	public function should_not_show_the_ticket_frontend_form_if_tickets_are_disabled_for_post_type( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3);
		// Disable tickets for post type for now it cannot be empty
		$I->haveTicketablePostTypes( ['none_existing'] );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->dontSeeElement( 'form#rsvp-now' );
	}

	/**
	 * Should be able to 'purchase' an RSVP ticket
	 *
	 * @test
	 */
	public function should_be_able_to_rsvp( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// complete fields and purchase tickets
		$I->fillField( '.tribe-ticket-quantity', '3' );
		// unfocus field so name and email fields are shown
		$I->click( 'body' );
		// extra check in case there's a delay
		$I->waitForElementVisible( '#tribe-tickets-full-name', 10 );
		$I->fillField( '#tribe-tickets-full-name', 'Tester Name' );
		$I->fillField( '#tribe-tickets-email', 'tester@tri.be' );
		$I->click( '.tribe-button--rsvp' );

		// Assert
		$I->seeElement( '.tribe-rsvp-message-success' );
	}

	/**
	 * Should be blocked to 'purchase' an RSVP ticket if login block is on and I'm logged out
	 *
	 * @test
	 */
	public function login_block_should_be_blocked_if_logged_out( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );
		// Force login for RSVP
		$tribe_options = $I->grabOptionFromDatabase( 'tribe_events_calendar_options' );
		$tribe_options['ticket-authentication-requirements'] = [ 'event-tickets_rsvp' ];
		$I->haveOptionInDatabase( 'tribe_events_calendar_options', $tribe_options );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		// see login block link
		$I->seeElement( '.add-to-cart a' );
		// don't see RSVP button
		$I->dontSeeElement( '.tribe-button--rsvp' );
	}

	/**
	 * Should be allowed to 'purchase' an RSVP ticket if login block is on and I'm logged in
	 *
	 * @test
	 */
	public function login_block_should_not_be_blocked_if_logged_in( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );
		// Force login for RSVP
		$tribe_options = $I->grabOptionFromDatabase( 'tribe_events_calendar_options' );
		$tribe_options['ticket-authentication-requirements'] = [ 'event-tickets_rsvp' ];
		$I->haveOptionInDatabase( 'tribe_events_calendar_options', $tribe_options );

		// Act
		$I->loginAsAdmin();
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		// see RSVP button
		$I->seeElement( '.tribe-button--rsvp' );
	}

	/**
	 * Should see 'out of stock' if all tickets are purchased
	 *
	 * @test
	 */
	public function should_see_out_of_stock_if_no_tickets_available( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 0 );
		// user logged in to pre-fill name and email fields
		$I->loginAsAdmin();

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		// See tickets are out of stock
		$I->seeElement( '.tickets_nostock' );
	}

	/**
	 * Should see an error message when trying to purchase without filling name and email fields
	 *
	 * @test
	 */
	public function should_see_error_if_rsvp_with_empty_name_and_email( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );

		// Act
		// Try to purchase without filling name and email
		$I->amOnPage( "/?p={$post_id}" );
		$I->fillField( '.tribe-ticket-quantity', '1' );
		$I->click( '.tribe-button--rsvp' );

		// Assert
		// See error message, let's give the JS code some time to process
		$I->waitForElement( '.tribe-rsvp-message-error', 2 );
	}

	/**
	 * Should see an error message when trying to purchase without a ticket quantity
	 *
	 * @test
	 */
	public function should_see_error_if_rsvp_without_quantity( AcceptanceTester $I ) {
		
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );

		// Act
		// Try to purchase without filling quantity
		$I->amOnPage( "/?p={$post_id}" );
		$I->click( '.tribe-button--rsvp' );

		// Assert
		// See error message
		$I->seeElement( '.tribe-rsvp-message-error' );
	}

}
