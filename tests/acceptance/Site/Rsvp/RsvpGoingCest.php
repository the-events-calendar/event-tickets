<?php
namespace Site\Rsvp;

use AcceptanceTester;

class RsvpGoingCest {
	public function _before( AcceptanceTester $I ) {

		// Log in as an admin.
		$I->loginAsAdmin();

		// Activate required plugins.
		$I->amOnAdminPage( '/plugins.php' );
		$I->activatePlugin(
			[
				'the-events-calendar',
				'event-tickets',
			]
		);

		// Set site options.
		$I->haveOptionInDatabase( 'permalink_structure', '/%postname%/' );
		$I->haveOptionInDatabase( 'showEventsInMainLoop', 'yes' );

		// Set tribe options.
		$I->setTribeOption( 'toggle_blocks_editor', 1 );
		$I->setTribeOption( 'tickets_rsvp_use_new_views', 1 );
	}

	/*
	 * @test
	 */
	public function should_confirm_rsvp_going_flow( AcceptanceTester $I ) {
		// Go to the Event page.
		$I->amOnPage( '/event/rsvp-test/' );

		// Confirm that we see the RSVP for the Event.
		$I->waitForText( 'Job & Career Fair' );
		$I->seeElement( ".tribe-tickets__rsvp-wrapper" );

		// Click on "Going".
		$I->click( ".tribe-tickets__rsvp-actions-button-going" );

		// Confirm that we see the "Going Form".
		$I->waitForText( 'Please submit your RSVP information, including the total number of guests.' );
		$I->seeElement( "form[name='tribe-tickets-rsvp-form']" );

		// Let's check if the validation works.
		$I->clearField( '.tribe-tickets__rsvp-form-field-name' );

		// Try to submit the form.
		$I->click( "form[name='tribe-tickets-rsvp-form'] button[type='submit']" );

		// Not seeing the loader will mean that it didn't submit.
		$I->dontSeeElement( '.tribe-common-c-loader' );

		// Re-fill the RSVP name field.
		$I->fillField( '.tribe-tickets__rsvp-form-field-name', 'Juan Doe' );

		// Submit the form.
		$I->click( "form[name='tribe-tickets-rsvp-form'] button[type='submit']" );

		// Check that the RSVP was confirmed.
		$I->waitForText( 'Your RSVP has been received' );
		$I->seeElement( '.tribe-tickets__rsvp-message--success' );

		// Reload the Event.
		$I->reloadPage();

		// Check that the view RSVP links is there on reload.
		$I->waitForText( 'You have 1 RSVP for this Event' );
		$I->seeElement( '.tribe-link-view-attendee' );
	}

}
