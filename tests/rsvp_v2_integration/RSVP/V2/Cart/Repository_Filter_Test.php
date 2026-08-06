<?php

namespace TEC\Tickets\RSVP\V2\Cart;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\RSVP\V2\Constants;

/**
 * `use_rsvp_cart_when_needed()` is a pure function of its two arguments: no global var, no
 * container lookups on the "should we swap?" decision. These tests exercise it directly with
 * both argument values instead of going through the full order-creation flow.
 */
class Repository_Filter_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_return_the_given_cart_for_the_default_ticket_type(): void {
		$filter = new Repository_Filter();
		$cart   = tribe( Cart_Interface::class );

		$this->assertSame( $cart, $filter->use_rsvp_cart_when_needed( $cart, 'ticket' ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_given_cart_when_no_ticket_type_is_passed(): void {
		$filter = new Repository_Filter();
		$cart   = tribe( Cart_Interface::class );

		$this->assertSame( $cart, $filter->use_rsvp_cart_when_needed( $cart ) );
	}

	/**
	 * @test
	 */
	public function it_should_swap_to_rsvp_cart_for_the_tc_rsvp_ticket_type(): void {
		$filter = new Repository_Filter();
		$cart   = tribe( Cart_Interface::class );

		$result = $filter->use_rsvp_cart_when_needed( $cart, Constants::TC_RSVP_TYPE );

		$this->assertInstanceOf( RSVP_Cart::class, $result );
		$this->assertNotSame( $cart, $result );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_given_cart_for_an_unrelated_ticket_type(): void {
		$filter = new Repository_Filter();
		$cart   = tribe( Cart_Interface::class );

		$this->assertSame( $cart, $filter->use_rsvp_cart_when_needed( $cart, 'some-other-type' ) );
	}
}
