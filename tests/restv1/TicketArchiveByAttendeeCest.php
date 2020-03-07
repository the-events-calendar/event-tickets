<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByAttendeeCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow filtering tickets by min/max number of attendees
	 *
	 * @test
	 */
	public function should_allow_filtering_tickets_by_min_max_number_of_attendees( Restv1Tester $I ) {
		// 3 posts, 2 tickets per post, varying number of attendees
		$post_ids         = $I->haveManyPostsInDatabase( 3 );
		$attendees_counts = [ 1, 2, 3, 4, 5, 6 ];
		$i                = 0;
		$tickets          = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$i, $attendees_counts ) {
			$acc[] = $rsvp_ticket = $this->create_rsvp_ticket( $post_id );
			$acc[] = $paypal_ticket = $this->create_paypal_ticket( $post_id, 2 );
			$this->create_many_attendees_for_ticket( $attendees_counts[ $i ++ ], $rsvp_ticket, $post_id );
			$this->create_many_attendees_for_ticket( $attendees_counts[ $i ++ ], $paypal_ticket, $post_id );

			return $acc;
		}, [] );

		// as a not logged in user the filter has no effect
		$I->sendGET( $this->tickets_url, [ 'attendees_min' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $tickets )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendees_min' => 3 ], $this->tickets_url . '/' ),
			'total'       => 6,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// as a not logged in user the filter has no effect
		$I->sendGET( $this->tickets_url, [ 'attendees_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendees_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 6,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->generate_nonce_for_role( 'editor' );

		$I->sendGET( $this->tickets_url, [ 'attendees_min' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendees_min' => 3 ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'attendees_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 0, 3 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendees_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'attendees_min' => 2, 'attendees_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 1, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'attendees_min' => 2, 'attendees_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
	}

	/**
	 * It should allow filtering tickets by checkedin quantity
	 *
	 * @test
	 */
	public function should_allow_filtering_tickets_by_checkedin_quantity(Restv1Tester $I) {
		// 3 posts, 2 tickets per post, varying number of attendees
		$post_ids                   = $I->haveManyPostsInDatabase( 3 );
		$checked_in_attendees_count = [ 1, 2, 3, 4, 5, 6 ];
		$i = 0;
		$tickets = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$i, $checked_in_attendees_count ) {
			$acc[] = $rsvp_ticket = $this->create_rsvp_ticket( $post_id );
			$acc[] = $paypal_ticket = $this->create_paypal_ticket( $post_id, 2 );
			// create checked-in RSVP attendees
			$this->create_many_attendees_for_ticket( $checked_in_attendees_count[ $i ++ ], $rsvp_ticket, $post_id, [ 'checkin' => 1 ] );
			// create not checked-in RSVP attendees
			$this->create_many_attendees_for_ticket( 2, $rsvp_ticket, $post_id, [ 'checkin' => 0 ] );
			// create checked-in PayPal attendees
			$this->create_many_attendees_for_ticket( $checked_in_attendees_count[ $i ++ ], $paypal_ticket, $post_id, [ 'checkin' => 1 ] );
			// create not checked-in PayPal attendees
			$this->create_many_attendees_for_ticket( 2, $paypal_ticket, $post_id, [ 'checkin' => 0 ] );

			return $acc;
		}, [] );

		// as a not logged in user the filter has no effect
		$I->sendGET( $this->tickets_url, [ 'checkedin_min' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( $tickets )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin_min' => 3 ], $this->tickets_url . '/' ),
			'total'       => 6,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		// as a not logged in user the filter has no effect
		$I->sendGET( $this->tickets_url, [ 'checkedin_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 6,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->generate_nonce_for_role( 'editor' );

		$I->sendGET( $this->tickets_url, [ 'checkedin_min' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin_min' => 3 ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'checkedin_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 0, 3 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'checkedin_min' => 2, 'checkedin_max' => 3 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->in( \array_slice( $tickets, 1, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'checkedin_min' => 2, 'checkedin_max' => 3 ], $this->tickets_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
	}
}
