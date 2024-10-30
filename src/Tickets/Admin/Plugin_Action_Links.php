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

		// Add 5-star review link.
		add_filter( 'plugin_row_meta', [ $this, 'add_links_to_plugin_meta' ], 10, 2 );
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

	/**
	 * Add links to plugin meta.
	 *
	 * @since 5.9.1
	 *
	 * @param array  $plugin_meta The array with the links on the plugin meta.
	 * @param string $plugin_file Path to the plugin file.
	 *
	 * @return array $plugin_meta An array of the plugin's metadata.
	 */
	public function add_links_to_plugin_meta( $plugin_meta, $plugin_file ) {
		if ( trailingslashit( \Tribe__Tickets__Main::instance()->plugin_dir ) . 'event-tickets.php' !== $plugin_file ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="https://evnt.is/et-docs-plugin-list-meta" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Docs', 'event-tickets' )
			. '</a>';

		$plugin_meta[] = '<a href="https://wordpress.org/support/plugin/event-tickets/reviews/?filter=5" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Leave a review', 'event-tickets' )
			. '</a>';

		return $plugin_meta;
	}
}
