<?php

namespace Tribe\Tickets\Test\REST\V1;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Ticket_Maker;

class AttendeeArchiveCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should getting all attendees
	 *
	 * @test
	 */
	public function should_getting_all_attendees( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );
		// 2 posts, 2 tickets per post, 2 attendees per ticket => 4 tickets, 8 attendees
		$attendees_and_tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ]['tickets']   = $ticket_ids = $this->create_many_rsvp_tickets( 2, $post_id );
			$acc[ $post_id ]['attendees'] = array_map( function ( int $ticket_id ) use ( $post_id ) {
				return $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id, [
					'rsvp_status' => 'yes',
					'optout'      => false,
				] );
			}, $ticket_ids );

			return $acc;
		}, [] );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 4 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4 ], $this->attendees_url . '/' ),
			'total'       => 8,
			'total_pages' => 2,
			'attendees'   => tribe_attendees( 'restv1' )->fetch()->per_page( 4 )->page( 1 )->all(),
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 8 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 2 );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 2 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4, 'page' => 2 ], $this->attendees_url . '/' ),
			'total'       => 8,
			'total_pages' => 2,
			'attendees'   => tribe_attendees( 'restv1' )->fetch()->per_page( 4 )->page( 2 )->all(),
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 8 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 2 );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 3 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return empty array if no attendees are found
	 *
	 * @test
	 */
	public function should_return_empty_array_if_no_attendees_are_found( Restv1Tester $I ) {
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 0,
			'total_pages' => 0,
			'attendees'   => [],
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 0 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 0 );

		$I->sendGET( $this->attendees_url, [ 'page' => 2 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should show private attendees to users that can read private posts
	 *
	 * @test
	 */
	public function should_show_private_attendees_to_users_that_can_read_private_posts( Restv1Tester $I ) {
		$post_ids = $I->haveManyPostsInDatabase( 2 );
		/**
		 * 2 posts, 2 tickets per post, 4 attendees per ticket:
		 *  - 1 attendee is going and did not opt out; will show to public
		 *  - 1 attendee is going and did opt out; will not show to public
		 *  - 1 attendee is not going and did not opt out; will not show to public
		 *  - 1 attendee is not going and did opt out; will not show to public
		 * Total: 2 posts, 4 tickets, 16 attendees
		 *
		 * Users that cannot read private posts will only see the going attendees
		 * that did not opt out.
		 */
		$attendees_and_tickets = array_reduce( $post_ids, function ( array $acc, int $post_id ) {
			$acc[ $post_id ]['tickets'] = $ticket_ids = $this->create_many_rsvp_tickets( 2, $post_id );
			$attendee_acc               = [];
			foreach ( $ticket_ids as $ticket_id ) {
				// going and did not opt out
				$publicly_visible_attendee           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
					'rsvp_status' => 'yes',
					'optout'      => false
				] );
				$acc['publicly_visible_attendees'][] = $publicly_visible_attendee;
				$attendee_acc[]                      = [
					$publicly_visible_attendee,
					// going and did opt out
					$this->create_attendee_for_ticket( $ticket_id, $post_id, [
						'rsvp_status' => 'yes',
						'optout'      => 'yes'
					] ),
					// not going and did not opt out
					$this->create_attendee_for_ticket( $ticket_id, $post_id, [
						'rsvp_status' => 'no',
						'optout'      => false
					] ),
					// not going and did opt out
					$this->create_attendee_for_ticket( $ticket_id, $post_id, [
						'rsvp_status' => 'no',
						'optout'      => 'yes'
					] ),
				];
			}
			$acc[ $post_id ]['attendees'] = array_merge( ...$attendee_acc );

			return $acc;
		}, [] );

		$expected_attendees = tribe_attendees( 'restv1' )
			->fetch()
			->where( 'post__in', $attendees_and_tickets['publicly_visible_attendees'] )
			->order_by( 'post__in' )
			->all();
		$I->sendGET( $this->attendees_url, [ 'per_page' => 4 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4 ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 4 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 1 );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 2 ] );
		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$I->generate_nonce_for_role( 'editor' );

		$expected_attendees = tribe_attendees( 'restv1' )
			->fetch()
			->per_page( 4 )
			->all();
		$I->sendGET( $this->attendees_url, [ 'per_page' => 4 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4 ], $this->attendees_url . '/' ),
			'total'       => 16,
			'total_pages' => 4,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 16 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 4 );

		$expected_attendees = tribe_attendees( 'restv1' )
			->fetch()
			->per_page( 4 )
			->page( 2 )
			->all();
		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 2 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4, 'page' => 2 ], $this->attendees_url . '/' ),
			'total'       => 16,
			'total_pages' => 4,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 16 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 4 );
	}
}
