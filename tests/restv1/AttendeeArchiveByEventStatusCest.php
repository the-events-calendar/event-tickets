<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByEventStatusCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;


	/**
	 * It should allow filtering attendees by related post status
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_related_post_status( \Restv1Tester $I ) {
		$public         = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'publish' ] );
		$private        = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'private' ] );
		$draft          = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'draft' ] );
		$i              = 0;
		$attendee_maker = function ( $acc, $post_id ) use ( &$i ) {
			$ticket_id = $i ++ % 2 === 0
				? $this->create_rsvp_ticket( $post_id )
				: $this->create_paypal_ticket( $post_id, 3 );
			$acc       = array_merge( $acc, $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id ) );

			return $acc;
		};
		// 6 posts, for each post 1 ticket (RSVP or PayPal), for each ticket 2 attendees
		// 4 public, 4 private, 4 drafts
		$public_attendees  = array_reduce( $public, $attendee_maker, [] );
		$private_attendees = array_reduce( $private, $attendee_maker, [] );
		$draft_attendees   = array_reduce( $draft, $attendee_maker, [] );

		/**
		 * Implicit post status => public
		 */
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $public_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Explicit post status
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'publish' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'publish' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Not a user that can read private posts
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'private' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'private' ], $this->attendees_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'attendees'   => [],
		] );
	}

	/**
	 * It should allow users that can read private posts to read attendees from any post status
	 *
	 * @test
	 */
	public function should_allow_users_that_can_read_private_posts_to_read_attendees_from_any_post_status( \Restv1Tester $I ) {
		$public         = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'publish' ] );
		$private        = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'private' ] );
		$draft          = $I->haveManyPostsInDatabase( 2, [ 'post_status' => 'draft' ] );
		$i              = 0;
		$attendee_maker = function ( $acc, $post_id ) use ( &$i ) {
			$ticket_id = $i ++ % 2 === 0
				? $this->create_rsvp_ticket( $post_id )
				: $this->create_paypal_ticket( $post_id, 3 );
			$acc       = array_merge( $acc, $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id ) );

			return $acc;
		};
		// 6 posts, for each post 1 ticket (RSVP or PayPal), for each ticket 2 attendees
		// 4 public, 4 private, 4 drafts
		$public_attendees  = array_reduce( $public, $attendee_maker, [] );
		$private_attendees = array_reduce( $private, $attendee_maker, [] );
		$draft_attendees   = array_reduce( $draft, $attendee_maker, [] );
		$I->haveOptionInDatabase( 'posts_per_page', 20 );

		$I->generate_nonce_for_role( 'editor' );

		/**
		 * Implicit post status => any
		 */
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $public_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 12,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Explicit post status
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'publish' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'publish' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Now we can read private posts
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'private' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $private_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'private' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Now we can read private posts
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'draft' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $draft_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'draft' ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Now we can read private posts
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => 'draft,private' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $private_attendees, $draft_attendees ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'post_status' => 'draft,private' ], $this->attendees_url . '/' ),
			'total'       => 8,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		/**
		 * Now we can read private posts
		 */
		$I->sendGET( $this->attendees_url, [ 'post_status' => [ 'draft', 'private', 'publish' ], 'per_page' => 20 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $public_attendees, $private_attendees, $draft_attendees ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [
				'post_status' => 'draft,private,publish',
				'per_page'    => 20
			], $this->attendees_url . '/' ),
			'total'       => 12,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
