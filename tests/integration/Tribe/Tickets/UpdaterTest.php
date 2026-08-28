<?php

namespace Tribe\Tickets;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Tribe__Tickets__Main;
use Tribe__Tickets__Updater;

class UpdaterTest extends WPTestCase {

	/**
	 * @test
	 * @covers Tribe__Tickets__Updater::flush_key_value_cache
	 */
	public function should_flush_the_key_value_cache(): void {
		$key = 'tec_tickets_updater_test_entry';
		tec_kv_cache()->set( $key, 'cached value', DAY_IN_SECONDS );

		$this->assertTrue( tec_kv_cache()->has( $key ) );

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->flush_key_value_cache();

		$this->assertFalse( tec_kv_cache()->has( $key ) );
	}

	/**
	 * @test
	 * @covers Tribe__Tickets__Updater::get_constant_update_callbacks
	 */
	public function should_flush_the_key_value_cache_on_every_version_update(): void {
		$updater = new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION );

		$this->assertContains( 'flush_key_value_cache', array_column( $updater->get_constant_update_callbacks(), 1 ) );
	}
}
