<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByDateCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by date
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_date( \Restv1Tester $I ) {
		$post_ids  = $I->haveManyPostsInDatabase( 3 );
		$attendees = array_reduce( $post_ids, function ( array $attendees, $post_id ) {
			$ticket_id = $this->create_rsvp_ticket( $post_id );
			$attendees = array_merge(
				$attendees,
				$this->create_many_attendees_for_ticket( 2, $ticket_id, $post_id )
			);

			return $attendees;
		}, [] );

		// distance attendees to be 1 day apart
		$date = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

		$one_day_apart = function () use ( &$date ) {
			/** @var \DateTime $date */
			$date->add( new \DateInterval( 'P1D' ) );

			return $date->format( 'Y-m-d H:i:s' );
		};

		tribe_attendees()
			->where( 'post__in', $attendees )
			->set( 'post_date_gmt', $one_day_apart )
			->save();
	}

	/**
	 * It should return 400 when trying to get attendees by bad date
	 *
	 * @test
	 */
	public function should_return_400_when_trying_to_get_attendees_by_bad_date( \Restv1Tester $I ) {

	}

	/**
	 * It should allow getting attendees by provider
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_provider( \Restv1Tester $I ) {
		$paypal_post_id   = $I->havePostInDatabase();
		$paypal_ticket_id = $this->create_paypal_ticket( $paypal_post_id, 2 );
		$paypal_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id, $paypal_post_id );
		$rsvp_post_id     = $I->havePostInDatabase();
		$rsvp_ticket_id   = $this->create_rsvp_ticket( $rsvp_post_id );
		$rsvp_attendees   = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $rsvp_post_id );
		$all              = array_merge( $paypal_attendees, $rsvp_attendees );
		sort( $all );

		$I->sendGET( $this->attendees_url, [] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->by( 'post__in', $all )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'provider' => 'rsvp' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $rsvp_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'provider' => 'rsvp' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );

		$I->sendGET( $this->attendees_url, [ 'provider' => 'tribe-commerce' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $paypal_attendees )
			->order_by( 'post__in' )
			->all();
		$I->assertEquals( [
			'rest_url'    => add_query_arg( [ 'provider' => 'tribe-commerce' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		], json_decode( $I->grabResponse(), true ) );
	}
}
