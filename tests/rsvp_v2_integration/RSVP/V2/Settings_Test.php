<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Settings_Test extends WPTestCase {
	use SnapshotAssertions;

	public function test_change_tickets_commerce_settings_changes_nothing_when_target_missing(): void {
		$settings = new Settings();
		$actual   = $settings->change_tickets_commerce_settings(
			[
				'tec-settings-payment-something' => [
					'type' => 'html',
					'html' => '<div>Test</div>'
				],
			]
		);

		$this->assertEquals( [
			'tec-settings-payment-something' => [
				'type' => 'html',
				'html' => '<div>Test</div>'
			],
		], $actual );
	}

	public function test_change_tickets_commerce_settings_changes_target(): void {
		$settings = new Settings();
		$fields   = $settings->change_tickets_commerce_settings(
			[
				'tec-settings-payment-something' => [
					'type' => 'html',
					'html' => '<div>Test</div>'
				],
				'tec-settings-payment-enable'    => [
					'type' => 'html',
					'html' => '<div>Test</div>'
				],
			]
		);

		$this->assertArrayHasKey( 'tec-settings-payment-enable', $fields );
		$this->assertArrayHasKey( 'type', $fields['tec-settings-payment-enable'] );
		$this->assertEquals( 'html', $fields['tec-settings-payment-enable']['type'] );
		$this->assertArrayHasKey( 'html', $fields['tec-settings-payment-enable'] );
		$this->assertMatchesHtmlSnapshot( $fields['tec-settings-payment-enable']['html'] );
	}
}
