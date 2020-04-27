<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;


class Attendee_Registration extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;
	use Attendee_Maker;

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

		$GLOBALS['hook_suffix'] = 'tribe_events_page_tickets-attendees';
	}

	/**
	 * @test
	 */
	public function should_return_true_if_one_ticket_has_meta() {
		$post_id_1 = $this->factory->post->create();
		$post_id_2 = $this->factory->post->create();

		$paypal_ticket_id_1                   = $this->create_paypal_ticket_basic( $post_id_1, 5, [
			'meta_input' => [
				'_stock'              => 30,
				'_capacity'           => 30,
				'_tribe_tickets_meta_enabled' => 'yes',
				'_tribe_tickets_meta'        => [
					'login-name'           => [
						'slug'     => 'login-name',
						'label'    => 'Login Name',
						'type'     => 'text',
						'required' => 'on',
						'extra'    => [],
					],
				]
			],
		] );
		$paypal_ticket_id_2                   = $this->create_paypal_ticket_basic( $post_id_1, 7, [
			'meta_input' => [
				'_stock'              => 10,
				'_capacity'           => 10,
			],
		] );
		$paypal_ticket_id_3                   = $this->create_paypal_ticket_basic( $post_id_2, 9, [
			'meta_input' => [
				'_stock'              => 20,
				'_capacity'           => 20,
			],
		] );

		// one ticket with and one without, returns true
		$tickets_1_2[]['id'] = $paypal_ticket_id_1;
		$tickets_1_2[]['id'] = $paypal_ticket_id_2;
		$show_tickets_2      = tribe( 'tickets.attendee_registration' )->has_attendee_registration_enabled_in_array_of_tickets( $tickets_1_2 );
		$this->assertEquals( true, $show_tickets_2 );

		// no meta, returns false
		$tickets_3[]['id'] = $paypal_ticket_id_3;
		$show_tickets_3    = tribe( 'tickets.attendee_registration' )->has_attendee_registration_enabled_in_array_of_tickets( $tickets_3 );
		$this->assertEquals( false, $show_tickets_3 );
	}

}
