<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByIdsCest extends BaseRestCest {
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

		$include_attendees  = \array_slice( $attendees, 2 );
		$expected_attendees = tribe_attendees( 'restv1' )->where( 'post__in', $include_attendees )->all();
		$I->sendGET( $this->attendees_url, [ 'include' => $include_attendees ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'include' => implode( ',', $include_attendees ) ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$exclude_attendees  = \array_splice( $attendees, 0, 2 );
		$I->sendGET( $this->attendees_url, [ 'exclude' => implode( ',', $exclude_attendees ) ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'exclude' => implode( ',', $exclude_attendees ) ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}

	/**
	 * It should return 400 if not all IDs are valid
	 *
	 * @test
	 */
	public function should_return_400_if_not_all_ids_are_valid( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_id     = $I->havePostInDatabase();
		$ticket_id   = $this->create_rsvp_ticket( $post_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $post_id );

		$I->sendGET( $this->attendees_url, [ 'include' => '12,34,foo' ] );
		$I->seeResponseCodeIs( 400 );

		$I->sendGET( $this->attendees_url, [ 'exclude' => [ 23, 89, 'foo' ] ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}
}
