<?php

use phpDocumentor\Reflection\PseudoTypes\True_;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Admin\Settings;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class NoticeTest extends Codeception\TestCase\WPTestCase {
	use With_Uopz;
	use SnapshotAssertions;

	/**
	 * @before
	 */
	public function setup_singletons() {
		tribe()->singleton( 'tickets.admin.notice', new Tribe__Tickets__Admin__Notices() );
	}

	/**
	 * @test
	 *
	 * @dataProvider fse_ar_page_notice_provider
	 */
	public function test_fse_ar_page_notice( $settings, $is_empty ) {
		$_GET['page'] = $settings['page'];
		$this->set_fn_return( 'is_admin', $settings['is_admin'] );
		$this->set_fn_return( 'has_action', $settings['has_action'] );
		$this->set_fn_return( 'wp_is_block_theme', $settings['wp_is_block_theme'] );

		// Mimick AR page being set with an existing page. This should cause notice not to be set.
		$this->set_fn_return( 'get_post_status', $settings['page_status'] );
		Tribe__Settings_Manager::set_option( 'ticket-attendee-page-id', $settings['page_id'] );

		$admin_notice = tribe( 'tickets.admin.notice' );
		$admin_notice->maybe_display_fse_ar_page_notice();

		$notice = Tribe__Admin__Notices::instance()->get( 'tec-tickets-ar-page-with-fse-theme' );

		$this->assertEquals( $is_empty, empty( $notice ) );
	}

	public function fse_ar_page_notice_provider() {
		return [
			'ar page set' => [
				[
					'page' => Settings::$settings_page_id,
					'is_admin' => true,
					'has_action' => true,
					'wp_is_block_theme' => true,
					'page_status' => 'publish',
					'page_id' => 1,
				],
				true,
			],
			'incorrect page id' => [
				[
					'page' => 'incorrect-page-id',
					'is_admin' => true,
					'has_action' => true,
					'wp_is_block_theme' => true,
					'page_status' => false,
					'page_id' => 0,
				],
				true,
			],
			'not block theme' => [
				[
					'page' => Settings::$settings_page_id,
					'is_admin' => true,
					'has_action' => true,
					'wp_is_block_theme' => false,
					'page_status' => false,
					'page_id' => 0,
				],
				true,
			],
			'notice should show' => [
				[
					'page' => Settings::$settings_page_id,
					'is_admin' => true,
					'has_action' => true,
					'wp_is_block_theme' => true,
					'page_status' => false,
					'page_id' => 0,
				],
				false,
			],
		];
	}
}
