<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveOffsetCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;


	/**
	 * It should allow getting attendees by ID
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_id( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_ids = $I->haveManyPostsInDatabase( 3 );
		// 3 posts, 1 ticket per post, 2 attendees per ticket => 6 attendees
		$attendees = array_reduce( $post_ids, function ( array $attendees, $post_id ) {
			$ticket_id = $this->create_rsvp_ticket( $post_id );
			$attendees = array_merge(
				$attendees,
				$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id )
			);

			return $attendees;
		}, [] );
		$I->haveManyPostsInDatabase(5);

		$I->sendGET( $this->attendees_url, [ 'offset' => 3 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', \array_slice( $attendees, 3 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'offset' => 3 ], $this->attendees_url . '/' ),
			'total'       => 6,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
