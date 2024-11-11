<?php

namespace TEC\Tickets\Seating\Tables;

use Exception;
use lucatume\WPBrowser\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Tests\Integration\Truncates_Custom_Tables;
use Tribe\Tests\Traits\With_Uopz;

class Sessions_Test extends WPTestCase {
	use Truncates_Custom_Tables;
	use With_Uopz;

	public function test_set_token_expiration_timestamp(): void {
		$sessions = tribe( Sessions::class );

		$initial_expiration = time() + 600;

		$sessions->upsert( 'test-token', 23, $initial_expiration );

		$this->assertEqualsWithDelta( 600, $sessions->get_seconds_left( 'test-token' ), 3 );

		$sessions->set_token_expiration_timestamp( 'test-token', 0 );

		$this->assertEquals( 0, $sessions->get_seconds_left( 'test-token' ) );

		$sessions->set_token_expiration_timestamp( 'test-token', time() + 23 );

		$this->assertEqualsWithDelta( 23, $sessions->get_seconds_left( 'test-token' ), 3 );
	}
}
