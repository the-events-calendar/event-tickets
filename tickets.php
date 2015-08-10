<?php
/*
Plugin Name: The Events Calendar: Tickets
Description: The Events Calendar: Tickets allows you to sell tickets to events
Version: 3.9
Author: Modern Tribe, Inc.
Author URI: http://m.tri.be/28
License: GPLv2 or later
Text Domain: tribe-tickets
Domain Path: /lang/
 */

/*
 Copyright 2010-2012 by Modern Tribe Inc and the contributors

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// This needs to happen before Tickets PRO
add_action( 'plugins_loaded', 'tribe_tickets_init', 5 );


function tribe_tickets_init() {

	if ( ! class_exists( 'Tribe__Events__Main' ) ) {
		return;
	}

	tribe_init_tickets_autoloading();

	load_plugin_textdomain( 'tribe-tickets', false, trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/' );

	add_action( 'add_meta_boxes',        array( 'Tribe__Events__Tickets__Metabox', 'maybe_add_meta_box' ) );
	add_action( 'admin_enqueue_scripts', array( 'Tribe__Events__Tickets__Metabox', 'add_admin_scripts'  ) );

	new Tribe__Events__Tickets__RSVP();
}

/**
 * Requires the autoloader class from the main plugin class and sets up
 * autoloading.
 */
function tribe_init_tickets_autoloading() {
	if ( ! class_exists( 'Tribe__Events__Autoloader' ) ) {
		return;
	}
	$autoloader = Tribe__Events__Autoloader::instance();

	$autoloader->register_prefix( 'Tribe__Events__Tickets__', dirname( __FILE__ ) . '/src/Tribe/Tickets' );

	require_once dirname( __FILE__ ) . '/src/template-tags/tickets.php';

	foreach ( glob( dirname( __FILE__ ) . '/src/deprecated/*.php' ) as $file ) {
		$class_name = str_replace( '.php', '', basename( $file ) );
		$autoloader->register_class( $class_name, $file );
	}

	$autoloader->register_autoloader();
}


