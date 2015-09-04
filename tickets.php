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

define( 'TRIBE_TICKETS_DIR', dirname( __FILE__ ) );

// the main plugin class
require_once TRIBE_TICKETS_DIR . '/src/Tribe/Main.php';

// This needs to happen before Tickets PRO
add_action( 'plugins_loaded', array( 'Tribe__Tickets__Main', 'instance' ), 5 );
