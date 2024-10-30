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
	 * It should not display add ticket button on Series page.
	 *
	 * @test
	 */
	public function should_not_display_new_ticket_button_on_series_page( Tester $I ): void {
		$series_id = $I->have_series_in_database();
		$I->have_ticketable_series_in_database();

		$I->amEditingPostWithId( $series_id );

		$I->dontSeeElement( '#ticket_form_toggle' );
	}

	/**
	 * It should not display add RSVP button on Series page.
	 *
	 * @test
	 */
	public function should_not_display_new_rsvp_button_on_series_page( Tester $I ): void {
		$series_id = $I->have_series_in_database();
		$I->have_ticketable_series_in_database();

		$I->amEditingPostWithId( $series_id );

		$I->dontSeeElement( '#rsvp_form_toggle' );
	}
}
