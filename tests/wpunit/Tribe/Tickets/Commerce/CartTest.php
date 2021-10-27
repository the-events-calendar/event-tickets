<?php

namespace Tribe\Tickets\Commerce;

use TEC\Tickets\Commerce\Cart;

class CartTest extends \Codeception\TestCase\WPTestCase {

	public function test_repository_is_returned() {
		$cart = new Cart();
		$repository = $cart->get_repository();

		$this->assertTrue( is_a( $repository, 'TEC\Tickets\Commerce\Cart\Unmanaged_Cart' ) );
	}
}
