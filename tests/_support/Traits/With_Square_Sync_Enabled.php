<?php

namespace Tribe\Tickets\Test\Traits;

use PHPUnit\Framework\Assert;
use TEC\Tickets\Commerce\Gateways\Square\Settings;

trait With_Square_Sync_Enabled {

	/**
	 * @before
	 */
	protected function enable_square_sync(): void {
		Assert::assertFalse( tribe( Settings::class )->is_inventory_sync_enabled() );
		tribe_update_option( Settings::OPTION_INVENTORY_SYNC, true );
		Assert::assertTrue( tribe( Settings::class )->is_inventory_sync_enabled() );
	}

	/**
	 * @after
	 */
	protected function disable_square_sync(): void {
		Assert::assertTrue( tribe( Settings::class )->is_inventory_sync_enabled() );
		tribe_remove_option( Settings::OPTION_INVENTORY_SYNC );
		Assert::assertFalse( tribe( Settings::class )->is_inventory_sync_enabled() );
	}
}
