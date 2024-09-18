<?php
/**
 * Event Tickets uninstall script.
 *
 * @since TBD
 */

declare( strict_types=1 );

use TEC\Tickets\Order_Modifiers\Controller as Order_Modifier_Controller;

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
tribe_register_provider( Order_Modifier_Controller::class );
$instance = tribe( Order_Modifier_Controller::class );

// Drop the custom tables.
$instance->drop_tables();
