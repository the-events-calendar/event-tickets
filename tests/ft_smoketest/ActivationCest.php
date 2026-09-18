<?php

use FT_Smoketester as Tester;

class ActivationCest {
	public function _before( Tester $I ): void {
		/*
		 * The dump carries the `db_version` of whatever WordPress it was taken from; on any other
		 * version admin requests redirect to the database upgrade screen instead of the plugins page.
		 */
		require $_ENV['WP_ROOT_FOLDER'] . '/wp-includes/version.php';
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

		/*
		 * Not `$I->deactivatePlugin()` / `$I->activatePlugin()`: WordPress 7.1 moved the plugin row
		 * checkbox from a `th` to a `td` and wp-browser 3.x only looks for it in the `th`.
		 */
		$I->checkOption( '//*[@data-slug="event-tickets"]//input[@type="checkbox"]' );
		$I->selectOption( 'action', 'deactivate-selected' );
		$I->click( '#doaction' );
		$I->amOnPluginsPage(); // Back to the plugins page to deal with redirection after deactivation.
		$I->seePluginDeactivated( 'event-tickets' );

		$I->checkOption( '//*[@data-slug="event-tickets"]//input[@type="checkbox"]' );
		$I->selectOption( 'action', 'activate-selected' );
		$I->click( '#doaction' );
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
