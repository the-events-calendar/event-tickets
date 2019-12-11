<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByPriceCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by include price
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_include_price( \Restv1Tester $I ) {
		$post_id            = $I->havePostInDatabase( [ 'meta' => [ '_tribe_hide_attendees_list' => 1 ] ] );
		$rsvp_ticket_id     = $this->create_rsvp_ticket( $post_id );
		$paypal_ticket_id_1 = $this->create_paypal_ticket( 2, 3 );
		$paypal_ticket_id_2 = $this->create_paypal_ticket( 2, 5 );

		$rsvp_ticket_attendees = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket_id, $post_id );
		$pp_ticket_1_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id_1, $post_id );
		$pp_ticket_2_attendees = $this->create_many_attendees_for_ticket( 2, $paypal_ticket_id_2, $post_id );


		$I->sendGET( $this->attendees_url, [ 'price_min' => 3 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $pp_ticket_1_attendees, $pp_ticket_2_attendees ) )
			->order_by( 'post__in' )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'price_min' => 3 ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'price_max' => 3 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $rsvp_ticket_attendees, $pp_ticket_1_attendees ) )
			->order_by( 'post__in' )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'price_max' => 3 ], $this->attendees_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'price_min' => 1, 'price_max' => 3 ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', array_merge( $pp_ticket_1_attendees ) )
			->order_by( 'post__in' )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'price_min' => 1, 'price_max' => 3 ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
