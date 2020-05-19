<?php

namespace Settings\Tickets;

use AcceptanceTester;

class PostTypesCest {

	public function _before( AcceptanceTester $I ) {
		// make sure only ET is activated
		$I->haveOptionInDatabase( 'active_plugins', [ 'event-tickets/event-tickets.php' ] );
		$I->loginAsAdmin();
		// the Tickets page in Settings
		$I->amOnAdminPage( '/admin.php?page=tribe-common&tab=event-tickets' );
		// the `id` attribute of the section dedicated to ticket-able post types
		$I->seeElement( '#tribe-field-ticket-enabled-post-types' );
		// the ticket-able post types inputs should be 2, checked or not
		$I->seeNumberOfElements( 'input[name="ticket-enabled-post-types[]"]', 2 );
	}

	/**
	 * It should allow setting the ticket-able post types
	 *
	 * @test
	 */
	public function should_allow_setting_the_ticket_able_post_types(AcceptanceTester $I) {
		// there are no post-type inputs checked
		$I->seeNumberOfElements( 'input[name="ticket-enabled-post-types[]"]:checked', 0 );

		// what is the `value` attribute of the first?
		$value = $I->grabValueFrom('input[name="ticket-enabled-post-types[]"]:first-of-type');

		// check the first
		$I->checkOption( 'input[name="ticket-enabled-post-types[]"]:first-of-type');

		// save
		$I->click('#tribeSaveSettings');

		// there is now 1 input checked and its the one I clicked
		$I->seeNumberOfElements( 'input[name="ticket-enabled-post-types[]"][value="' . $value . '"]:checked', 1 );
	}
}
