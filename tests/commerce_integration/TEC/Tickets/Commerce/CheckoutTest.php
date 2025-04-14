<?php

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class CheckoutTest extends WPTestCase {

	use Cart_Trait;
	use Ticket_Maker;
	use With_Uopz;

	private static $cart_hash;

	public function test_current_page_is_checkout_page() {
		$checkout = tribe( Checkout::class );

		$this->assertFalse( $checkout->is_current_page() );

		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', false );
		$this->set_fn_return( 'get_queried_object_id', 7654876598 );
		$this->set_class_fn_return( Checkout::class, 'get_page_id', 7654876598 );

		$this->assertFalse( $checkout->is_current_page() );

		$this->set_fn_return( 'tec_tickets_commerce_is_enabled', true );

		$this->assertTrue( $checkout->is_current_page() );
	}
}
