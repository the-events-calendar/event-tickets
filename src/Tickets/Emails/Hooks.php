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
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Email\RSVP;
use Tribe__Tickets__Tickets as Tickets_Module;

/**
 * Class Hooks.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */
class Hooks extends Service_Provider {

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
		add_action( 'tec_settings_footer_after_save_fields_tab_emails', [ $this, 'action_add_preview_modal_button' ] );
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

		// Inject the new email into the legacy codebase for emails.
		add_filter( 'tec_tickets_send_rsvp_email_pre', [ $this, 'filter_hijack_legacy_sent_rsvp_emails' ], 20, 4 );
		add_filter( 'tec_tickets_send_tickets_email_for_attendee_pre', [ $this, 'filter_hijack_legacy_sent_tickets_attendees_emails' ], 20, 5 );
		add_filter( 'tec_tickets_send_rsvp_non_attendance_confirmation_pre', [ $this, 'filter_hijack_legacy_send_rsvp_non_attendance_confirmation' ], 20, 4 );

		// skip saving hidden fields for RSVP emails.
		add_filter( 'tribe_settings_fields', [ $this, 'filter_rsvp_fields_before_saving' ], 90, 2 );
	}

	/**
	 * Filters the values to be saved while saving RSVP Email settings.
	 *
	 * @since 5.6.0
	 *
	 * @param array $fields The fields to be saved.
	 * @param string $admin_page The admin page being saved.
	 *
	 * @return array
	 */
	public function filter_rsvp_fields_before_saving( array $fields, $admin_page ): array {
		return $this->container->make( RSVP::class )->filter_rsvp_fields_before_saving( $fields, $admin_page );
	}

	/**
	 * Filters the redirect URL to determine whether section key needs to be added.
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
		echo $this->container->make( Admin\Preview_Modal::class )->get_modal_button();
	}

	/**
	 * Action to add the preview modal to the settings page.
	 *
	 * @since 5.5.7
	 */
	public function action_add_preview_modal() {
		$this->container->make( Admin\Preview_Modal::class )->render_modal();
	}

	/**
	 * Filter to add tab id to tickets emails tab.
	 *
	 * @since 5.5.6
	 *
	 * @param array $tabs Current array of tabs ids.
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
	 * @param array $fields Current array of Tickets Emails settings fields.
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
	 * @param array $fields Current array of Tickets Emails settings fields.
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

		return $this->container->make( Admin\Preview_Modal::class )->get_modal_content_ajax( $render_response, $vars );
	}

	/**
	 * Hooks to the legacy modules and hijacks the sending of RSVP emails from the old system to Tickets Emails.
	 *
	 * @see   Legacy_Hijack::send_rsvp_email
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean   $pre      Previous value from the filter, mostly will be null.
	 * @param int            $order_id The order ID.
	 * @param int            $event_id The event ID.
	 * @param Tickets_Module $module   Commerce module we are using for these emails.
	 */
	public function filter_hijack_legacy_sent_rsvp_emails( $pre, $order_id, $event_id = null, $module = null ) {
		return $this->container->make( Legacy_Hijack::class )->send_rsvp_email( $pre, $order_id, $event_id, $module );
	}

	/**
	 * Hooks to the legacy modules and hijacks the sending of Tickets emails from the old system to Tickets Emails.
	 *
	 * @see   Legacy_Hijack::send_tickets_email_for_attendee
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean   $pre     Previous value from the filter, mostly will be null.
	 * @param string         $to      The email to send the tickets to.
	 * @param array          $tickets The list of tickets to send.
	 * @param array          $args    See the rest of the documentation for this on Legacy::send_tickets_email_for_attendee
	 *
	 * @param Tickets_Module $module  Commerce module we are using for these emails.
	 *
	 * @return bool Whether email was sent to attendees.
	 */
	public function filter_hijack_legacy_sent_tickets_attendees_emails( $pre, $to, $tickets, $args = [], $module = null ) {
		return $this->container->make( Legacy_Hijack::class )->send_tickets_email_for_attendee( $pre, $to, $tickets, $args, $module );
	}

	/**
	 * Hooks to the legacy module of RSVP and hijacks the sending of RSVP email for non-attendance confirmation from the old system to Tickets Emails.
	 *
	 * @see   Legacy_Hijack::send_rsvp_non_attendance_confirmation
	 *
	 * @since 5.6.0
	 *
	 * @param null|boolean   $pre      Previous value from the filter, mostly will be null.
	 * @param int            $order_id The order ID.
	 * @param int            $event_id The event ID.
	 * @param Tickets_Module $module   Commerce module we are using for these emails.
	 *
	 * @return bool Whether email was sent to attendees.
	 */
	public function filter_hijack_legacy_send_rsvp_non_attendance_confirmation( $pre, $order_id, $event_id, $module = null ) {
		return $this->container->make( Legacy_Hijack::class )->send_rsvp_non_attendance_confirmation( $pre, $order_id, $event_id, $module );
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
