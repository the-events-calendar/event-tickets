<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

/**
 * Test the Card Element template rendering.
 *
 * @since TBD
 */
class Card_Element_Template_Test extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'gateway/stripe/card-element';

	/**
	 * Test that the checkout button is initially hidden when the compact card element type is used.
	 *
	 * @test
	 */
	public function it_should_render_compact_card_element_with_hidden_button() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$this->assertMatchesHtmlSnapshot( $html );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should have hidden class initially.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID.' );
	}

	/**
	 * Test that the checkout button is initially hidden when the multiple fields card element type is used.
	 *
	 * @test
	 */
	public function it_should_render_separate_card_element_with_hidden_button() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::SEPARATE_CARD_ELEMENT_SLUG,
		] );

		$this->assertMatchesHtmlSnapshot( $html );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should have hidden class initially.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID.' );
		$this->assertStringContainsString( 'tribe-tickets__commerce-checkout-stripe-card-element--separate', $html, 'Should have separate class modifier.' );
	}

	/**
	 * Test that card element doesn't render when must_login is true.
	 *
	 * @test
	 */
	public function it_should_not_render_when_must_login() {
		$html = $this->get_partial_html( [
			'must_login'        => true,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$this->assertEmpty( $html, 'Template should not render when must_login is true.' );
	}

	/**
	 * Test that card element doesn't render when billing info is still not collected.
	 *
	 * @test
	 */
	public function it_should_not_render_when_payment_element_true() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => true,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$this->assertEmpty( $html, 'Template should not render when payment_element is true.' );
	}

	/**
	 * Test that compact card element has correct structure for JavaScript interaction.
	 *
	 * @test
	 */
	public function it_should_have_correct_structure_for_js_with_compact() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID for JS to target.' );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should be initially hidden for JS to reveal.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-element"', $html, 'Card element container should have ID for JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-errors"', $html, 'Errors container should have ID for JS error handling.' );
		$this->assertStringContainsString( 'role="alert"', $html, 'Errors container should have alert role for accessibility.' );
	}

	/**
	 * Test that separate card element has correct structure for JavaScript interaction.
	 *
	 * @test
	 */
	public function it_should_have_correct_structure_for_js_with_separate() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::SEPARATE_CARD_ELEMENT_SLUG,
		] );

		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html, 'Button should have correct ID for JS to target.' );
		$this->assertStringContainsString( 'tribe-common-a11y-hidden', $html, 'Button should be initially hidden for JS to reveal.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-element"', $html, 'Card element container should have ID for JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-number"', $html, 'Card number field should have ID for Stripe JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-expiry"', $html, 'Expiry field should have ID for Stripe JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-cvc"', $html, 'CVC field should have ID for Stripe JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-zip"', $html, 'Zip field should have ID for Stripe JS.' );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-errors"', $html, 'Errors container should have ID for JS error handling.' );
		$this->assertStringContainsString( 'role="alert"', $html, 'Errors container should have alert role for accessibility.' );
	}

	/**
	 * Test that checkout button is always present for JS to reveal after billing collected.
	 *
	 * @test
	 */
	public function it_should_always_render_button_for_js_to_reveal() {
		$compact_html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$separate_html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::SEPARATE_CARD_ELEMENT_SLUG,
		] );

		$this->assertStringContainsString( '<button', $compact_html, 'Button element should be present in compact layout.' );
		$this->assertStringContainsString( '<button', $separate_html, 'Button element should be present in separate layout.' );
		$this->assertStringContainsString( 'tribe-common-c-btn', $compact_html, 'Button should have base class for styling.' );
		$this->assertStringContainsString( 'tribe-common-c-btn', $separate_html, 'Button should have base class for styling.' );
		$this->assertStringContainsString( 'tribe-tickets__commerce-checkout-form-submit-button', $compact_html, 'Button should have submit class.' );
		$this->assertStringContainsString( 'tribe-tickets__commerce-checkout-form-submit-button', $separate_html, 'Button should have submit class.' );
	}
}
