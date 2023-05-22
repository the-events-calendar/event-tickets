<?php

use Tribe\Tickets\Editor\Warnings;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;

class WarningsTest extends \Codeception\TestCase\WPTestCase {

	use MatchesSnapshots;


	/**
	 * @test
	 * @dataProvider notice_data_provider
	 */
	public function render_notice_display_correctly( $message, $type, $depends_on, $condition, $additionalClasses ) {
		$warnings = new Warnings();

		ob_start();
		$warnings->render_notice( $message, $type, $depends_on, $condition, $additionalClasses );

		$rendered = ob_get_clean();
		codecept_debug( $rendered );
		$this->assertMatchesSnapshot( $rendered );
	}

	/**
	 * Data provider for render_notice_display_correctly test.
	 *
	 * @return Generator
	 */
	public function notice_data_provider() {
		yield 'Test with blank message' => [
			'message' => '',
			'type' => 'info',
			'depends_on' => '',
			'condition' => '',
			'additionalClasses' => []
		];

		yield 'Test with blank type' => [
			'message' => 'Test message',
			'type' => '',
			'depends_on' => '',
			'condition' => '',
			'additionalClasses' => []
		];

		yield 'Test with blank depends_on' => [
			'message' => 'Test message',
			'type' => 'info',
			'depends_on' => '',
			'condition' => '',
			'additionalClasses' => []
		];

		yield 'Test with blank condition' => [
			'message' => 'Test message',
			'type' => 'info',
			'depends_on' => '',
			'condition' => '',
			'additionalClasses' => []
		];

		yield 'Test with blank additionalClasses' => [
			'message' => 'Test message',
			'type' => 'info',
			'depends_on' => '',
			'condition' => '',
			'additionalClasses' => []
		];

		yield 'Test with all values filled' => [
			'message' => 'Another message',
			'type' => 'warning',
			'depends_on' => '#element-id',
			'condition' => 'checked',
			'additionalClasses' => [ 'class1', 'class2' ]
		];

	}
}