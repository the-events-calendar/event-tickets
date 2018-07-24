<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\REST\V1\BaseRestCest;

class TicketArchiveByAvailabilityCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow offsetting the ticket results
	 *
	 * @test
	 */
	public function should_allow_offsetting_the_ticket_results( Restv1Tester $I ) {
		$post_ids     = $I->haveManyPostsInDatabase( 2 );
		$available = array_reduce( $post_ids, function ( array $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_stock' => 10, '_capacity' => 10 ] ] );
			$acc[] = $this->create_paypal_ticket( $post_id, 2, [
				'meta_input' => [
					'_stock' => 10,
					'_capacity' => 10
				]
			] );

			return $acc;
		}, [] );
		$not_availble = array_reduce( $post_ids, function ( array $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_stock' => 0, '_capacity' => 0 ] ] );
			$acc[] = $this->create_paypal_ticket( $post_id, 2, [ 'meta_input' => [ '_stock' => 0, '_capacity' => 0 ] ] );

			return $acc;
		}, [] );

		// no availability specified
		$I->sendGET( $this->tickets_url );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', array_merge( $available, $not_availble ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => $this->tickets_url . '/',
			'total_pages' => 1,
			'total'       => 8,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'is_available' => true ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', $available )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'is_available' => true ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'is_available' => false ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', $not_availble )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'is_available' => false ], $this->tickets_url . '/' ),
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		] );
	}

	/**
	 * It should return 400 when passing invalid available values
	 *
	 * @test
	 */
	public function should_return_400_when_passing_invalid_offset_values( Restv1Tester $I ) {
		$I->sendGET( $this->tickets_url, [ 'is_available' => 'foo' ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 400 );
	}
}
