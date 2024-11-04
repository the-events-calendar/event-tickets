<?php

namespace Tec\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;

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
		
		$this->assertArrayHasKey( 'ticket-seating-frontend-timer', $settings );
		$this->assertMatchesJsonSnapshot( wp_json_encode( $settings, JSON_SNAPSHOT_OPTIONS ) );
	}
}
