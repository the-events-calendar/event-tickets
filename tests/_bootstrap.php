<?php
/**
 * @file Global bootstrap for all codeception tests
 */
use Codeception\Util\Autoload;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Extensions\Suite_Env;
use TEC\Common\Tests\Filters;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\RSVP\Controller as RSVP_Controller;

Autoload::addNamespace( 'Tribe__Events__WP_UnitTestCase', __DIR__ . '/_support' );
Autoload::addNamespace( 'Tribe\Tickets\Test', __DIR__ . '/_support' );
Autoload::addNamespace( 'TEC\Tickets\Test', __DIR__ . '/_support' );
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

function tec_tickets_tests_add_manual_gateway_id( $args ) {
	$args['gateway_order_id'] = md5( wp_generate_password() . microtime() );

	return $args;
}

function tec_tickets_tests_enable_gateway_id_generation() {
	add_filter( 'tec_tickets_commerce_order_create_args', 'tec_tickets_tests_add_manual_gateway_id' );
}

function tec_tickets_tests_disable_gateway_id_generation() {
	remove_filter( 'tec_tickets_commerce_order_create_args', 'tec_tickets_tests_add_manual_gateway_id' );
}

function tec_tickets_tests_global_rest_route_registration_listener() {
	uopz_set_return( 'register_rest_route', function( $route_namespace, $route, $args = array(), $override = false ) {
		if ( isset( $args['schema'] ) && ! is_callable( $args['schema'] ) ) {
			throw new \Exception( 'Schema must be a callable! Fix your route registration: ' . $route_namespace . ' ' . $route );
		}

		if ( ! isset( $args['args'] ) ) {
			foreach ( $args as $arg ) {
				if ( ! is_array( $arg ) ) {
					continue;
				}

				if ( isset( $arg['schema'] ) && ! is_callable( $arg['schema'] ) ) {
					throw new \Exception( 'Schema must be a callable! Fix your route registration: ' . $route_namespace . ' ' . $route );
				}
			}
		}
		return register_rest_route( $route_namespace, $route, $args, $override );
	}, true );
}

tec_tickets_tests_global_rest_route_registration_listener();

// In the context of the RSVP v2 suite testing, activate the RSVP v2 feature.
Suite_Env::module_init( 'rsvp_v2_integration', static function (): void {
	// Enable Tickets Commerce
	Filters::add_pre_initialized_filter( 'tec_tickets_commerce_is_enabled', fn() => true, 0 );
	Filters::add_pre_initialized_filter( 'tec_tickets_rsvp_version', fn() => RSVP_Controller::VERSION_2, 0 );
} );
