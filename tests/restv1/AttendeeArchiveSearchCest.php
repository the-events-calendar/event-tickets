<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Ticket_Maker;

class AttendeeArchiveSearchCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow searching attendees
	 *
	 * @test
	 */
	public function should_allow_searching_attendees( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$titles    = [
			'foo',
			'foo bar',
			'bar',
			'bar baz'
		];
		$post_id   = $I->havePostInDatabase();
		$ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendees = [
			$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'post_title' => $titles[0] ] ),
			$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'post_title' => $titles[1] ] ),
			$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'post_title' => $titles[2] ] ),
			$this->create_attendee_for_ticket( $ticket_id, $post_id, [ 'post_title' => $titles[3] ] ),
		];

		$I->sendGET( $this->attendees_url, [ 'search' => 'zop' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'zop' ], $this->attendees_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'attendees'   => [],
		] );

		$I->sendGET( $this->attendees_url, [ 'search' => 'foo' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'foo' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => tribe_attendees( 'restv1' )->where( 'post__in', [
				$attendees[0],
				$attendees[1]
			] )->all(),
		] );

		$I->sendGET( $this->attendees_url, [ 'search' => 'bar' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'bar' ], $this->attendees_url . '/' ),
			'total'       => 3,
			'total_pages' => 1,
			'attendees'   => tribe_attendees( 'restv1' )->where( 'post__in', [
				$attendees[1],
				$attendees[2],
				$attendees[3]
			] )->all(),
		] );

		$I->sendGET( $this->attendees_url, [ 'search' => 'foo bar' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'search' => 'foo bar' ], $this->attendees_url . '/' ),
			'total'       => 1,
			'total_pages' => 1,
			'attendees'   => tribe_attendees( 'restv1' )->where( 'post__in', [
				$attendees[1],
			] )->all(),
		] );
	}
}