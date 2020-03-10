<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByRelatedPostCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should return 400 when trying to fetch attendees by bad post_id
	 *
	 * @test
	 */
	public function should_return_400_when_trying_to_fetch_attendees_by_bad_post_id( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$rsvp_post_id   = $I->havePostInDatabase();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );

		$I->sendGET( $this->attendees_url, [ 'post_id' => 23424 ] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return 400 when trying to fetch attendees by bad ticket_id
	 *
	 * @test
	 */
	public function should_return_400_when_trying_to_fetch_attendees_by_bad_ticket_id( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$rsvp_post_id   = $I->havePostInDatabase();
		$rsvp_ticket_id = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );

		$I->sendGET( $this->attendees_url, [ 'ticket_id' => 23424 ] );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should allow getting attendees by related post_id
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_related_post_id( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket_basic( $paypal_post_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $paypal_post_id );
		$rsvp_post_id     = $I->havePostInDatabase();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );
		$all              = array_merge( $paypal_attendees, $rsvp_attendees );
		sort( $all );

		$I->sendGET( $this->attendees_url, [ 'post_id' => $paypal_post_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->by( 'post__in', $paypal_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'post_id' => $paypal_post_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'post_id' => $rsvp_post_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $rsvp_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'post_id' => $rsvp_post_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting attendees by ticket_id
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_ticket_id( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket_basic( $paypal_post_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $paypal_post_id );
		$rsvp_post_id     = $I->havePostInDatabase();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );
		$all              = array_merge( $paypal_attendees, $rsvp_attendees );
		sort( $all );

		$I->sendGET( $this->attendees_url, [ 'ticket_id' => $paypal_ticket_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->by( 'post__in', $paypal_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'ticket_id' => $paypal_ticket_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'ticket_id' => $rsvp_ticket_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $rsvp_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'ticket_id' => $rsvp_ticket_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting attendees by a list of related post_ids
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_a_list_of_related_post_ids( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket_basic( $paypal_post_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $paypal_post_id );
		$rsvp_post_id     = $I->havePostInDatabase();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );
		$all              = array_merge( $paypal_attendees, $rsvp_attendees );
		sort( $all );

		$I->sendGET( $this->attendees_url, [ 'include_post' => implode( ',', [ $paypal_post_id, $rsvp_post_id ] ) ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->by( 'post__in', $all )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [
				'include_post' => implode( ',', [
					$paypal_post_id,
					$rsvp_post_id
				] ),
			], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'include_post' => $rsvp_post_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $rsvp_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'include_post' => $rsvp_post_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting attendees by a list of tickets
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_a_list_of_tickets( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket_basic( $paypal_post_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $paypal_post_id );
		$rsvp_post_id     = $I->havePostInDatabase();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );
		$all              = array_merge( $paypal_attendees, $rsvp_attendees );
		sort( $all );

		$I->sendGET( $this->attendees_url, [
			'include_ticket' => implode( ',', [
				$paypal_ticket_id,
				$rsvp_ticket_id
			] )
		] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->by( 'post__in', $all )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [
				'include_ticket' => implode( ',', [
					$paypal_ticket_id,
					$rsvp_ticket_id
				] )
			], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'include_ticket' => $rsvp_ticket_id ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $rsvp_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'include_ticket' => $rsvp_ticket_id ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting attendees excluding related post_ids
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_excluding_related_post_ids( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_ids  = $I->haveManyPostsInDatabase( 3 );
		$attendees = array_reduce( $post_ids, function ( array $acc, $post_id ) {
			$ticket_id       = $this->create_rsvp_ticket( $post_id );
			$acc[ $post_id ] = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

			return $acc;
		}, [] );

		$I->sendGET( $this->attendees_url, [ 'exclude_post' => $post_ids[0] ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', array_merge( $attendees[ $post_ids[1] ], $attendees[ $post_ids[2] ] ) )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'exclude_post' => $post_ids[0] ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'exclude_post' => [ $post_ids[0], $post_ids[1] ] ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $attendees[ $post_ids[2] ] )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [
				'exclude_post' => implode( ',', [ $post_ids[0], $post_ids[1] ] ),
			], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}

	/**
	 * It should allow getting attendees excluding ticket_ids
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_excluding_ticket_ids( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

		$post_ids   = $I->haveManyPostsInDatabase( 3 );
		$attendees  = array_reduce( $post_ids, function ( $acc, $post_id ) {
			$ticket_id = $this->create_rsvp_ticket( $post_id );

			$acc[ $ticket_id ] = $this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id );

			return $acc;
		}, [] );
		$ticket_ids = array_keys( $attendees );

		$I->sendGET( $this->attendees_url, [ 'exclude_ticket' => $ticket_ids[0] ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', array_merge( $attendees[ $ticket_ids[1] ], $attendees[ $ticket_ids[2] ] ) )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'exclude_ticket' => $ticket_ids[0] ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'exclude_ticket' => [ $ticket_ids[0], $ticket_ids[1] ] ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )

			->where( 'post__in', $attendees[ $ticket_ids[2] ] )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [
				'exclude_ticket' => implode( ',', [ $ticket_ids[0], $ticket_ids[1] ] ),
			], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}
}
