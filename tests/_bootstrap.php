<?php
/**
 * @file Global bootstrap for all codeception tests
 */

Codeception\Util\Autoload::addNamespace( 'Tribe__Events__WP_UnitTestCase', __DIR__ . '/_support' );
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', __DIR__ . '/_support' );
Codeception\Util\Autoload::addNamespace( 'Tribe\Tickets\Test', __DIR__ . '/_support' );

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
if ( in_array( '--debug', $_SERVER['argv'], true ) ) {
	$_SERVER['argv'][] = '--update-snapshots';
}
