<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Codeception\Example;
use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class TicketArchiveByPostCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting all the tickets for an event
	 *
	 * @test
	 */
	public function should_allow_getting_all_the_tickets_for_an_event( Restv1Tester $I ) {
		$post_id    = $I->havePostInDatabase();
		$ticket_ids = $this->create_many_paypal_tickets_basic( 3, $post_id );
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_id ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$response         = json_decode( $I->grabResponse(), true );
		$expected_tickets = array_map( function ( $ticket_id ) use ( $repository ) {
			return $repository->get_ticket_data( $ticket_id );
		}, $ticket_ids );

		$expected_rest_url = add_query_arg( [
			'include_post' => $post_id
		], $this->tickets_url . '/' );

		$I->assertEquals( [
			'rest_url'    => $expected_rest_url,
			'total'       => 3,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		], $response );
	}

	/**
	 * It should return 400 if the `include_post` param is invalid
	 *
	 * @test
	 *
	 * @dataProvider invalid_include_post
	 */
	public function should_return_400_if_the_include_post_param_is_invalid( Restv1Tester $I, Example $example ) {
		$params = [ 'include_post' => $example[0] ];

		$post_id    = $I->havePostInDatabase();
		$ticket_ids = $this->create_many_paypal_tickets_basic( 1, $post_id );
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $this->tickets_url, $params );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 400 );
	}

	/**
	 * It should return only tickets accessible by the user
	 *
	 * @test
	 */
	public function should_return_only_tickets_accessible_by_the_user( Restv1Tester $I ) {
		$post_id           = $I->havePostInDatabase();
		$public_ticket_ids = $this->create_many_paypal_tickets_basic( 2, $post_id );
		$draft_ticket_ids  = $this->create_many_paypal_tickets_basic( 2, $post_id, [ 'post_status' => 'draft' ] );
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_id ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$response         = json_decode( $I->grabResponse(), true );
		$expected_tickets = array_map( function ( $ticket_id ) use ( $repository ) {
			return $repository->get_ticket_data( $ticket_id );
		}, $public_ticket_ids );

		$expected_rest_url = add_query_arg( [
			'include_post' => $post_id
		], $this->tickets_url . '/' );

		$I->assertEquals( [
			'rest_url'    => $expected_rest_url,
			'total'       => 2,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		], $response );

		$I->generate_nonce_for_role( 'administrator' );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_id ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$response         = json_decode( $I->grabResponse(), true );
		$expected_tickets = array_map( function ( $ticket_id ) use ( $repository ) {
			return $repository->get_ticket_data( $ticket_id );
		}, array_merge( $public_ticket_ids, $draft_ticket_ids ) );

		$expected_rest_url = add_query_arg( [
			'include_post' => $post_id
		], $this->tickets_url . '/' );

		$I->assertEquals( [
			'rest_url'    => $expected_rest_url,
			'total'       => 4,
			'total_pages' => 1,
			'tickets'     => $expected_tickets,
		], $response );
	}

	/**
	 * It should allow getting paginated results
	 *
	 * @test
	 */
	public function should_allow_getting_paginated_results(Restv1Tester $I) {
		$post_id = $I->havePostInDatabase();
		$ticket_ids = $this->create_many_paypal_tickets_basic( 4, $post_id );
		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );
		$page_1_tickets = array_map( function ( $ticket_id ) use ( $repository ) {
			return $repository->get_ticket_data( $ticket_id );
		}, [ $ticket_ids[0], $ticket_ids[1] ] );
		$page_2_tickets = array_map( function ( $ticket_id ) use ( $repository ) {
			return $repository->get_ticket_data( $ticket_id );
		}, [ $ticket_ids[2], $ticket_ids[3] ] );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_id, 'per_page' => 2 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$page_1_response = json_decode( $I->grabResponse(), true );

		$expected_page_1_rest_url = add_query_arg( [
			'include_post' => $post_id,
			'per_page' => 2,
		], $this->tickets_url . '/' );

		$I->assertEquals( [
			'rest_url'    => $expected_page_1_rest_url,
			'total'       => 4,
			'total_pages' => 2,
			'tickets'     => $page_1_tickets,
		], $page_1_response );

		$I->sendGET( $this->tickets_url, [ 'include_post' => $post_id, 'per_page' => 2, 'page' => 2 ] );
		$I->seeResponseIsJson();
		$I->seeResponseCodeIs( 200 );
		$page_2_response = json_decode( $I->grabResponse(), true );

		$expected_page_2_rest_url = add_query_arg( [
			'include_post' => $post_id,
			'per_page'     => 2,
			'page'         => 2,
		], $this->tickets_url . '/' );

		$I->assertEquals( [
			'rest_url'    => $expected_page_2_rest_url,
			'total'       => 4,
			'total_pages' => 2,
			'tickets'     => $page_2_tickets,
		], $page_2_response );
	}

	protected function invalid_include_post() { return [
			'empty_string'      => [ '' ],
			'non_existing'      => [ '23' ],
			'bad_list_1'        => [ 'foo, bar' ],
			'non_existing_list' => [ '23, 89' ],
		];
	}
}
