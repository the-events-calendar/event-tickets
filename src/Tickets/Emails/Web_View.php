<?php
/**
 * Tickets Emails web view.
 *
 * @since 5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use WP_Error;

/**
 * Class Web_View
 *
 * @since   5.5.9
 *
 * @package TEC\Tickets\Emails
 */
class Web_View {

	/**
	 * The web view URL.
	 *
	 * @since 5.5.9.
	 */
	public static $url_slug = 'tec-tickets-emails-web-view';

	/**
	 * Get the web view link.
	 *
	 * @since 5.5.9
	 *
	 * @return string The email web view URL.
	 */
	public function get_url(): string {
		// @todo @juanfra: Implement a method to get the link to the web view link URL based on the email.
		return '';
	}

	/**
	 * Manage the redirect to generate the email on the fly.
	 *
	 * @since 5.5.9
	 *
	 * @return void
	 */
	public function action_template_redirect_tickets_emails() {
		if ( empty( tribe_get_request_var( self::$url_slug ) ) ) {
			return;
		}

		$attendee_id   = (int) tribe_get_request_var( 'attendee_id' );
		$security_code = (string) tribe_get_request_var( 'security_code' );

		// @todo @juanfra: See if we use the WP_Error or something else.
		if ( empty( $attendee_id ) ) {
			//new WP_Error( 'tec-tickets-emails-web-view-no-attendee', 'The `attendee_id` parameter is empty.' );
		}

		// @todo @juanfra: See if we use the WP_Error or something else.
		if ( empty( $security_code ) ) {
			//new WP_Error( 'tec-tickets-emails-web-view-no-security-code', 'The `security_code` parameter is empty.' );
		}

		/** @var \Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		$service_provider = $data_api->get_ticket_provider( $attendee_id );

		if (
			empty( $service_provider->security_code )
			|| get_post_meta( $attendee_id, $service_provider->security_code, true ) !== $security_code
		) {
			//return new WP_Error( 'tec-tickets-emails-web-view-security-code-not-valid', 'The `security_code` parameter is not valid.' );
		}

		/** @var Tribe__Tickets__Editor__Template $template */
		$tickets_template = tribe( 'tickets.editor.template' );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( true );

		$email_template->render();

		exit;
	}

}
