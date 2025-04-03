<?php
/**
 * @file Global bootstrap for all codeception tests
 */

use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Classy\Controller as Classy;
use TEC\Tickets\Commerce\Order;

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

function tec_tickets_tests_fake_transactions_enable() {
	uopz_set_return( DB::class, 'beginTransaction', true, false );
	uopz_set_return( DB::class, 'rollback', function () {
		// On rollback we want to clear any locks and reset the lock id back to empty in runtime.
		DB::query( DB::prepare( 'UPDATE %i SET post_content_filtered=""', DB::prefix( 'posts' ) ) );
		tribe( Order::class )->reset_lock_id();
	}, true );
	uopz_set_return( DB::class, 'commit', true, false );
}

function tec_tickets_tests_fake_transactions_disable() {
	uopz_unset_return( DB::class, 'beginTransaction' );
	uopz_unset_return( DB::class, 'rollback' );
	uopz_unset_return( DB::class, 'commit' );
}

function tec_tickets_tests_disable_and_unregister_classy_editor() {
	/** @var Tribe__Container $container */
	$container = tribe();

	// If Classy isn't registered, or the container doesn't have Classy, we don't need to do anything.
	if ( ! $container->getVar( Classy::class . '_registered' ) || ! $container->has( Classy::class ) ) {
		return;
	}

	$classy = $container->get( Classy::class );

	// Unregister Classy.
	$classy->unregister();

	// Unset the _registered var in the container.
	unset( $container[ Classy::class . '_registered' ] );

	// Set the classy filter to false.
	add_filter( 'tec_using_classy_editor', [ Classy::class, 'return_false' ], 100 );

	// Register the old editor.
	$container->register( Tribe__Editor__Provider::class );
}
