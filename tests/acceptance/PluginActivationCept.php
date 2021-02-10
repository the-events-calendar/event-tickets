<?php
$I = new AcceptanceTester( $scenario );
$I->wantTo( 'activate Event Tickets on a fresh WordPress installation and deactivate it' );

// set the `active_plugins` in the database to an empty array to make sure no plugin is active
// by default the database dump has Event Tickets active
// $I->haveOptionInDatabase( 'active_plugins', [] );

$I->loginAsAdmin();
$I->amOnPluginsPage();
$I->seePluginDeactivated( 'event-tickets' );

$I->activatePlugin( 'event-tickets' );

// to get back to the plugins page if redirected after the plugin activation
$I->amOnPluginsPage();

$I->seePluginActivated( 'event-tickets' );

$I->deactivatePlugin( 'event-tickets' );

// to get back to the plugins page if redirected after the plugin activation
$I->amOnPluginsPage();

$I->seePluginDeactivated( 'event-tickets' );
