<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class AttendeeArchiveByOrderStatusCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow filtering attendees by order status
	 *
	 * @test
	 */
	public function should_allow_filtering_attendees_by_order_status( \Restv1Tester $I ) {
		$rsvp_post                     = $I->havePostInDatabase();
		$rsvp_ticket                   = $this->create_rsvp_ticket( $rsvp_post );
		$rsvp_yes_attendee             = $this->create_attendee_for_ticket( $rsvp_ticket, $rsvp_post, [ 'rsvp_status' => 'yes' ] );
		$rsvp_no_attendee              = $this->create_attendee_for_ticket( $rsvp_ticket, $rsvp_post, [ 'rsvp_status' => 'no' ] );
		$paypal_post                   = $I->havePostInDatabase();
		$paypal_ticket                 = $this->create_paypal_ticket( $paypal_post, 2 );
		$paypal_completed_attendee     = $this->create_attendee_for_ticket( $paypal_ticket, $paypal_post, [ '_tribe_tpp_status' => 'completed' ] );
		$paypal_not_completed_attendee = $this->create_attendee_for_ticket( $paypal_ticket, $paypal_post, [ '_tribe_tpp_status' => 'not-completed' ] );
		$paypal_pending_attendee       = $this->create_attendee_for_ticket( $paypal_ticket, $paypal_post, [ '_tribe_tpp_status' => 'pending-payment' ] );
		$paypal_refunded_attendee      = $this->create_attendee_for_ticket( $paypal_ticket, $paypal_post, [ '_tribe_tpp_status' => 'refunded' ] );
		$paypal_denied_attendee        = $this->create_attendee_for_ticket( $paypal_ticket, $paypal_post, [ '_tribe_tpp_status' => 'denied' ] );

		$public_attendees  = [
			$rsvp_yes_attendee,
			$paypal_completed_attendee,
		];
		$all_attendees     = [
			$rsvp_yes_attendee,
			$rsvp_no_attendee,
			$paypal_completed_attendee,
			$paypal_not_completed_attendee,
			$paypal_pending_attendee,
			$paypal_refunded_attendee,
			$paypal_denied_attendee,
		];
		$private_attendees = array_diff( $all_attendees, $public_attendees );

		// Implicit `public` order status
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $public_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		// Explicit `public` order status
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'public' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $public_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'public' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		// Private status as a no-one
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'private' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'private' ], $this->attendees_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'attendees'   => [],
		] );

		// More private order stati as a no-one
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'not-completed' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'not-completed' ], $this->attendees_url . '/' ),
			'total'       => 0,
			'total_pages' => 0,
			'attendees'   => [],
		] );

		$I->generate_nonce_for_role( 'editor' );

		// Implicit `any` order status
		$I->sendGET( $this->attendees_url );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $all_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->attendees_url . '/',
			'total'       => count( $all_attendees ),
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		// Explicit `public` order status
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'public' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $public_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'public' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		// Private status
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'private' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', $private_attendees )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'private' ], $this->attendees_url . '/' ),
			'total'       => count( $private_attendees ),
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		// More private order stati
		$I->sendGET( $this->attendees_url, [ 'order_status' => 'not-completed' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', [ $paypal_not_completed_attendee ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'not-completed' ], $this->attendees_url . '/' ),
			'total'       => 1,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'order_status' => 'not-completed,denied' ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', [ $paypal_not_completed_attendee, $paypal_denied_attendee ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'not-completed,denied' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );

		$I->sendGET( $this->attendees_url, [ 'order_status' => [ 'completed', 'refunded' ] ] );
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$expected_attendees = tribe_attendees( 'restv1' )
			->where( 'post__in', [ $paypal_completed_attendee, $paypal_refunded_attendee ] )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'order_status' => 'completed,refunded' ], $this->attendees_url . '/' ),
			'total'       => 2,
			'total_pages' => 1,
			'attendees'   => $expected_attendees,
		] );
	}
}
