<?php

use FT_Smoketester as Tester;

class ActivationCest {
	/**
	 * Stores the running WordPress's database version, as an upgrade would.
	 *
	 * The dump comes from an older WordPress: past the first admin page, an older version sends every admin page
	 * to the "Database Update Required" screen. CI runs several WordPress versions, so the dump can't hold the one.
	 */
	public function _before( Tester $I ): void {
		global $wp_db_version;

		$I->haveOptionInDatabase( 'db_version', $wp_db_version );
	}

	/**
	 * It should activate deactivate correctly
	 *
	 * @test
	 */
	public function should_activate_deactivate_correctly( Tester $I ): void {
		$I->loginAsAdmin();

		$I->amOnPluginsPage( '' );
		$I->seePluginActivated( 'event-tickets' );

		$I->deactivatePlugin( 'event-tickets' );
		$I->amOnPluginsPage(); // Back to the plugins page to deal with redirection after deactivation.
		$I->seePluginDeactivated( 'event-tickets' );

		$I->activatePlugin( 'event-tickets' );
		$I->amOnPluginsPage(); // Back to plugins page to deal with redirection after activation.
		$I->amOnPluginsPage(); // And back again to deal with the second redirection after activation.
		$I->seePluginActivated( 'event-tickets' );
	}

	/**
	 * It should have TEC CT1 activated
	 *
	 * @test
	 */
	public function should_have_tec_ct_1_activated( Tester $I ): void {
		$I->amOnPage( '/' );

		$I->assert_data_key( 'tec_flexible_tickets', true );
	}
}
