<?php

namespace Tribe\Tickets\Tickets;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;

class UTCTest extends Ticket_Object_TestCase {
	protected $timezone = 'UTC';

	/**
	 * It should allow getting availability slug by collection as available.
	 *
	 * @test
	 */
	public function should_allow_getting_availability_slug_by_collection_as_available() {
		$event_id = $this->make_event();

		$rsvp  = $this->make_rsvp( [], $event_id );
		$rsvp2 = $this->make_rsvp( [], $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'available', $availability_slug, 'Failed to get correct availability slug on RSVP (available).' );
	}

	/**
	 * It should allow getting availability slug by collection as availability-future.
	 *
	 * @test
	 */
	public function should_allow_getting_availability_slug_by_collection_as_availability_future() {
		$event_id = $this->make_event();

		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+20 minutes' ) ),
			],
		];

		$rsvp  = $this->make_rsvp( $args, $event_id );
		$rsvp2 = $this->make_rsvp( $args, $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'availability-future', $availability_slug, 'Failed to get correct availability slug on RSVP (availability-future).' );
	}

	/**
	 * It should allow getting availability slug by collection as availability-past.
	 *
	 * @test
	 */
	public function should_allow_getting_availability_slug_by_collection_as_availability_past() {
		$event_id = $this->make_event();

		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-20 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
			],
		];

		$rsvp  = $this->make_rsvp( $args, $event_id );
		$rsvp2 = $this->make_rsvp( $args, $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'availability-past', $availability_slug, 'Failed to get correct availability slug on RSVP (availability-past).' );
	}

	/**
	 * It should allow getting availability slug by collection as availability-mixed.
	 *
	 * @test
	 */
	public function should_allow_getting_availability_slug_by_collection_as_availability_mixed() {
		$event_id = $this->make_event();

		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-20 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
			],
		];

		$args2 = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+20 minutes' ) ),
			],
		];

		$rsvp  = $this->make_rsvp( $args, $event_id );
		$rsvp2 = $this->make_rsvp( $args2, $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'availability-mixed', $availability_slug, 'Failed to get correct availability slug on RSVP (availability-mixed).' );
	}

	/**
	 * It should allow filtering the availability slug in collection
	 *
	 * @test
	 */
	public function it_should_allow_filtering_the_availability_slug_in_collection() {
		add_filter( 'event_tickets_availability_slug', static function () {
			return 'slug_test';
		} );

		$event_id = $this->make_event();

		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-20 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
			],
		];

		$args2 = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+20 minutes' ) ),
			],
		];

		$rsvp  = $this->make_rsvp( $args, $event_id );
		$rsvp2 = $this->make_rsvp( $args2, $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'slug_test', $availability_slug, 'Failed to get correct availability slug on RSVP (available).' );
	}

	/**
	 * It should allow filtering the availability slug by collection
	 *
	 * @test
	 */
	public function it_should_allow_filtering_the_availability_slug_by_collection() {
		add_filter( 'event_tickets_availability_slug_by_collection', static function () {
			return 'collection_slug_test';
		} );

		$event_id = $this->make_event();

		$args = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-20 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
			],
		];

		$args2 = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+20 minutes' ) ),
			],
		];

		$rsvp  = $this->make_rsvp( $args, $event_id );
		$rsvp2 = $this->make_rsvp( $args2, $event_id );

		/** @var \Tribe__Tickets__RSVP $rsvp_provider */
		$rsvp_provider = tribe( 'tickets.rsvp' );

		$tickets = $rsvp_provider->get_tickets( $event_id );

		$availability_slug = $rsvp_provider->get_availability_slug_by_collection( $tickets );

		$this->assertEquals( 'collection_slug_test', $availability_slug, 'Failed to get correct availability slug on RSVP (available).' );
	}
}
