<?php

namespace Tribe\Tickets\ORM\Tickets;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Ticket_Repository as Ticket_Repository;
use Tribe__Tickets__Data_API as Data_API;

class SchemaTest extends \Codeception\TestCase\WPTestCase {

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
	 * It should count active tickets correctly from the tribe-commerce context.
	 *
	 * @test
	 */
	public function it_should_count_active_tickets_correctly_from_tribe_commerce_context() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets( 'tribe-commerce' );

		$post_id = $this->factory->post->create();

        // Create 5 tickets that are no longer active.
		$this->create_many_paypal_tickets( 5, $post_id, [
            'ticket_start_date' => date( 'Y-m-d', strtotime( '-3 day' ) ),
            'ticket_start_end'  => date( 'Y-m-d', strtotime( '-2 day' ) ),
        ] );
        // Create 5 tickets that are currently active.
		$this->create_many_paypal_tickets( 5, $post_id, [
            'ticket_start_date' => date( 'Y-m-d', strtotime( '-2 day' ) ),
            'ticket_start_end'  => date( 'Y-m-d', strtotime( '+2 day' ) ),
        ] );

		$active_tickets_count = $tickets->where( 'is_active' )->count();

		$this->assertEqualSets( $active_tickets_count, 5 );
	}

}
