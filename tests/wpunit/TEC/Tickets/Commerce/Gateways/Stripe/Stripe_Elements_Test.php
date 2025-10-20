<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Codeception\TestCase\WPTestCase;

/**
 * Test the Stripe_Elements class.
 *
 * @since TBD
 */
class Stripe_Elements_Test extends WPTestCase {

	/**
	 * Test that get_checkout_template_vars returns expected structure.
	 *
	 * @test
	 */
	public function it_should_return_expected_checkout_template_vars() {
		$stripe_elements = tribe( Stripe_Elements::class );
		$vars = $stripe_elements->get_checkout_template_vars();

		$this->assertIsArray( $vars );
		$this->assertArrayHasKey( 'payment_element', $vars );
		$this->assertArrayHasKey( 'card_element_type', $vars );
		$this->assertIsBool( $vars['payment_element'] );
		$this->assertIsString( $vars['card_element_type'] );
	}

	/**
	 * Test must_login returns correct boolean.
	 *
	 * @test
	 */
	public function it_should_check_must_login_correctly() {
		$stripe_elements = tribe( Stripe_Elements::class );

		// User is logged in.
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$this->assertFalse( $stripe_elements->must_login() );

		// User is logged out.
		wp_set_current_user( 0 );

		// Without login requirement, should still be false.
		$this->assertFalse( $stripe_elements->must_login() );
	}

	/**
	 * Test that card_element_type returns valid string.
	 *
	 * @test
	 */
	public function it_should_return_valid_card_element_type() {
		$stripe_elements = tribe( Stripe_Elements::class );
		$card_type = $stripe_elements->card_element_type();

		$this->assertIsString( $card_type );
		$this->assertContains( $card_type, [ Settings::COMPACT_CARD_ELEMENT_SLUG, Settings::SEPARATE_CARD_ELEMENT_SLUG, '' ] );
	}

	/**
	 * Test that include_payment_element returns boolean.
	 *
	 * @test
	 */
	public function it_should_return_boolean_for_include_payment_element() {
		$stripe_elements = tribe( Stripe_Elements::class );
		$result = $stripe_elements->include_payment_element();

		$this->assertIsBool( $result );
	}
}
