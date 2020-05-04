<?php

namespace Tribe\Tickets\Test\REST\V1\RSVP;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class SingleTicketCest extends BaseRestCest {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * It should allow getting a ticket information by ticket post ID
	 *
	 * @test
	 */
	public function should_allow_getting_a_ticket_information_by_ticket_post_id( Restv1Tester $I ) {
		$I->generate_nonce_for_role( 'administrator' );

		$post_id                     = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );
		$going_attendees_count       = 7;
		$not_going_attendees_count   = 5;
		$ticket_id                   = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => $going_attendees_count,
				'_stock'      => 30 - $going_attendees_count,
				'_capacity'   => 30,
			]
		] );
		$first_attendee_checkin_date = '2018-01-02 09:00:16';
		$first_attendee_checkin_time = strtotime( $first_attendee_checkin_date );
		$first_attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $first_attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			]
		] );
		$going_attendees             = $this->create_many_attendees_for_ticket( $going_attendees_count - 1, $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$not_going_attendees         = $this->create_many_attendees_for_ticket( $not_going_attendees_count, $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );
		$ticket_post                 = get_post( $ticket_id );
		$ticket_rest_url             = $this->tickets_url . "/{$ticket_id}";
		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = $I->factory()->attachment->create_upload_object( codecept_data_dir( 'images/test-image-1.jpg' ) );
		update_post_meta( $post_id, $handler->key_image_header, $image_id );

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$ticket_attendees = $repository->get_ticket_attendees( $ticket_id );
		$ticket_object    = \Tribe__Tickets__RSVP::load_ticket_object( $ticket_id );

		$expectedJson = [
			'id'                            => $ticket_id,
			'post_id'                       => $post_id,
			'global_id'                     => $repository->get_ticket_global_id( $ticket_id ),
			'global_id_lineage'             => $repository->get_ticket_global_id_lineage( $ticket_id ),
			'author'                        => $ticket_post->post_author,
			'status'                        => $ticket_post->post_status,
			'date'                          => $ticket_post->post_date,
			'date_utc'                      => $ticket_post->post_date_gmt,
			'modified'                      => $ticket_post->post_modified,
			'modified_utc'                  => $ticket_post->post_modified_gmt,
			'rest_url'                      => $ticket_rest_url,
			'provider'                      => 'rsvp',
			'title'                         => $ticket_post->post_title,
			'description'                   => $ticket_post->post_excerpt,
			'image'                         => $repository->get_ticket_header_image( $ticket_id ),
			'available_from'                => $repository->get_ticket_start_date( $ticket_id ),
			'available_from_details'        => $repository->get_ticket_start_date( $ticket_id, true ),
			'available_until'               => $repository->get_ticket_end_date( $ticket_id ),
			'available_until_details'       => $repository->get_ticket_end_date( $ticket_id, true ),
			'capacity'                      => 30,
			'capacity_details'              => [
				'available_percentage' => 76,
				'max'                  => 30,
				'available'            => 23,
				'sold'                 => 7,
				'pending'              => 0,
			],
			'is_available'                  => true,
			'cost'                          => '$0.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [ 0 ],
			],
			'attendees'                     => $ticket_attendees,
			'supports_attendee_information' => false, // we are on RSVP, no ET+ installed'
			'rsvp'                          => [
				'rsvp_going'     => $going_attendees_count,
				'rsvp_not_going' => $not_going_attendees_count,
			],
			'checkin'                       => [
				'checked_in'              => 1,
				'unchecked_in'            => 11,
				'checked_in_percentage'   => 9,
				'unchecked_in_percentage' => 91,
			],
			'capacity_type'                 => 'own',
			'sku'                           => null,
			'totals'                        => [
				'stock'   => 23,
				'sold'    => 7,
				'pending' => 0,
			],
		];

		$response = json_decode( $I->grabResponse(), true );

		$I->assertContains( $response, $expectedJson );

		// @todo - move this to dedicated test when Attendees endpoint is done
		$attendees_objects = tribe_tickets_get_ticket_provider( $ticket_id )->get_attendees_by_id( $ticket_id );

		$first_attendee_object = array_values( array_filter( $attendees_objects, function ( $attendee ) use ( $first_attendee_id ) {
			return $attendee['attendee_id'] === $first_attendee_id;
		} ) )[0];

		$first_attendee_from_response = array_values( array_filter( $response['attendees'], function ( $attendee ) use ( $first_attendee_id ) {
			return $attendee['id'] === $first_attendee_id;
		} ) )[0];

		$first_attendee_post = get_post( $first_attendee_id );

		$I->assertInstanceOf( \WP_Post::class, $first_attendee_post );

		$expected_first_attendee = [
			'id'                => $first_attendee_id,
			'post_id'           => $post_id,
			'ticket_id'         => $ticket_id,
			'global_id'         => $repository->get_attendee_global_id( $first_attendee_id ),
			'global_id_lineage' => $repository->get_attendee_global_id_lineage( $first_attendee_id ),
			'author'            => $first_attendee_post->post_author,
			'status'            => $first_attendee_post->post_status,
			'date'              => $first_attendee_post->post_date,
			'date_utc'          => $first_attendee_post->post_date_gmt,
			'modified'          => $first_attendee_post->post_modified,
			'modified_utc'      => $first_attendee_post->post_modified_gmt,
			'rest_url'          => $this->attendees_url . '/' . $first_attendee_id,
			'provider'          => 'rsvp',
			'order'             => $first_attendee_id, // they are the same!
			'sku'               => '',
			'title'             => $first_attendee_object['holder_name'],
			'email'             => $first_attendee_object['holder_email'],
			'checked_in'        => (bool) $first_attendee_object['check_in'],
			'checkin_details'   => [
				'date'         => $first_attendee_checkin_date,
				'date_details' => [
					'year'    => date( 'Y', $first_attendee_checkin_time ),
					'month'   => date( 'm', $first_attendee_checkin_time ),
					'day'     => date( 'd', $first_attendee_checkin_time ),
					'hour'    => date( 'H', $first_attendee_checkin_time ),
					'minutes' => date( 'i', $first_attendee_checkin_time ),
					'seconds' => date( 's', $first_attendee_checkin_time ),
				],
				'source'       => 'bar',
				'author'       => 'John Doe',
			],
			'rsvp_going'        => true,
			'optout'            => false,
		];

		$I->assertEquals( $expected_first_attendee, $first_attendee_from_response );
	}

	/**
	 * It should hide private fields to public queries
	 *
	 * @test
	 */
	public function should_hide_private_fields_to_public_queries( Restv1Tester $I ) {
		$post_id                     = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );
		$going_attendees_count       = 7;
		$going_optin_count = 4;
		$going_optout_count = 3;
		$not_going_attendees_count   = 5;
		$ticket_id                   = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => $going_attendees_count,
				'_stock'      => 30 - $going_attendees_count,
				'_capacity'   => 30,
			]
		] );
		$first_attendee_checkin_date = '2018-01-02 09:00:16';
		$first_attendee_checkin_time = strtotime( $first_attendee_checkin_date );
		$first_attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $first_attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			]
		] );
		// here we decrease the going and optin count by 1 to account for the going and optin first attendee
		$going_optin_attendees = $this->create_many_attendees_for_ticket( $going_optin_count -1, $ticket_id, $post_id, [ 'rsvp_status' => 'yes' ] );
		$goin_optout_attendees = $this->create_many_attendees_for_ticket( $going_optout_count, $ticket_id, $post_id, [
			'rsvp_status' => 'yes',
			'optout'      => 'yes'
		] );
		$not_going_attendees = $this->create_many_attendees_for_ticket( $not_going_attendees_count, $ticket_id, $post_id, [ 'rsvp_status' => 'no' ] );
		$ticket_post                 = get_post( $ticket_id );
		$ticket_rest_url             = $this->tickets_url . "/{$ticket_id}";
		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = $I->factory()->attachment->create_upload_object( codecept_data_dir( 'images/test-image-1.jpg' ) );
		update_post_meta( $post_id, $handler->key_image_header, $image_id );

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		$response = json_decode( $I->grabResponse(), true );

		$response_attendees = $response['attendees'];
		unset( $response['attendees'] );

		$I->assertCount( count( $going_optin_attendees ) + 1, $response_attendees );

		$expectedJson     = array(
			'id'                            => $ticket_id,
			'post_id'                       => $post_id,
			'global_id'                     => $repository->get_ticket_global_id( $ticket_id ),
			'global_id_lineage'             => $repository->get_ticket_global_id_lineage( $ticket_id ),
			'author'                        => $ticket_post->post_author,
			'status'                        => $ticket_post->post_status,
			'date'                          => $ticket_post->post_date,
			'date_utc'                      => $ticket_post->post_date_gmt,
			'modified'                      => $ticket_post->post_modified,
			'modified_utc'                  => $ticket_post->post_modified_gmt,
			'rest_url'                      => $ticket_rest_url,
			'provider'                      => 'rsvp',
			'title'                         => $ticket_post->post_title,
			'description'                   => $ticket_post->post_excerpt,
			'image'                         => $repository->get_ticket_header_image( $ticket_id ),
			'available_from'                => $repository->get_ticket_start_date( $ticket_id ),
			'available_from_details'        => $repository->get_ticket_start_date( $ticket_id, true ),
			'available_until'               => $repository->get_ticket_end_date( $ticket_id ),
			'available_until_details'       => $repository->get_ticket_end_date( $ticket_id, true ),
			'capacity'                      => 30,
			'capacity_details'              => [
				'available_percentage' => 76,
				'available'            => 23,
			],
			'is_available'                  => true,
			'cost'                          => '$0.00',
			'cost_details'                  => [
				'currency_symbol'   => '$',
				'currency_position' => 'prefix',
				'values'            => [ 0 ],
				'suffix'            => null,
			],
			'supports_attendee_information' => false, // we are on RSVP, no ET+ installed'
			'price_suffix'                  => null,
		);

		$I->assertEquals( $expectedJson, $response );

		// @todo - move this to dedicated test when Attendees endpoint is done
		$attendees_objects            = tribe_tickets_get_ticket_provider( $ticket_id )->get_attendees_by_id( $ticket_id );
		$first_attendee_object = array_values( array_filter( $attendees_objects, function ( $attendee ) use ( $first_attendee_id ) {
			return $attendee['attendee_id'] === $first_attendee_id;
		} ) )[0];
		$first_attendee_from_response = array_values( array_filter( $response_attendees, function ( $attendee ) use ( $first_attendee_id ) {
			return $attendee['id'] === $first_attendee_id;
		} ) )[0];
		$first_attendee_post          = get_post( $first_attendee_id );

		$I->assertInstanceOf( \WP_Post::class, $first_attendee_post );

		$expected_first_attendee = [
			'id'                => $first_attendee_id,
			'post_id'           => $post_id,
			'ticket_id'         => $ticket_id,
			'global_id'         => $repository->get_attendee_global_id( $first_attendee_id ),
			'global_id_lineage' => $repository->get_attendee_global_id_lineage( $first_attendee_id ),
			'author'            => $first_attendee_post->post_author,
			'status'            => $first_attendee_post->post_status,
			'date'              => $first_attendee_post->post_date,
			'date_utc'          => $first_attendee_post->post_date_gmt,
			'modified'          => $first_attendee_post->post_modified,
			'modified_utc'      => $first_attendee_post->post_modified_gmt,
			'rest_url'          => $this->attendees_url . '/' . $first_attendee_id,
			'title'             => $first_attendee_object['holder_name'],
			'optout'            => false,
		];
		$I->assertEquals( $expected_first_attendee, $first_attendee_from_response );
	}

	/**
	 * It should return 404 when trying to get non existing post ID
	 *
	 * @test
	 */
	public function should_return_404_when_trying_to_get_non_existing_post_id( Restv1Tester $I ) {
		$ticket_rest_url = $this->tickets_url . '/23';
		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 404 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return 401 when trying to access non public ticket
	 *
	 * @test
	 */
	public function should_return_401_when_trying_to_access_non_public_ticket( Restv1Tester $I ) {
		$post_id   = $I->havePostInDatabase();
		$ticket_id = $this->create_rsvp_ticket( $post_id, [
			'post_status' => 'draft',
			'meta_input'  => [
				'total_sales' => 0,
				'_stock'      => 30,
				'_capacity'   => 30,
			]
		] );

		$ticket_rest_url = $this->tickets_url . "/{$ticket_id}";
		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
	}
}
