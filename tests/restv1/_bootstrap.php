<?php
// This file runs at SUITE_INIT time.

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use lucatume\WPBrowser\Events\Dispatcher;
use lucatume\WPBrowser\Module\WPFilesystem;

/**
 * Retrieves the WPFilesystem module instance from the current suite.
 *
 * @param SuiteEvent $event The suite event to retrieve the module from.
 *
 * @return WPFilesystem The WPFilesystem module instance.
 *
 * @throws RuntimeException If the WPFilesystem module is not found.
 */
function get_wpfilesystem_module( SuiteEvent $event ): WPFilesystem {
	$fsModules = array_filter(
		$event->getSuite()->getModules(),
		static fn( $module ) => $module instanceof WPFilesystem
	);

	if ( empty( $fsModules ) ) {
		throw new RuntimeException( 'WPFilesystem module not found' );
	}

	/** @var WPFilesystem $fsModule */
	$fsModule = reset( $fsModules );

	return $fsModule;
}

// The path that will store the mu-plugin file path.
$mu_plugin_file = null;

/*
 * After the suite initialized, all modules are loaded, and no tests ran yet, place the must-use
 * plugin that will force the RSVP version to be v1.
 */
Dispatcher::addListener(
	Events::SUITE_BEFORE,
	static function ( SuiteEvent $event ) use ( &$mu_plugin_file ) {
		$force_rsvp_v1_mu_plugin_code = <<< PHP
		<?php
		/**
		 * Plugin Name: Force RSVP v1
		 */

		use TEC\Tickets\RSVP\Controller as RSVP_Controller;

		add_filter( 'tec_tickets_rsvp_version', function (): string {
			return get_option( 'test_rsvp_version', RSVP_Controller::VERSION_1 );
		} );
		PHP;
		$fsModule                     = get_wpfilesystem_module( $event );
		$fsModule->writeToMuPluginFile( 'force_rsvp_v1.php', $force_rsvp_v1_mu_plugin_code );
		$mu_plugin_file = $fsModule->_getConfig( 'mu-plugins' ) . 'force_rsvp_v1.php';
	}
);

// After the suite completed, remove the must-use plugin.
Dispatcher::addListener(
	Events::SUITE_AFTER,
	static function ( SuiteEvent $event ) use ( &$mu_plugin_file ) {
		$fsModule = get_wpfilesystem_module( $event );
		$fsModule->deleteFile( $mu_plugin_file );
	}
);
