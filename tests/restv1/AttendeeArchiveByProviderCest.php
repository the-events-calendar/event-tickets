<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByProviderCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting attendees by provider
	 *
	 * @test
	 */
	public function should_allow_getting_attendees_by_provider( \Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'editor' );

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
			->where( 'post__in', $all )
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
