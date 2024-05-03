<?php

namespace slr_smoketest;
use SmoketestTester;

class Activation_Deactivation_Cest {
	public function _before( SmoketestTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
	}

	public function should_activate_deactivate_correctly( SmoketestTester $I ) {
		$I->seePluginActivated( 'events-assigned-seating' );

		$I->deactivatePlugin( 'events-assigned-seating' );

		$I->dontSeeElement( 'body#error-page' ); // wp-die page
		$I->seeElement( 'div#message.notice.updated' );
		$I->seePluginDeactivated( 'events-assigned-seating' );

		$I->activatePlugin( 'events-assigned-seating' );

		$I->dontSeeElement( 'body#error-page' ); // wp-die page
		$I->seeElement( 'div#message.notice.updated' );
		$I->seePluginActivated( 'events-assigned-seating' );
	}
}
