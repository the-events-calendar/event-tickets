<?php

namespace Tribe\Tickets\ORM\Orders;

use Tribe\Tickets\Repositories\Order;
use Tribe__Tickets__Data_API as Data_API;

/**
 * Class CreateTest
 *
 * @package Tribe\Tickets\ORM\Orders
 * @group   orm-create-update
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
		/** @var Order $orders */
		$orders = tribe_tickets_orders();

		$args = [
			'title' => 'A test order',
		];

		$order = $orders->set_args( $args )->create();

		$this->assertFalse( $order );
	}

}
