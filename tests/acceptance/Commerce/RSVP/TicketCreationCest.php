<?php

namespace Commerce\RSVP;

use AcceptanceTester;

class TicketCreationCest {

	public function _before( AcceptanceTester $I ) {
		// Login as admin for all tests
		$I->loginAsAdmin();
	}

	/**
	 * Should be able to create RSVP tickets in the back-end for tickets-enabled post types
	 *
	 * @test
	 * @dataProvider post_types_provider
	 */
	public function create_rsvp_ticket( AcceptanceTester $I, \Codeception\Example $example ) {

		// ARRANGE 
		// Use example Post Type
		$post_type = $example['post_type'];
		// Enable tickets for test post type
		$I->haveTicketablePostTypes( [$post_type] );
		// Create a sample post for test post type
		$post_type_id = $I->havePostInDatabase( [ 'post_name' => 'sample', 'post_type' => $post_type ] );
		$I->amEditingPostWithId( $post_type_id );
		
		// ACT
		// Create new RSVP ticket
		$I->click( '#rsvp_form_toggle' );
		$I->fillField( '#ticket_name', 'Free Ticket' );
		$I->fillField( '#Tribe__Tickets__RSVP_capacity', '3' );
		$I->click( '#rsvp_form_save' );
		// Wait for the ticket to be created before trying to assert
		$I->waitForElement( '#tribe_ticket_list_table', 10 );

		// ASSERT
		// Let's see if the ticket was correctly added to the post in the backend
		$I->see( 'Free Ticket', '.ticket_name' );
		$I->see( '3', '.ticket_capacity' );
		$I->see( '3', '.ticket_available' );
	}

	/**
	 * Shouldn't be able to create RSVP tickets in the back-end for tickets-disabled post types
	 *
	 * @test
	 * @dataProvider post_types_provider
	 */
	public function cannot_create_rsvp_ticket_for_disabled_post_type( AcceptanceTester $I, \Codeception\Example $example ) {

		// ARRANGE
		// Use example Post Type
		$post_type = $example['post_type'];
		// Disable tickets for all post types
		$I->haveTicketablePostTypes( ['none_existing'] );
		// Create a sample post for test post type
		$post_type_id = $I->havePostInDatabase( [ 'post_name' => 'sample', 'post_type' => $post_type ] );
		$I->amEditingPostWithId( $post_type_id );

		// ASSERT
		// Assert we are on the edit page
		$I->seeElement( '#title' );
		// Cannot create new RSVP ticket
		$I->dontSeeElement( '#rsvp_form_toggle' );
	}

	/**
	 *
	 * Provides post types as examples for tests
	 *
	 * @return array
	 */
	protected function post_types_provider () {
		return [
			[ 'post_type' => 'post' ],
			[ 'post_type' => 'page' ]
		];
	}
}