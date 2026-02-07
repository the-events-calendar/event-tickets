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
	 * Test that the payment element renders with hidden "Purchase Tickets" button initially.
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
	 * Test that the payment element doesn't render when billing info is still not collected.
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
	 * Test that payment element has correct structure for JavaScript interaction.
	 *
	 * @test
	 */
	public function it_should_have_correct_structure_for_js() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => true,
		] );

		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID for JS to target.' );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should be initially hidden for JS to reveal.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-payment-element"', $html, 'Payment element container should have ID for JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-errors"', $html, 'Errors container should have ID for JS error handling.' );
		$this->assertStringContainsString( 'role="alert"', $html, 'Errors container should have alert role for accessibility.' );
	}

	/**
	 * Test that payment element button is always present for JS to reveal after billing collected.
	 *
	 * @test
	 */
	public function it_should_always_render_button_for_js_to_reveal() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => true,
		] );

		$this->assertStringContainsString( '<button', $html, 'Button element should be present in DOM.' );
		$this->assertStringContainsString( 'tribe-common-c-btn', $html, 'Button should have base class for styling.' );
		$this->assertStringContainsString( 'tribe-tickets__commerce-checkout-form-submit-button', $html, 'Button should have submit class.' );
		$this->assertStringContainsString( 'id="spinner"', $html, 'Button should have spinner element for loading state.' );
		$this->assertStringContainsString( 'id="button-text"', $html, 'Button should have text element for JS to update.' );
	}

	/**
	 * Test that payment element has payment message container for JS updates.
	 *
	 * @test
	 */
	public function it_should_have_payment_message_container() {
		$shortcode = tribe( Checkout_Shortcode::class );

		$html = $this->get_partial_html( [
			'shortcode'        => $shortcode,
			'must_login'       => false,
			'payment_element'  => true,
		] );

		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-payment-message"', $html, 'Should have payment message container for JS.' );
		$this->assertStringContainsString( 'class="hidden"', $html, 'Payment message should be hidden initially.' );
	}
}
