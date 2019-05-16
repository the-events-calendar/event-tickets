<?php

namespace Tribe\Tickets\ORM\Tickets;

use Tribe\Tickets\RSVPTest;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Ticket_Repository as Ticket_Repository;
use Tribe__Tickets__Data_API as Data_API;

class CreateTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;

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
	 * It should not allow creating a ticket from the default context.
	 *
	 * @test
	 */
	public function should_not_allow_creating_ticket_from_default_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets();

		$args = [
			'title' => 'A test ticket',
		];

		$ticket = $tickets->set_args( $args )->create();

		$this->assertFalse( $ticket );
	}

	/**
	 * It should allow creating a ticket from the rsvp context.
	 *
	 * @test
	 */
	public function should_allow_creating_ticket_from_rsvp_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'rsvp' );

		$args = [
			'title' => 'A test ticket',
		];

		$ticket = $tickets->set_args( $args )->create();

		$this->assertInstanceOf( \WP_Post::class, $ticket );
	}

	/**
	 * It should allow creating a ticket from the tribe-commerce context.
	 *
	 * @test
	 */
	public function should_allow_creating_ticket_from_tribe_commerce_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'tribe-commerce' );

		$args = [
			'title' => 'A test ticket',
		];

		$ticket = $tickets->set_args( $args )->create();

		$this->assertInstanceOf( \WP_Post::class, $ticket );
	}

}
