<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as Ticket_Maker;
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

		$post_id = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );

		$I->havePostmetaInDatabase( $post_id, '_tribe_hide_attendees_list', '1' );

		$attendees_count             = 7;
		$ticket_id                   = $this->create_paypal_ticket_basic( $post_id, 5, [
			'meta_input' => [
				'total_sales' => $attendees_count,
				'_stock'      => 30 - $attendees_count,
				'_capacity'   => 30,
			],
		] );
		$first_attendee_checkin_date = '2018-01-02 09:00:16';
		$first_attendee_checkin_time = strtotime( $first_attendee_checkin_date );
		$first_attendee_order        = 'sdfjsldk4jk4lk3jlwjk2lbjlj2l3kj432l';
		$first_attendee_sku          = 'foo-bar-23';
		$first_attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $first_attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			],
			'order_id'        => $first_attendee_order,
			'sku'             => $first_attendee_sku,
		] );
		// for PayPal tickets the payment time is the moment attendees are generated
		$first_attendee_payment_date = get_post_time( 'Y-m-d H:i:s', false, $first_attendee_id );
		$first_attendee_payment_time = strtotime( $first_attendee_payment_date );
		$attendees_id                = $this->create_many_attendees_for_ticket( $attendees_count - 1, $ticket_id, $post_id );
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
			'provider'                      => 'tribe-commerce',
			'title'                         => $ticket_post->post_title,
			'description'                   => $ticket_post->post_excerpt,
			'image'                         => $repository->get_ticket_header_image( $ticket_id ),
			'available_from'                => $repository->get_ticket_start_date( $ticket_id ),
			'available_from_start_time'     => '',
			'available_from_end_time'       => '',
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
				'global_stock_mode'    => 'own',
			],
			'is_available'                  => true,
			'cost'                          => '$5.00',
			'cost_details'                  => [
				'currency_symbol'             => '$',
				'currency_position'           => 'prefix',
				'values'                      => [ '5' ],
				'currency_decimal_separator'  => '.',
				'currency_decimal_numbers'    => 2,
				'currency_thousand_separator' => ',',
			],
			'attendees'                     => $repository->get_ticket_attendees( $ticket_id ),
			'supports_attendee_information' => false, // no ET+ installed'
			'checkin'                       => [
				'checked_in'              => 1,
				'unchecked_in'            => 6,
				'checked_in_percentage'   => 15,
				'unchecked_in_percentage' => 85,
			],
			'capacity_type'                 => 'own',
			'sku'                           => '',
			'totals'                        => [
				'stock'   => 23,
				'sold'    => 7,
				'pending' => 0,
			],
			'suffix'                        => null,
			'ticket'                        => [
				'id'              => $ticket_id,
				'title'           => $ticket_post->post_title,
				'description'     => $ticket_post->post_excerpt,
				'raw_price'       => '5',
				'formatted_price' => '5.00',
				'currency_config' => [
					"symbol"             => '&#x24;',
					"placement"          => 'prefix',
					"decimal_point"      => '.',
					"thousands_sep"      => ',',
					"number_of_decimals" => 2,
				],
				'start_sale'      => trim( $repository->get_ticket_start_date( $ticket_id ) ),
				'end_sale'        => trim( $repository->get_ticket_end_date( $ticket_id ) ),
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
			'provider'          => 'tribe-commerce',
			'order'             => $first_attendee_order,
			'sku'               => $first_attendee_sku,
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
			'payment'           => [
				'provider'     => 'tpp',
				'price'        => '5',
				'currency'     => '$',
				'date'         => $first_attendee_payment_date,
				'date_details' => [
					'year'    => date( 'Y', $first_attendee_payment_time ),
					'month'   => date( 'm', $first_attendee_payment_time ),
					'day'     => date( 'd', $first_attendee_payment_time ),
					'hour'    => date( 'H', $first_attendee_payment_time ),
					'minutes' => date( 'i', $first_attendee_payment_time ),
					'seconds' => date( 's', $first_attendee_payment_time ),
				],
			],
			'optout'            => false,
			'is_subscribed'     => false,
			'is_purchaser'      => true,
			'ticket'            => [
				'id'              => $ticket_id,
				'title'           => $ticket_post->post_title,
				'description'     => $ticket_post->post_excerpt,
				'raw_price'       => '5',
				'formatted_price' => '5.00',
				'currency_config' => [
					"symbol"             => '&#x24;',
					"placement"          => 'prefix',
					"decimal_point"      => '.',
					"thousands_sep"      => ',',
					"number_of_decimals" => 2,
				],
				'start_sale'      => trim( $repository->get_ticket_start_date( $ticket_id ) ),
				'end_sale'        => trim( $repository->get_ticket_end_date( $ticket_id ) ),
			],
		];
		$I->assertEquals( $expected_first_attendee, $first_attendee_from_response );
	}

	/**
	 * It should hide private fields to public queries
	 *
	 * @test
	 */
	public function should_hide_private_fields_to_public_queries( Restv1Tester $I ) {
		$post_id         = $I->havePostInDatabase( [ 'post_content' => '[tribe_attendees_list]' ] );
		$attendees_count = 7;
		$optout_count    = 3;
		$ticket_id       = $this->create_paypal_ticket_basic( $post_id, 5, [
			'meta_input' => [
				'total_sales' => $attendees_count,
				'_stock'      => 30 - $attendees_count,
				'_capacity'   => 30,
			],
		] );
		// make some attendees optout
		$opting_in_attendees  = $this->create_many_attendees_for_ticket( $attendees_count - 1 - $optout_count, $ticket_id, $post_id );
		$opting_out_attendees = $this->create_many_attendees_for_ticket( $optout_count, $ticket_id, $post_id, [ 'optout' => 'yes' ] );

		$first_attendee_checkin_date = '2018-01-02 09:00:16';
		$first_attendee_checkin_time = strtotime( $first_attendee_checkin_date );
		$first_attendee_order        = 'sdfjsldk4jk4lk3jlwjk2lbjlj2l3kj432l';
		$first_attendee_sku          = 'foo-bar-23';
		$first_attendee_id           = $this->create_attendee_for_ticket( $ticket_id, $post_id, [
			'checkin'         => true,
			'checkin_details' => [
				'date'   => $first_attendee_checkin_date,
				'source' => 'bar',
				'author' => 'John Doe',
			],
			'order_id'        => $first_attendee_order,
			'sku'             => $first_attendee_sku,
		] );
		// for PayPal tickets the payment time is the moment attendees are generated
		$first_attendee_payment_date = get_post_time( 'Y-m-d H:i:s', false, $first_attendee_id );
		$first_attendee_payment_time = strtotime( $first_attendee_payment_date );
		$ticket_post                 = get_post( $ticket_id );
		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = $I->factory()->attachment->create_upload_object( codecept_data_dir( 'images/test-image-1.jpg' ) );
		update_post_meta( $post_id, $handler->key_image_header, $image_id );

		$ticket_rest_url = $this->tickets_url . "/{$ticket_id}";

		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();

		/** @var \Tribe__Tickets__REST__V1__Post_Repository $repository */
		$repository = tribe( 'tickets.rest-v1.repository' );

		$response           = json_decode( $I->grabResponse(), true );
		$response_attendees = $response['attendees'];
		unset( $response['attendees'] );

		$I->assertCount( count( $opting_in_attendees ) + 1, $response_attendees );

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
			'provider'                      => 'tribe-commerce',
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
			'cost'         => '$5.00',
			'cost_details' => [
				'currency_symbol'             => '$',
				'currency_position'           => 'prefix',
				'values'                      => [ 5 ],
				'suffix'                      => null,
				'currency_decimal_separator'  => '.',
				'currency_decimal_numbers'    => 2,
				'currency_thousand_separator' => ',',
			],
			'supports_attendee_information' => false, //no ET+ installed
			'price_suffix'                  => null,
			'on_sale'                       => null,
			'iac'                           => 'none',
			'type'                          => 'default',
			'sale_price_data'               => [],
		];

		$I->assertEquals( $expectedJson, $response );

		// @todo - move this to dedicated test when Attendees endpoint is done
		$attendees_objects            = tribe_tickets_get_ticket_provider( $ticket_id )->get_attendees_by_id( $ticket_id );
		$first_attendee_object        = array_values( array_filter( $attendees_objects, function ( $attendee ) use ( $first_attendee_id ) {
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
			'ticket'            => [
				'id'              => $ticket_id,
				'title'           => $ticket_post->post_title,
				'description'     => $ticket_post->post_excerpt,
				'raw_price'       => '5',
				'formatted_price' => '5.00',
				'currency_config' => [
					"symbol"             => '&#x24;',
					"placement"          => 'prefix',
					"decimal_point"      => '.',
					"thousands_sep"      => ',',
					"number_of_decimals" => 2,
				],
				'start_sale'      => trim( $repository->get_ticket_start_date( $ticket_id ) ),
				'end_sale'        => trim( $repository->get_ticket_end_date( $ticket_id ) ),
			],
		];

		$I->assertEquals( $expected_first_attendee, $first_attendee_from_response );
	}

	/**
	 * It should return 404 when trying to get non existing post ID
	 *
	 * @test
	 */
	public function should_return_404_when_trying_to_get_non_existing_post_id( Restv1Tester $I ) {
		$ticket_rest_url = $this->tickets_url . '/12312421345125';
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
		$ticket_id = $this->create_paypal_ticket_basic( $post_id, 3, [
			'post_status' => 'draft',
			'meta_input'  => [
				'total_sales' => 0,
				'_stock'      => 30,
				'_capacity'   => 30,
			],
		] );

		$ticket_rest_url = $this->tickets_url . "/{$ticket_id}";
		$I->sendGET( $ticket_rest_url );

		$I->seeResponseCodeIs( 401 );
		$I->seeResponseIsJson();
	}

	/**
	 * It should return 400 when trying to create a ticket with invalid price
	 *
	 * @test
	 */
	public function should_return_400_when_trying_to_create_a_ticket_ticket_with_invalid_price( Restv1Tester $I ) {
		$I->generate_nonce_for_role('administrator');

		$post_id = $I->havePostInDatabase();
		$ticket_create_rest_url = $this->tickets_url . '/';

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name qwerty 555',
			'description'      => 'Test description text',
			'price'            => 0,
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-111',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => -5,
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-222',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => '',
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-555',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => 0.0,
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-777',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => "0,0",
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-444',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => "000",
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-444',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );

		$I->seeResponseCodeIs( 400 );
		$I->seeResponseIsJson();

		$create_args = [
			'post_id'          => $post_id,
			'name'             => 'Test ticket name test 213',
			'description'      => 'Test description text',
			'price'            => ' ',
			'start_date'       => '2023-08-24',
			'start_time'       => '08:00:00',
			'end_date'         => '2023-12-31',
			'end_time'         => '20:00:00',
			'sku'              => 'TKT-444',
			'menu_order'       => 1,
			'add_ticket_nonce' => wp_create_nonce( 'add_ticket_nonce' ),
			'provider'         => 'TEC\Tickets\Commerce\Module',
			'ticket' => [
				'mode' => 'capped',
				'capacity' => 100,
				'event_capacity' => 100
			]
		];

		$I->sendPOST( $ticket_create_rest_url, $create_args );
	}
}
