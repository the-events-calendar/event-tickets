<?php

namespace TEC\Tickets\Admin;

use Tribe\Tickets\Admin\Settings as Plugin_Settings;

/**
 * Class Plugin_Action_Links
 *
 * @since 5.4.1
 *
 * @package TEC\Tickets\Admin
 */
class Plugin_Action_Links {

	/**
	 * Method to register plugin action links related hooks.
	 *
	 * @since 5.4.1
	 */
	public function hooks() {
		add_action(
			'plugin_action_links_' . trailingslashit( \Tribe__Tickets__Main::instance()->plugin_dir ) . 'event-tickets.php',
			[
				$this,
				'add_links_to_plugin_actions',
			]
		);
	}

	/**
	 * Add links to plugin actions.
	 *
	 * @since 5.4.1
	 *
	 * @param array $actions The array with the links on the plugin actions.
	 *
	 * @return array $actions The modified array with the links.
	 */
	public function add_links_to_plugin_actions( $actions ) {
		$actions['tec-tickets-settings']        = '<a href="' . tribe( Plugin_Settings::class )->get_url() . '">' . esc_html__( 'Settings', 'event-tickets' ) . '</a>';
		$actions['tec-tickets-getting-started'] = '<a href="https://evnt.is/1aot" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Getting started', 'event-tickets' ) . '</a>';

		return $actions;
	}
}
