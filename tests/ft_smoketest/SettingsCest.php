<?php

use FT_Smoketester as Tester;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;

class SettingsCest {
	/**
	 * It should not allow controlling the ticketability of Series
	 *
	 * @test
	 */
	public function should_not_allow_controlling_the_ticketability_of_series( Tester $I ): void {
		$I->loginAsAdmin();

		$I->amOnAdminPage( '/admin.php?page=tec-tickets-settings' );
		$I->seeElement( 'input[value="' . Series_Post_Type::POSTTYPE . '"]' );
	}
}
