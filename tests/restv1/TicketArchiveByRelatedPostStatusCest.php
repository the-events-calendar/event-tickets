<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByRelatedPostStatusCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting tickets by related post status
	 *
	 * @test
	 */
	public function should_allow_getting_tickets_by_related_post_status( Restv1Tester $I ) {
		$public_post  = $I->havePostInDatabase();
		$public       = $this->create_rsvp_ticket( $public_post );
		$private_post = $I->havePostInDatabase( [ 'post_status' => 'private' ] );
		$private      = $this->create_paypal_ticket_basic( $private_post, 2 );
		$draft_post   = $I->havePostInDatabase( [ 'post_status' => 'draft' ] );
		$draft        = $this->create_rsvp_ticket( $draft_post );
		$future_post  = $I->havePostInDatabase( [
			'post_status' => 'future',
			'post_date'   => date( 'Y-m-d H:i:s', strtotime( '+1week' ) )
		] );
		$future       = $this->create_paypal_ticket_basic( $future_post, 2 );

		// implicitly publish
		$I->sendGET( $this->tickets_url, [ 'post_status' => 'any' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $public ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'any' ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 1,
			'tickets'     => $expected_tickets,
		] );

		// explicitly publish
		$I->sendGET( $this->tickets_url, [ 'post_status' => 'publish' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'publish' ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 1,
			'tickets'     => $expected_tickets,
		] );

		// explicitly private, draft
		$I->sendGET( $this->tickets_url, [ 'post_status' => [ 'private', 'draft' ] ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'private,draft' ], $this->tickets_url . '/' ),
			'total_pages' => 0,
			'total'       => 0,
			'tickets'     => [],
		] );

		$I->generate_nonce_for_role( 'administrator' );

		// implicitly any
		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $public, $private, $draft, $future ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->tickets_url . '/',
			'total_pages' => 1,
			'total'       => 4,
			'tickets'     => $expected_tickets,
		] );

		// explicitly any
		$I->sendGET( $this->tickets_url, [ 'post_status' => 'any' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'any' ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 4,
			'tickets'     => $expected_tickets,
		] );

		// explicitly publish
		$I->sendGET( $this->tickets_url, [ 'post_status' => 'publish' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $public ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'publish' ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 1,
			'tickets'     => $expected_tickets,
		] );

		// explicitly private, draft
		$I->sendGET( $this->tickets_url, [ 'post_status' => 'private,draft' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', [ $private, $draft ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'private,draft' ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 2,
			'tickets'     => $expected_tickets,
		] );
	}
}
