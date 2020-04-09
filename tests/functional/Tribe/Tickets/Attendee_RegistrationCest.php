<?


class Attendee_RegistrationCest {

	public function should_not_see_shortcode_in_page( FunctionalTester $I ) {
		$page_id = $I->havePageInDatabase( [
			"post_content" => 'Foo',
		] );

		$I->setTribeOption( "ticket-attendee-page-id", $page_id );

		$page_url             = get_post_permalink( $page_id );
		$shortcode_identifier = "div.tribe-tickets__registration";

		$I->amOnUrl( $page_url );
		$I->dontSeeElement( $shortcode_identifier );
	}

	public function should_see_shortcode_in_page( FunctionalTester $I ) {
		$page_id = $I->havePageInDatabase( [
			"post_content" => '[tribe_attendee_registration]',
		] );

		$I->setTribeOption( "ticket-attendee-page-id", $page_id );

		$page_url             = get_post_permalink( $page_id );
		$shortcode_identifier = "div.tribe-tickets__registration";

		$I->amOnUrl( $page_url );
		$I->seeElement( $shortcode_identifier );
	}

	public function should_see_shortcode_and_content_in_page( FunctionalTester $I ) {
		$before = "Some text before";
		$after  = "Some text after";

		$page_id = $I->havePageInDatabase( [
			"post_content" => "$before [tribe_attendee_registration] $after",
		] );

		$I->setTribeOption( "ticket-attendee-page-id", $page_id );

		$page_url             = get_post_permalink( $page_id );
		$shortcode_identifier = "div.tribe-tickets__registration";

		$I->amOnUrl( $page_url );
		$I->seeElement( $shortcode_identifier );
		$I->see( $before );
		$I->see( $after );
	}

}
