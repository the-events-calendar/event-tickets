<?php

namespace Tribe\Tickets\Test\REST\V1\QR;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Cart\Agnostic_Cart;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker as TC_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker as TC_Order_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Module as TC_Module;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe__Date_Utils;
use Tribe__Tickets__Data_API as Data_API;

class ScanCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;
	use PayPal_Ticket_Maker;
	use TC_Ticket_Maker;
	use TC_Order_Maker;

	private $api_key = '909090';

	public function _before( Restv1Tester $I ) {
		parent::_before( $I );
		$this->rest_url = $this->site_url . '/wp-json/tribe/tickets/v1/qr';
		// Avoid tests breaking due to deprecation warnings.
		define( 'WP_DEBUG', false );
		define( 'WP_DEBUG_LOG', false );
		define( 'WP_DEBUG_DISPLAY', false );

		tribe_update_option( 'tickets-plus-qr-options-api-key', $this->api_key );

		// Initialize Tickets Commerce properly for tests that need it
		$this->setup_tickets_commerce();
	}

	/**
	 * Set up Tickets Commerce for tests that need it.
	 */
	private function setup_tickets_commerce() {
		// Enable Tickets Commerce
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ TC_Module::class ] = tribe( TC_Module::class )->plugin_name;
			return $modules;
		} );

		// Register the Commerce Provider using the pattern from bootstrap files
		tribe_register_provider( Commerce_Provider::class );

		// Reset Data_API object so it sees Tribe Commerce
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Set up Cart bindings to prevent Cart_Interface binding errors
		tribe_singleton( Cart::class, new Cart() );
		tribe_singleton( Cart_Interface::class, Agnostic_Cart::class );

		// Ensure post is ticketable
		$ticketable = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

		// Initialize the Commerce module
		tribe( TC_Module::class );

		// Enable fake transactions for testing
		if ( function_exists( 'tec_tickets_tests_fake_transactions_enable' ) ) {
			tec_tickets_tests_fake_transactions_enable();
		}
	}

	/**
	 * It should allow scanning a ticket if all params are valid.
	 *
	 * @test
	 */
	public function should_allow_checking_in_an_attendee_if_all_params_are_valid( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id     = $this->create_rsvp_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.rsvp' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );

		// check response.
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseContains( 'Checked In!' );
	}

	/**
	 * It should not allow scanning a ticket if api key is missing.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_api_key_is_missing( Restv1Tester $I ) {
		$data = [
			'event_id'      => 10,
			'ticket_id'     => 11,
			'security_code' => 12,
		];

		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 400 );

		$expected_json = [
			'code'    => 'rest_missing_callback_param',
			'message' => 'Missing parameter(s): api_key',
			'data'    => [
				'status' => 400,
				'params' => [
					'api_key',
				],
			],
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow scanning a ticket if api key is invalid.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_api_key_is_invalid( Restv1Tester $I ) {
		$data = [
			'api_key'       => 'random',
			'event_id'      => 10,
			'ticket_id'     => 11,
			'security_code' => 12,
		];

		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 400 );

		// returns the same data for invalid api key.
		$expected_json = [
			'api_key'       => 'random',
			'event_id'      => 10,
			'ticket_id'     => 11,
			'security_code' => 12,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow scanning a ticket if required params are missing.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_api_key_and_security_code_missing( Restv1Tester $I ) {
		$data = [
			'ticket_id' => 11,
		];

		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 400 );

		$expected_json = [
			'code'    => 'rest_missing_callback_param',
			'message' => 'Missing parameter(s): api_key, security_code',
			'data'    => [
				'status' => 400,
				'params' => [
					'api_key',
					'security_code',
				],
			],
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow scanning a ticket if required params are missing.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_api_key_and_ticket_id_missing( Restv1Tester $I ) {
		$data = [
			'security_code' => 11,
		];

		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 400 );

		$expected_json = [
			'code'    => 'rest_missing_callback_param',
			'message' => 'Missing parameter(s): api_key, ticket_id',
			'data'    => [
				'status' => 400,
				'params' => [
					'api_key',
					'ticket_id',
				],
			],
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow checking in a ticket if security code is invalid.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_security_code_is_invalid( Restv1Tester $I ) {
		$event_id    = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id   = $this->create_rsvp_ticket( $event_id );
		$attendee_id = $this->create_attendee_for_ticket( $ticket_id, $event_id );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => 'invalid_code',
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$expected_json = [
			'msg'   => 'Security code is not valid!',
			'error' => 'security_code_not_valid',
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow checking in if attendee order is not valid or attendee is not authorized.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_attendee_order_is_not_valid_or_attendee_is_not_authorized( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id     = $this->create_rsvp_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id, [ 'rsvp_status' => 'no' ] );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.rsvp' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );
		$expected_json = [
			'msg'      => 'This attendee&#039;s ticket is not authorized to be Checked in. Please check the order status.',
			'error'    => 'attendee_not_authorized',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow checking in the Event is not active.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_if_event_is_not_active( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [
			'post_type'  => 'tribe_events',
			'meta_input' => [
				'_EventTimezone'     => 'America/New_York',
				'_EventTimezoneAbbr' => 'EST',
				'_EventStartDate'    => '2017-01-05 14:23:36',
				'_EventEndDate'      => '2017-01-05 16:23:36',
				'_EventStartDateUTC' => '2017-01-05 14:23:36',
				'_EventEndDateUTC'   => '2017-01-05 16:23:36',
				'_EventDuration'     => '7200',
			],
		] );
		$ticket_id     = $this->create_rsvp_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.rsvp' )->security_code, true );

		/** @var \Tribe__Tickets_Plus__REST__V1__Endpoints__QR $qr_rest */
		$qr_rest = tribe( 'tickets.rest-v1.endpoints.qr' );

		// check for active events set to true.
		tribe_update_option( 'tickets-plus-qr-check-in-events-happening-now', true );
		$I->assertEquals( $qr_rest->should_checkin_qr_events_happening_now( $event_id, $attendee_id ), true );

		// event is not active.
		$is_active = $qr_rest->is_tec_event_happening_now( $event_id );
		$I->assertEquals( $is_active, false );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );

		$expected_json = [
			'msg'      => 'Event has not started or it has finished.',
			'error'    => 'event_not_happening_now',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow checking in a PayPal ticket attendee with pending order status.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_paypal_ticket_with_pending_status( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id     = $this->create_paypal_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id, [ 'order_status' => 'pending' ] );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.commerce.paypal' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );
		$expected_json = [
			'msg'      => 'This attendee&#039;s ticket is not authorized to be Checked in. Please check the order status.',
			'error'    => 'attendee_not_authorized',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should allow checking in a PayPal ticket attendee with completed order status.
	 *
	 * @test
	 */
	public function should_allow_checking_in_paypal_ticket_with_completed_status( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase(
			[
			'post_type' => 'tribe_events',
			'meta_input' => [
				'_EventStartDate' => Tribe__Date_Utils::build_date_object( 'now - 1 hour' )->format( 'Y-m-d H:i:s' ),
				'_EventEndDate'   => Tribe__Date_Utils::build_date_object( 'now + 1 hour' )->format( 'Y-m-d H:i:s' ),
			],
			]
		);
		$ticket_id     = $this->create_paypal_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id, [ 'order_status' => 'completed' ] );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.commerce.paypal' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );

		// check response.
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseContains( 'Checked In!' );
	}

	/**
	 * It should not allow checking in a Tickets Commerce attendee with pending order status.
	 *
	 * @test
	 * @skip TC integration is not working as expected here.
	 */
	public function should_not_allow_checking_in_tickets_commerce_with_pending_status( Restv1Tester $I ) {
		$event_id   = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id  = $this->create_tc_ticket( $event_id );

		// Create order with pending status and attendee
		$order = $this->create_order( [ $ticket_id => 1 ], [ 'order_status' => Pending::SLUG ] );
		$attendees = tribe( TC_Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_id = $attendees[0]['attendee_id'];

		$security_code = get_post_meta( $attendee_id, tribe( TC_Module::class )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );
		$expected_json = [
			'msg'      => 'This attendee&#039;s ticket is not authorized to be Checked in. Please check the order status.',
			'error'    => 'attendee_not_authorized',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should allow checking in a Tickets Commerce attendee with completed order status.
	 *
	 * @test
	 * @skip TC integration is not working as expected here.
	 */
	public function should_allow_checking_in_tickets_commerce_with_completed_status( Restv1Tester $I ) {
		$event_id   = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id  = $this->create_tc_ticket( $event_id );

		// Create order with completed status and attendee
		$order = $this->create_order( [ $ticket_id => 1 ], [ 'order_status' => Completed::SLUG ] );
		$attendees = tribe( TC_Module::class )->get_attendees_by_order_id( $order->ID );
		$attendee_id = $attendees[0]['attendee_id'];

		$security_code = get_post_meta( $attendee_id, tribe( TC_Module::class )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );

		// check response.
		$I->seeResponseCodeIs( 201 );
		$I->seeResponseContains( 'Checked In!' );
	}

	/**
	 * It should not allow checking in a PayPal ticket attendee with denied order status.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_paypal_ticket_with_denied_status( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id     = $this->create_paypal_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id, [ 'order_status' => 'denied' ] );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.commerce.paypal' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );
		$expected_json = [
			'msg'      => 'This attendee&#039;s ticket is not authorized to be Checked in. Please check the order status.',
			'error'    => 'attendee_not_authorized',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}

	/**
	 * It should not allow checking in a PayPal ticket attendee with refunded order status.
	 *
	 * @test
	 */
	public function should_not_allow_checking_in_paypal_ticket_with_refunded_status( Restv1Tester $I ) {
		$event_id      = $I->havePostInDatabase( [ 'post_type' => 'tribe_events' ] );
		$ticket_id     = $this->create_paypal_ticket( $event_id );
		$attendee_id   = $this->create_attendee_for_ticket( $ticket_id, $event_id, [ 'order_status' => 'refunded' ] );
		$security_code = get_post_meta( $attendee_id, tribe( 'tickets.commerce.paypal' )->security_code, true );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $this->api_key,
			'ticket_id'     => $attendee_id,
			'security_code' => $security_code,
		];
		// send checkin request.
		$I->sendGET( $this->rest_url, $data );
		// check response.
		$I->seeResponseCodeIs( 403 );

		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );
		$expected_json = [
			'msg'      => 'This attendee&#039;s ticket is not authorized to be Checked in. Please check the order status.',
			'error'    => 'attendee_not_authorized',
			'attendee' => $attendee_data,
		];

		$I->seeResponseContainsJson( $expected_json );
	}
}
