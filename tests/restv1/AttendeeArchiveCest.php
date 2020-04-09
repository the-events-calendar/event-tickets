<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Ticket_Maker;

class AttendeeArchiveCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting all attendees
	 *
	 * @test
	 */
	public function should_allow_getting_all_attendees( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );
		$I->haveMuPlugin( 'test-attendees.php', $code );
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
			'attendees'   => tribe_attendees( 'restv1' )->per_page( 4 )->page( 1 )->all(),
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
			'attendees'   => tribe_attendees( 'restv1' )->per_page( 4 )->page( 2 )->all(),
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
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );
		$I->haveMuPlugin( 'test-attendees.php', $code );

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
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/test-attendees.php' ) );
		$I->haveMuPlugin( 'test-attendees.php', $code );
		// 2 posts, 1 ticket per post, 4 attendees per ticket = 8 attendees (2 public, 6 private)
		$post_ids  = $I->haveManyPostsInDatabase( 2 );
		$public    = [];
		$private   = [];
		$attendees = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$public, &$private ) {
			$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
			// goind and did not opt out
			$acc[] = $public[] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
				'rsvp_status' => 'yes',
				'optout'      => false
			] );
			// going and did opt out
			$acc[] = $private[] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
				'rsvp_status' => 'yes',
				'optout'      => 'yes'
			] );
			// not going and did not opt out
			$acc[] = $private[] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
				'rsvp_status' => 'no',
				'optout'      => false
			] );
			// not going and did opt out
			$acc[] = $private[] = $this->create_attendee_for_ticket( $rsvp_ticket_id, $post_id, [
				'rsvp_status' => 'no',
				'optout'      => 'yes'
			] );

			return $acc;
		}, [] );

//		$I->sendGET( $this->attendees_url );
//		$I->seeResponseCodeIs( 200 );
//		$I->seeResponseIsJson();
//		$expected_attendees = tribe_attendees( 'restv1' )
//			->where( 'post__in', $public )
//			->order_by( 'post__in' )
//			->all();
//		$I->assertEquals( [
//			'rest_url'    => $this->attendees_url . '/',
//			'total'       => 2,
//			'total_pages' => 1,
//			'attendees'   => $expected_attendees,
//		], json_decode( $I->grabResponse(), true ) );
//		$I->seeHttpHeader( 'X-ET-TOTAL', 2 );
//		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 1 );
//
//		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 2 ] );
//		$I->seeResponseCodeIs( 400 );
//		$I->seeResponseIsJson();

		$I->generate_nonce_for_role( 'editor' );

		$I->sendGET( $this->attendees_url, [ 'per_page' => 4 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', \array_slice( $attendees, 0, 4 ) )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg(['per_page'=>4],$this->attendees_url . '/'),
			'total'       => 8,
			'total_pages' => 2,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 8 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 2 );

		$expected_attendees = tribe_attendees( 'restv1' )
			->where('post__in', \array_slice($attendees,4))
			->all();
		$I->sendGET( $this->attendees_url, [ 'per_page' => 4, 'page' => 2 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'per_page' => 4, 'page' => 2 ], $this->attendees_url . '/' ),
			'total'       => 8,
			'total_pages' => 2,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
		$I->seeHttpHeader( 'X-ET-TOTAL', 8 );
		$I->seeHttpHeader( 'X-ET-TOTAL-PAGES', 2 );
	}

	/**
	 * It should return empty array if no attendees are found
	 *
	 * @test
	 */
	public function should_return_error_if_et_plus_inactive( Restv1Tester $I ) {
		$code = file_get_contents( codecept_data_dir( 'REST/V1/mu-plugins/disable-etplus.php' ) );
		$I->haveMuPlugin( 'disable-etplus.php', $code );

		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 401 );
	}
}
