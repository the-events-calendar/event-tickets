<?php

namespace Commerce\RSVP;


use AcceptanceTester;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class FrontEndFormCest {
	use RSVP_Ticket_Maker;

	protected $factory;

	public function _before( AcceptanceTester $I ) {
		// the RSVP_Ticket_Maker will need this property to work
		$this->factory = $I->factory();
		// make sure the `post` post type is ticket-able, use a filter to avoid DB lag time, has to be 5.2 compat
		$code = <<< PHP
add_filter( 'tribe_tickets_post_types', 'test_ticketable_post_types' );
function test_ticketable_post_types(){
	return array( 'post' );
}
PHP;

		$I->haveMuPlugin('ticketable-post-types.php',$code);
	}

	/**
	 * It should show the RSVP form on the frontend of posts that have RSVP tickets
	 *
	 * @test
	 */
	public function should_show_the_rsvp_form_on_the_frontend_of_posts_that_have_rsvp_tickets( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->seeElement( 'form#rsvp-now' );
	}

	/**
	 * It should not show the ticket frontend form if RSVP tickets are not available
	 *
	 * @test
	 */
	public function should_not_show_the_ticket_frontend_form_if_rsvp_tickets_are_not_available( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3, [
			'meta_input' => [
				'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) )
			]
		] );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->dontSeeElement( 'form#rsvp-now' );
	}

	/**
	 * It should show frontend ticket form if post has at least one RSVP ticket available
	 *
	 * @test
	 */
	public function should_show_frontend_ticket_form_if_post_has_at_least_one_rsvp_ticket_available( AcceptanceTester $I ) {
		// Arrange
		$post_id = $I->havePostInDatabase();
		$this->make_ticket( $post_id, 3 );
		$this->make_ticket( $post_id, 3, [
			'meta_input' => [
				'_ticket_end_date' => date( 'Y-m-d H:i:s', strtotime( 'yesterday' ) )
			]
		] );

		// Act
		$I->amOnPage( "/?p={$post_id}" );

		// Assert
		$I->seeElement( 'form#rsvp-now' );
	}
}
