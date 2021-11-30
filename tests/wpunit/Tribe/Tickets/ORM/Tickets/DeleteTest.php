<?php

namespace Tribe\Tickets\ORM\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Ticket_Repository as Ticket_Repository;
use Tribe__Tickets__Data_API as Data_API;

class DeleteTest extends \Codeception\TestCase\WPTestCase {

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

	public function _before() {
		tribe_events()->per_page( -1 )->delete();
		tribe_tickets()->per_page( -1 )->delete();
	}

	/**
	 * It should allow deleting tickets.
	 *
	 * @test
	 */
	public function should_allow_deleting_tickets() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets();

		$post_id = $this->factory->post->create();

		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );
		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );

		$deleted = $tickets->delete();

		$this->assertEqualSets( array_merge( $paypal_ticket_ids, $rsvp_ticket_ids ), $deleted );
	}

	/**
	 * It should allow deleting tickets from the rsvp context.
	 *
	 * @test
	 */
	public function should_allow_deleting_tickets_from_rsvp_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'rsvp' );

		$post_id = $this->factory->post->create();

		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );
		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );

		$deleted = $tickets->delete();

		$this->assertEqualSets( $rsvp_ticket_ids, $deleted );
	}

	/**
	 * It should allow deleting tickets from the tribe-commerce context.
	 *
	 * @test
	 */
	public function should_allow_deleting_tickets_from_tribe_commerce_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

		$paypal_ticket_ids = $this->create_many_paypal_tickets_basic( 5, $post_id );
		$rsvp_ticket_ids   = $this->create_many_rsvp_tickets( 5, $post_id );

		$deleted = $tickets->delete();

		$this->assertEqualSets( $paypal_ticket_ids, $deleted );
	}

}
