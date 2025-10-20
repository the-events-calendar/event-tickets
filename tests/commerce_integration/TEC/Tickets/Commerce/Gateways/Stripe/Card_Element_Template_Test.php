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
	 * Test that card element renders with hidden button for compact type.
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
	 * Test that card element renders with hidden button for separate type.
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
	 * Test that card element doesn't render when payment_element is true.
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
	 * Test that card element has correct structure.
	 *
	 * @test
	 */
	public function it_should_render_with_correct_structure() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		// Check for card element container.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-element"', $html );

		// Check for button.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-checkout-button"', $html );

		// Check for errors container.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-errors"', $html );
	}

	/**
	 * Test that separate card elements have individual field containers.
	 *
	 * @test
	 */
	public function it_should_render_separate_fields_for_separate_type() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::SEPARATE_CARD_ELEMENT_SLUG,
		] );

		// Check for individual field containers.
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-number"', $html );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-expiry"', $html );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-cvc"', $html );
		$this->assertStringContainsString( 'id="tec-tc-gateway-stripe-card-zip"', $html );
	}

	/**
	 * Test that compact class modifier is applied correctly.
	 *
	 * @test
	 */
	public function it_should_apply_compact_class_modifier() {
		$html = $this->get_partial_html( [
			'must_login'        => false,
			'payment_element'   => false,
			'card_element_type' => Settings::COMPACT_CARD_ELEMENT_SLUG,
		] );

		$this->assertStringContainsString( 'tribe-tickets__commerce-checkout-stripe-card-element--compact', $html, 'Should have compact class modifier.' );
	}
}
