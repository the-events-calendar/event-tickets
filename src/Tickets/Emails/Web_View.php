<?php
/**
 * Tickets Emails web view.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use WP_Error;

/**
 * Class Web_View
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */
class Web_View {

	/**
	 * The web view URL.
	 *
	 * @since TBD.
	 */
	public static $url_slug = 'tec-tickets-emails-web-view';

	/**
	 * Get the web view link.
	 *
	 * @since TBD
	 *
	 * @return string The email web view URL.
	 */
	public function get_url(): string {
		// @todo @juanfra: Implement a method to get the link to the web view link URL.
		$url = add_query_arg(
			[
				self::$url_slug => 1,
			],
			site_url()
		);

		return $url;
	}

	/**
	 * Manage the redirect to generate the PDF on the fly.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function action_template_redirect_tickets_email() {
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

		/** @var Tribe__Tickets__Data_API $data_api */
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

		echo $email_template->get_html( [], false );

		exit;
	}

}
