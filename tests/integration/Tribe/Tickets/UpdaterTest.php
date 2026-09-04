<?php

namespace Tribe\Tickets;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Tribe__Settings_Manager;
use Tribe__Tickets__Main;

class UpdaterTest extends WPTestCase {

	/**
	 * @test
	 * @covers Tribe__Tickets__Updater::flush_key_value_cache
	 * @covers Tribe__Tickets__Updater::get_constant_update_callbacks
	 */
	public function should_flush_the_key_value_cache_on_update(): void {
		$key = 'tec_tickets_updater_test_entry';
		tec_kv_cache()->set( $key, 'cached value', DAY_IN_SECONDS );

		$this->assertTrue( tec_kv_cache()->has( $key ) );

		/* Any version below the current one is what makes the updater treat the install as out of date. */
		Tribe__Settings_Manager::set_option( 'event-tickets-schema-version', '0.1.0' );

		Tribe__Tickets__Main::instance()->run_updates();

		$this->assertFalse( tec_kv_cache()->has( $key ) );
		$this->assertEquals(
			Tribe__Tickets__Main::VERSION,
			Tribe__Settings_Manager::get_option( 'event-tickets-schema-version' ),
			'The update should have run to completion, not bailed before the flush.'
		);
	}
}
