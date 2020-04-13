<?php

namespace Tribe\Tickets\Test\Testcases;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Test_Case;
use Tribe__Date_Utils as Date_Utils;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Ticket_Object as RSVP;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

class Ticket_Object_TestCase extends Test_Case {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	protected $timezone = 'UTC';

	protected $earlier_date = 0;
	protected $now_date = 0;
	protected $later_date = 0;

	public function setUp() {
		// before
		parent::setUp();

		$GLOBALS['post'] = null;

		date_default_timezone_set( 'UTC' );

		update_option( 'timezone_string', $this->timezone );

		// your set up methods here
		$this->factory()->event = new Event();

		$this->earlier_date = strtotime( '-3 hours' );
		$this->now_date     = time();
		$this->later_date   = strtotime( '+3 hours' );

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', static function () {
			return [
				'post',
				'tribe_events',
			];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = $paypal->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @return Ticket_Object
	 */
	protected function make_instance() {
		return new Ticket_Object();
	}

	/**
	 * Wrapper function: Get ticket object from ticket ID
	 *
	 * @param int $ticket_id
	 *
	 * @return Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		return $provider->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Create event and return event ID.
	 *
	 * @return int Event ID.
	 */
	protected function make_event() {
		$event_id = $this->factory()->event->create();

		update_post_meta( $event_id, '_EventTimezone', $this->timezone );

		return $event_id;
	}

	/**
	 * Create event and RSVP, return RSVP object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param array    $args     List of arguments to send to ticket creation.
	 * @param null|int $event_id Event ID to use instead of creating a new one.
	 *
	 * @return RSVP
	 */
	protected function make_rsvp( $args = [], $event_id = null ) {
		$defaults = [
			'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( $this->earlier_date ),
			'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( $this->later_date ),
		];

		/*codecept_debug( '----------------' );
		codecept_debug( 'Earlier Date: ' . var_export( $this->earlier_date, true ) );
		codecept_debug( 'Now Date: ' . var_export( $this->now_date, true ) );
		codecept_debug( 'Later Date: ' . var_export( $this->later_date, true ) );
		codecept_debug( 'Ticket start/end: ' . var_export( $defaults, true ) );
		codecept_debug( '----------------' );*/

		if ( isset( $args['meta_input'] ) ) {
			$args['meta_input'] = array_merge( $defaults, $args['meta_input'] );
		} else {
			$args['meta_input'] = $defaults;
		}

		if ( ! $event_id ) {
			$event_id = $this->make_event();
		}

		$rsvp_id  = $this->create_rsvp_ticket( $event_id, $args );

		return $this->get_ticket( $event_id, $rsvp_id );
	}

	/**
	 * Create event and Tribe Commerce Ticket, return Ticket object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param int      $cost     Cost of ticket.
	 * @param array    $args     List of arguments to send to ticket creation.
	 * @param null|int $event_id Event ID to use instead of creating a new one.
	 *
	 * @return PayPal
	 */
	protected function make_ticket( $cost = 1, $args = [], $event_id = null ) {
		$defaults = [
			'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( $this->earlier_date ),
			'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( $this->later_date ),
		];

		if ( isset( $args['meta_input'] ) ) {
			$args['meta_input'] = array_merge( $defaults, $args['meta_input'] );
		} else {
			$args['meta_input'] = $defaults;
		}

		if ( ! $event_id ) {
			$event_id = $this->make_event();
		}

		$ticket_id = $this->create_paypal_ticket_basic( $event_id, $cost, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * Create event and RSVP with shared capacity, return RSVP object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param integer $cost
	 * @param array   $args
	 *
	 * @return PayPal
	 */
	protected function make_shared_rsvp( $cost = 1, $args = [] ) {
		$event_args = [
			'meta_input' => [
				'_tribe_ticket_use_global_stock' => 1,
				'_tribe_ticket_capacity'         => 100,
			],
		];

		$event_id  = $this->make_event();
		$ticket_id = $this->create_rsvp_ticket( $event_id, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	/**
	 * reate event and Tribe Commerce Ticket with shared capacity, return Ticket object.
	 * Also sets timezone for event as this is needed for some tests.
	 *
	 * @param integer $cost
	 * @param array   $args
	 *
	 * @return PayPal
	 */
	protected function make_shared_ticket( $cost = 1, $args = [] ) {
		$event_args = [
			'meta_input' => [
				'_tribe_ticket_use_global_stock' => 1,
				'_tribe_ticket_capacity'         => 100,
			],
		];

		$event_id  = $this->make_event();
		$ticket_id = $this->create_paypal_ticket_basic( $event_id, $cost, $args );

		return $this->get_ticket( $event_id, $ticket_id );
	}

	protected function get_local_datetime_string_from_utc_time( $time = null ) {
		if ( null === $time ) {
			$time = time();
		}

		$utc_timezone = new \DateTimeZone( 'UTC' );
		$date         = Date_Utils::build_date_object( $time, $utc_timezone );

		if ( 'UTC' !== $this->timezone ) {
			$timezone = new \DateTimeZone( $this->timezone );
			$date->setTimezone( $timezone );
		}

		return $date->format( Date_Utils::DBDATETIMEFORMAT );
	}

	protected function get_timestamp_from_utc_time( $time = null ) {
		if ( null === $time ) {
			$time = time();
		}

		$utc_timezone = new \DateTimeZone( 'UTC' );
		$date         = Date_Utils::build_date_object( $time, $utc_timezone );

		if ( 'UTC' !== $this->timezone ) {
			$timezone = new \DateTimeZone( $this->timezone );
			$date->setTimezone( $timezone );
		}

		return $date->format( Date_Utils::DBDATETIMEFORMAT );
	}
}
