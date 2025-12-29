<?php

namespace TEC\Tickets\RSVP\V2;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;

class Controller_Test extends Controller_Test_Case {
	use SnapshotAssertions;

	protected string $controller_class = Controller::class;

	public static function change_tickets_commerce_settings_provider(): array {
		return [
			'empty fields' => [ [], [] ],

			'with fields, but target missing' => [
				[
					'tec-settings-payment-something' => [
						'type' => 'html',
						'html' => '<div>Test</div>'
					],
				],
				[
					'tec-settings-payment-something' => [
						'type' => 'html',
						'html' => '<div>Test</div>'
					],
				],
			],

			'with fields and target' => [
				[
					'tec-settings-payment-enable' => [
						'type' => 'html',
						'html' => '<div>Test</div>'
					],
				],
				[
					'tec-settings-payment-enable' => [
						'type' => 'html',
						'html' => '<div>Test</div>'
					],
				],
			]
		];
	}

	public function test_change_tickets_commerce_settings_changes_nothing_when_target_missing(): void {
		$controller = $this->make_controller();
		$actual     = $controller->change_tickets_commerce_settings(
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

	public function change_tickets_commerce_settings_changes_target(): void {
		$controller = $this->make_controller();
		$actual     = $controller->change_tickets_commerce_settings(
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
