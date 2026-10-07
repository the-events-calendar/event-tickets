<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;

/**
 * The Recurrence tier registers by itself when ECP's custom tables are active.
 */
class Recurrence_Tier_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_register_once_ecp_custom_tables_are_fully_active(): void {
		$this->assertSame( 1, did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) );
		$this->assertTrue( Recurrence_Controller::is_registered() );
	}
}
