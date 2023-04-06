<?php

namespace Admin;

use FT_Smoketester as Tester;

class Series_PassesCest {
	public function _before( Tester $I ) {
		$I->loginAsAdmin();
	}

	/**
	 * It should display form toggle on Series page
	 *
	 * @test
	 */
	public function should_display_form_toggle_on_series_page( Tester $I ): void {
		$series_id = $I->have_series_in_database();
		$I->have_ticketable_series_in_database();

		$I->amEditingPostWithId( $series_id );

		$I->seeElement( '#series_pass_form_toggle' );
	}

	/**
	 * It should not display add ticket on Series page if not ticketable
	 *
	 * @test
	 */
	public function should_not_display_add_ticket_on_series_page_if_not_ticketable( Tester $I ): void {
		$series_id = $I->have_series_in_database();
		$I->have_ticketable_series_in_database( false );

		$I->amEditingPostWithId( $series_id );

		$I->dontSeeElement( '#series_pass_form_toggle' );
	}
}
