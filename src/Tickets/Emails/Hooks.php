<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Emails\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.emails.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Emails\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.emails.hooks' ), 'some_method' ] );
 *
 * @since   5.5.6
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \tad_DI52_ServiceProvider;

/**
 * Class Hooks.
 *
 * @since   5.5.6
 *
 * @package TEC\Tickets\Emails
 */
class Hooks extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.5.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Emails component.
	 *
	 * @since 5.5.6
	 */
	protected function add_actions() {
		add_action( 'tribe_settings_do_tabs', [ $this, 'register_emails_tab' ], 17 );
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since 5.5.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_add_tab_id' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_template_list' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_sender_info_fields' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_email_styling_fields' ] );
	}

	/**
	 * Action to add emails tab to tickets settings page.
	 *
	 * @since 5.5.6
	 *
	 * @param $admin_page Page ID of current admin page.
	 */
	public function register_emails_tab( $admin_page ) {
		$this->container->make( Emails_Tab::class )->register_tab( $admin_page );
	}

	/**
	 * Filter to add tab id to tickets emails tab.
	 *
	 * @since 5.5.6
	 *
	 * @param  array $tabs Current array of tabs ids.
	 *
	 * @return array $tabs Filtered array of tabs ids.
	 */
	public function filter_add_tab_id( $tabs ) {
		return $this->container->make( Emails_Tab::class )->settings_add_tab_id( $tabs );
	}

	/**
	 * Filter to add template list to Ticklets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param  array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_template_list( $fields ) {
		return $this->container->make( Settings::class )->add_template_list( $fields );
	}

	/**
	 * Filter to add sender info to Ticklets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_sender_info_fields( $fields ) {
		return $this->container->make( Settings::class )->sender_info_fields( $fields );
	}

	/**
	 * Filter to add sender info to Ticklets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_email_styling_fields( $fields ) {
		return $this->container->make( Settings::class )->email_styling_fields( $fields );
	}
}
