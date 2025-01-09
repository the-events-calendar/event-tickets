<?php
/**
 * @file Global bootstrap for all codeception tests
 */
use Codeception\Util\Autoload;
use TEC\Tickets\Commerce\Order;
use TEC\Common\StellarWP\DB\DB;

Autoload::addNamespace( 'Tribe__Events__WP_UnitTestCase', __DIR__ . '/_support' );
Autoload::addNamespace( 'Tribe\Tickets\Test', __DIR__ . '/_support' );
Autoload::addNamespace( 'TEC\Tickets\Tests', __DIR__ . '/_support' );

$common_tests_dir = __DIR__ . '/../common/tests/';
$common_support_dir = $common_tests_dir . '/_support';

require_once $common_support_dir . '/Helper/TECDb.php';

Autoload::addNamespace( 'Tribe\Tests', $common_support_dir );
Autoload::addNamespace( '\\TEC\\Common\\Tests', $common_support_dir );

if( ! defined( 'TRIBE_TESTS_HOME_URL' ) ) {
	/**
	 * Snapshots URL to compare to home_url().
	 *
	 * Added to reduce complexity and avoid having to regenerate snapshots simply from switching testing environments.
	 * If value ever changes, keep in sync with `tests/data/restv1-dump.sql`.
	 */
	define( 'TRIBE_TESTS_HOME_URL', 'http://wordpress.test/' );
}

/**
 * Codeception will regenerate snapshots on `--debug`, while the `spatie/snapshot-assertions`
 * library will do the same on `--update-snapshots`.
 * Since Codeception has strict check on the CLI arguments appending `--update-snapshots` to the
 * `vendor/bin/codecept run` command will throw an error.
 * We handle that intention here.
 */
if ( isset( $_SERVER['argv'] ) && in_array( '--debug', $_SERVER['argv'], true ) ) {
	$_SERVER['argv'][] = '--update-snapshots';
}

// By default, do not enable the Custom Tables v1 implementation in tests.
putenv( 'TEC_CUSTOM_TABLES_V1_DISABLED=1' );
$_ENV['TEC_CUSTOM_TABLES_V1_DISABLED'] = 1;

// Fake Order Instance's lock mechanism without actually using DB transactions to avoid leaking in tests.
$lock_key = 'post_content_filtered';

uopz_set_return( Order::class, 'lock_order', function ( int $order_id ) use ( $lock_key ) {
	tribe( Order::class )->generate_lock_id();

	return (bool) DB::query(
		DB::prepare(
			"UPDATE %i set $lock_key = %s where ID = $order_id and $lock_key = ''",
			DB::prefix( 'posts' ),
			tribe( Order::class )->get_lock_id()
		)
	);
}, true );

uopz_set_return( Order::class, 'unlock_order', function ( int $order_id ) use ( $lock_key ){
	return (bool) DB::query(
		DB::prepare(
			"UPDATE %i set $lock_key = '' where ID = $order_id and $lock_key = %s",
			DB::prefix( 'posts' ),
			tribe( Order::class )->get_lock_id()
		)
	);
}, true );
