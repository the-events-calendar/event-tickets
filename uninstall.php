<?php
/**
 * Event Tickets uninstall script.
 *
 * @since TBD
 */

declare( strict_types=1 );

use TEC\Tickets\Order_Modifiers\Controller as Order_Modifier_Controller;
use Tribe__Tickets__Main as Main;

// Ensure the uninstall script is not called directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Ensure that uninstallation happens deliberately, via an option or a constant.
$should_uninstall = get_option( 'event_tickets_uninstall', defined( 'EVENT_TICKETS_UNINSTALL' ) && EVENT_TICKETS_UNINSTALL );
if ( ! $should_uninstall ) {
	return;
}

// Get the custom table controller class instance.
$main_instance = Main::instance();

// Remove the actions that were added in the constructor.
remove_action( 'plugins_loaded', [ $main_instance, 'should_autoload' ], -1 );
remove_action( 'plugins_loaded', [ $main_instance, 'plugins_loaded' ], 0 );

// Determine should_autoload().
$main_instance->should_autoload();

// Init the autoloader.
$init_autoloader = Closure::bind(
	function () {
		$this->init_autoloading();
	},
	$main_instance,
	$main_instance
);
$init_autoloader();

// Get the instance of the order modifier controller.
tribe_register_provider( Order_Modifier_Controller::class );
$instance = tribe( Order_Modifier_Controller::class );

// Drop the custom tables.
$instance->drop_tables();
