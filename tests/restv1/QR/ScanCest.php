<?php

namespace Tribe\Tickets\Test\REST\V1\QR;

use Restv1Tester;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseRestCest;

class ScanCest extends BaseRestCest {
	use RSVP_Ticket_Maker;
	use Attendee_Maker;

	public function _before( Restv1Tester $I ) {
		parent::_before( $I );
		$this->rest_url = $this->site_url . '/wp-json/tribe/tickets/v1/qr';
		// Avoid tests breaking due to deprecation warnings.
		define( 'WP_DEBUG', false );
		define( 'WP_DEBUG_LOG', false );
		define( 'WP_DEBUG_DISPLAY', false );
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

		// set api key for settings.
		$api_key = '909090';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $api_key,
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

		// set api key for settings.
		$api_key = '909090';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $api_key,
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

		// set api key for settings.
		$api_key = '909090';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $api_key,
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

		// set api key for settings.
		$api_key = '909090';
		tribe_update_option( 'tickets-plus-qr-options-api-key', $api_key );

		$data = [
			'event_id'      => $event_id,
			'api_key'       => $api_key,
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
}
