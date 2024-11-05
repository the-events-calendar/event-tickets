<?php

namespace Tec\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Frontend\Timer;

class Settings_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	
	protected string $controller_class = Settings::class;
	
	public function test_seating_reservation_setting_is_included_after_login_requirements() {
		$this->make_controller()->register();
		
		$settings = apply_filters(
			'tribe_tickets_settings_tab_fields',
			[
				'ticket-authentication-requirements' => [
					'type'  => 'checkbox',
					'label' => 'Require Login',
				],
			] 
		);
		
		$this->assertArrayHasKey( 'tickets-seating-timer-limit', $settings );
		$this->assertMatchesJsonSnapshot( wp_json_encode( $settings, JSON_SNAPSHOT_OPTIONS ) );
	}
	
	public function test_saved_reservation_timer_is_used() {
		$this->make_controller()->register();
		
		// Default value is 15 minutes.
		$timeout = tribe( Timer::class )->get_timeout( 0 );
		$this->assertEquals( 15 * 60, $timeout );
		
		// Update the value to 30 minutes.
		tribe_update_option( 'tickets-seating-timer-limit', 30 );
		$timeout = tribe( Timer::class )->get_timeout( 0 );
		
		$this->assertEquals( 30 * 60, $timeout );
	}
}
