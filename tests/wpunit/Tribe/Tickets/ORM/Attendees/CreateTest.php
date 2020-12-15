<?php

namespace Tribe\Tickets\ORM\Attendees;

use Tribe__Tickets__Attendee_Repository as Attendee_Repository;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class CreateTest
 *
 * @package Tribe\Tickets\ORM\Attendees
 * @group orm-create-update
 */
class CreateTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * It should not allow creating an attendee from the default context.
	 *
	 * @test
	 */
	public function should_not_allow_creating_attendee_from_default_context() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$args = [
			'title' => 'A test attendee',
		];

		$attendee = $attendees->set_args( $args )->create();

		$this->assertFalse( $attendee );
	}

	/**
	 * It should not allow creating an attendee from the rsvp context without required args.
	 *
	 * @test
	 */
	public function should_not_allow_creating_attendee_from_rsvp_context_without_required_args() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'rsvp' );

		$args = [
			'title' => 'A test attendee',
		];

		$attendee = $attendees->set_args( $args )->create();

		$this->assertFalse( $attendee );
	}

	/**
	 * It should not allow creating an attendee from the tribe-commerce context without required args.
	 *
	 * @test
	 */
	public function should_not_allow_creating_attendee_from_tribe_commerce_context_without_required_args() {
		/** @var Attendee_Repository $attendees */
		$attendees = tribe_attendees( 'tribe-commerce' );

		$args = [
			'title' => 'A test attendee',
		];

		$attendee = $attendees->set_args( $args )->create();

		$this->assertFalse( $attendee );
	}

}
