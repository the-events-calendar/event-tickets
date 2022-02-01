<?php

namespace Tribe\Tickets\ORM\Tickets;

use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Ticket_Repository as Ticket_Repository;
use Tribe__Tickets__Data_API as Data_API;

class SchemaTest extends \Codeception\TestCase\WPTestCase {

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
	 * It should count active tickets properly.
	 *
	 * @test
	 */
	public function should_count_active_tickets() {
		/** @var Ticket_Repository $tickets */
		$tickets = tribe_tickets();

		$post_id = $this->factory->post->create();

		$inactive_ticket_args = [
			'ticket_start_date' => '2020-01-02',
			'ticket_end_date'   => '2021-03-01',
		];

		$inactive_ticket_ids = $this->create_many_paypal_tickets( 5, $post_id, $inactive_ticket_args );

		$active_ticket_args = [
			'ticket_start_date' => '2020-01-02',
			'ticket_end_date'   => '2050-03-01',
		];

		$active_ticket_ids = $this->create_many_paypal_tickets( 3, $post_id, $active_ticket_args );

		$total_tickets = count( $inactive_ticket_ids ) + count( $active_ticket_ids );

		$total_count  = $tickets->count();

		$active_count = $tickets->where( 'is_active' )->count();

		$this->assertEquals( 8 , $total_tickets );
		$this->assertEquals( 3, $active_count );
		$this->assertEquals( $total_tickets, $total_count );
	}
}
