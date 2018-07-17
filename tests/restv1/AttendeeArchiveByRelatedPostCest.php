<?php

namespace Tribe\Tickets\Test\REST\V1;

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
		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket( $paypal_post_id, 2 );
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
			->fetch()
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
			->fetch()
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
		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket( $paypal_post_id, 2 );
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
			->fetch()
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
			->fetch()
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
}
