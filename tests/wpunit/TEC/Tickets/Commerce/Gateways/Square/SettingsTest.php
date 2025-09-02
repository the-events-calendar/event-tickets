<?php
/**
 * Test the Square Settings class for fee message display.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use Codeception\TestCase\WPTestCase;

/**
 * Class SettingsTest
 *
 * @since TBD
 */
class SettingsTest extends WPTestCase {

	/**
	 * Test that the 2% fee message is displayed in Square settings when ETP is not active.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_display_fee_message_when_etp_is_not_active() {
		$this->assertFalse(
			class_exists( 'Tribe__Tickets_Plus__PUE' ),
			'Event Tickets Plus should not be active for this test'
		);

		$settings = tribe( Settings::class );

		$settings_array = $settings->get_settings();

		$this->assertArrayHasKey(
			'tickets-commerce-square-commerce-description',
			$settings_array,
			'Square settings should contain the fee description field when ETP is not active'
		);

		$fee_description = $settings_array['tickets-commerce-square-commerce-description'];

		$this->assertEquals(
			'html',
			$fee_description['type'],
			'Fee description should be an HTML field type'
		);

		$this->assertStringContainsString(
			'You are using the free Square payment gateway integration',
			$fee_description['html'],
			'Fee description should mention the free Square integration'
		);
	}
}
