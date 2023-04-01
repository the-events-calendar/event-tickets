<?php

use FT_Smoketester as Tester;

class SettingsCest {
	/**
	 * It should allow users to set Series as a Post Type that can have tickets
	 *
	 * @test
	 */
	public function should_allow_setting_series_to_have_tickets( Tester $I ): void {
		$I->loginAsAdmin();

		$I->amOnAdminPage( '/admin.php?page=tec-tickets-settings' );
		$I->seeElement('label[title="Series"]');
		$I->seeElement('input[value="tribe_event_series"]');
	}

}
