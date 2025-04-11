<?php
/**
 * Activate The Events Calendar button template.
 *
 * @since TBD
 *
 * @var \Tribe\Tickets\Admin\Onboarding\Template  $this      The template instance.
 * @var \TEC\Common\StellarWP\Installer\Installer $installer The installer instance.
 */

use TEC\Common\StellarWP\Installer\Installer;

Installer::get()->render_plugin_button(
	'the-events-calendar',
	'activate',
	__( 'Activate', 'event-tickets' ),
	admin_url( 'admin.php?page=tickets-setup' )
);
