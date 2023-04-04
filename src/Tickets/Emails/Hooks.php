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
use TEC\Tickets\Emails\Admin\Emails_Tab;

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
		add_action( 'init', [ $this, 'action_register_post_type' ] );
		add_action( 'init', [ $this, 'action_maybe_populate_email_post_types' ] );
		add_action( 'tribe_settings_do_tabs', [ $this, 'register_emails_tab' ], 17 );
		add_action( 'tribe_settings_after_form_element_tab_emails', [ $this, 'action_add_preview_modal_button' ] );
		add_action( 'admin_footer', [ $this, 'action_add_preview_modal' ] );
		add_action( 'template_redirect', [ $this, 'action_template_redirect_tickets_emails' ] );
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since 5.5.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_add_tab_id' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_maybe_add_upgrade_field' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_template_list' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_sender_info_fields' ] );
		add_filter( 'tec_tickets_emails_settings_fields', [ $this, 'filter_add_email_styling_fields' ] );

		// Hook the `Tickets Emails` preview for the AJAX requests.
		add_filter( 'tribe_tickets_admin_manager_request', [ $this, 'filter_add_preview_modal_content' ], 15, 2 );

		add_filter( 'wp_redirect', [ $this, 'filter_redirect_url' ] );
	}

	/**
	 * Filters the redirect URL to determine whether or not section key needs to be added.
	 *
	 * @since 5.5.9
	 *
	 * @param string $url Redirect URL.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		return $this->container->make( Emails_Tab::class )->filter_redirect_url( $url );
	}

	/**
	 * Action to register the post type with emails.
	 *
	 * @since 5.5.9
	 *
	 */
	public function action_register_post_type() {
		$this->container->make( Email_Handler::class )->register_post_type();
	}

	/**
	 * Action to possibly create default email post types.
	 *
	 * @since 5.5.9
	 *
	 */
	public function action_maybe_populate_email_post_types() {
		$this->container->make( Email_Handler::class )->maybe_populate_tec_tickets_emails_post_type();
	}

	/**
	 * Action to add emails tab to tickets settings page.
	 *
	 * @since 5.5.6
	 *
	 * @param string $admin_page Page ID of current admin page.
	 */
	public function register_emails_tab( $admin_page ) {
		$this->container->make( Admin\Emails_Tab::class )->register_tab( $admin_page );
	}

	/**
	 * Action to add the preview modal button to the settings page.
	 *
	 * @since 5.5.7
	 */
	public function action_add_preview_modal_button() {
		echo $this->container->make( Admin\Preview_modal::class )->get_modal_button();
	}

	/**
	 * Action to add the preview modal to the settings page.
	 *
	 * @since 5.5.7
	 */
	public function action_add_preview_modal() {
		echo $this->container->make( Admin\Preview_modal::class )->render_modal();
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
		return $this->container->make( Admin\Emails_Tab::class )->settings_add_tab_id( $tabs );
	}

	/**
	 * Filter to maybe add the upgrade option.
	 *
	 * @since 5.5.9
	 *
	 * @param  array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_maybe_add_upgrade_field( $fields ) {
		return $this->container->make( Admin\Settings::class )->maybe_add_upgrade_field( $fields );
	}

	/**
	 * Filter to add template list to Tickets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param  array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_template_list( $fields ) {
		return $this->container->make( Admin\Settings::class )->add_template_list( $fields );
	}

	/**
	 * Filter to add sender info to Tickets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_sender_info_fields( $fields ) {
		return $this->container->make( Admin\Settings::class )->sender_info_fields( $fields );
	}

	/**
	 * Filter to add sender info to Tickets Emails settings fields.
	 *
	 * @since 5.5.6
	 *
	 * @param array $fields Current array of Tickets Emails settings fields.
	 *
	 * @return array $fields Filtered array of Tickets Emails settings fields.
	 */
	public function filter_add_email_styling_fields( $fields ) {
		return $this->container->make( Admin\Settings::class )->email_styling_fields( $fields );
	}

	/**
	 * Filter the preview modal content.
	 *
	 * @since 5.5.7
	 *
	 * @param string|\WP_Error $render_response The render response HTML content or WP_Error with list of errors.
	 * @param array            $vars            The request variables.
	 *
	 * @return string $content The response for the preview modal content.
	 */
	public function filter_add_preview_modal_content( $render_response, $vars ) {
		if ( 'tec_tickets_preview_email' !== $vars['request'] ) {
			return $render_response;
		}

		return $this->container->make( Admin\Preview_modal::class )->get_modal_content_ajax( $render_response, $vars );
	}

	/**
	 * Manage the redirect to view emails via web.
	 *
	 * @since 5.5.9
	 *
	 * @return void
	 */
	public function action_template_redirect_tickets_emails() {
		$this->container->make( Web_View::class )->action_template_redirect_tickets_emails();
	}
}
