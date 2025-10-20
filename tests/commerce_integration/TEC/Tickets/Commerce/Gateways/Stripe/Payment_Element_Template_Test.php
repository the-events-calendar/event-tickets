<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;
use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;

/**
 * Test the Payment Element template rendering.
 *
 * @since TBD
 */
class Payment_Element_Template_Test extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/stripe/payment-element';

	/**
	 * Test that payment element renders with hidden button initially.
	 *
	 * @test
	 */
	public function it_should_render_payment_element_with_hidden_button() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => true,
		] );

		$this->assertMatchesHtmlSnapshot( $html );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should have hidden class initially.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID.' );
	}

	/**
	 * Test that payment element doesn't render when must_login is true.
	 *
	 * @test
	 */
	public function it_should_not_render_when_must_login() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => true,
			'payment_element'  => true,
		] );

		$this->assertEmpty( $html, 'Template should not render when must_login is true.' );
	}

	/**
	 * Test that payment element doesn't render when payment_element is false.
	 *
	 * @test
	 */
	public function it_should_not_render_when_payment_element_false() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => false,
		] );

		$this->assertEmpty( $html, 'Template should not render when payment_element is false.' );
	}

	/**
	 * Test that payment element has correct structure.
	 *
	 * @test
	 */
	public function it_should_render_with_correct_structure() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => true,
		] );

		// Check for payment element container.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-payment-element"', $html );

		// Check for button.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html );

		// Check for spinner.
		$this->assertStringContainsString( 'class="spinner hidden"', $html );

		// Check for payment message container.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-payment-message"', $html );

		// Check for errors container.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-errors"', $html );
	}
}
