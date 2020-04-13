<?php

namespace Tribe\Tickets\Test\REST\V1;

use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class TicketArchiveByAvailabilityCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should getting tickets by availability
	 *
	 * @test
	 */
	public function should_allow_getting_tickets_by_availability( Restv1Tester $I ) {
		// 2 posts, each post 2 tickets w/ capacity and 2 tickets w/o capacity = 8 tickets
		$post_ids     = $I->haveManyPostsInDatabase( 2 );
		$i            = 0;
		$capacities   = [ 1, 5, 10, 23 ];
		// 2 posts, 2 tickets per post = 4 posts
		$available    = array_reduce( $post_ids, function ( array $acc, $post_id ) use ( &$i, $capacities ) {
			$acc[] = $this->create_rsvp_ticket( $post_id, [
				'meta_input' => [
					'_stock'    => $capacities[ $i ],
					'_capacity' => $capacities[ $i ++ ]
				]
			] );
			$acc[] = $this->create_paypal_ticket_basic( $post_id, 2, [
				'meta_input' => [
					'_stock'    => $capacities[ $i ],
					'_capacity' => $capacities[ $i ++ ]
				]
			] );

			return $acc;
		}, [] );
		// 2 posts, 2 tickets per post w/ 0 capacity = 4 tickets w/ 0 capacity
		$not_availble = array_reduce( $post_ids, function ( array $acc, $post_id ) {
			$acc[] = $this->create_rsvp_ticket( $post_id, [ 'meta_input' => [ '_stock' => 0, '_capacity' => 0 ] ] );
			$acc[] = $this->create_paypal_ticket_basic( $post_id, 2, [
				'meta_input' => [
					'_stock'    => 0,
					'_capacity' => 0
				]
			] );

			return $acc;
		}, [] );
		$all          = array_merge( $available, $not_availble );

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

		// filtering by min/max availability should not change the filtering
		// for users that cannot read private posts
		$I->sendGET( $this->tickets_url, [ 'capacity_min' => 10 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', array_merge( $available, $not_availble ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_min' => 10 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 8,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'capacity_max' => 10 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_max' => 10 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 8,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'capacity_min' => 10, 'capacity_max' => 23 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_min' => 10, 'capacity_max' => 23 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 8,
			'tickets'     => $expected_tickets,
		] );

		$I->generate_nonce_for_role( 'administrator' );

		$I->sendGET( $this->tickets_url, [ 'capacity_min' => 10 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', \array_slice( $available, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_min' => 10 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 2,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'capacity_max' => 10 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', array_merge( \array_slice( $available, 0, 3 ), $not_availble ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_max' => 10 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 7,
			'tickets'     => $expected_tickets,
		] );

		$I->sendGET( $this->tickets_url, [ 'capacity_min' => 3, 'capacity_max' => 20 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$expected_tickets = tribe_tickets( 'restv1' )
			->where( 'post__in', \array_slice( $available, 1, 2 ) )
			->all();
		$I->seeResponseContainsJson( [
			'rest_url'    => add_query_arg( [ 'capacity_min' => 3, 'capacity_max' => 20 ], $this->tickets_url . '/' ),
			'total_pages' => 1,
			'total'       => 2,
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
