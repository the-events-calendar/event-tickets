<?php
/**
 * Class that handles interfacing with tec-common Telemetry.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Telemetry
 */

namespace TEC\Tickets\Telemetry;

use TEC\Common\StellarWP\Telemetry\Config;
use TEC\Common\StellarWP\Telemetry\Opt_In\Status;
use TEC\Common\Telemetry\Telemetry as Common_Telemetry;
use Tribe__Tickets__Main;

/**
 * Class Telemetry
 *
 * @since   TBD

 * @package TEC\Tickets\Telemetry
 */
class Telemetry {

	/**
	 * The Telemetry plugin slug for Event Tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $plugin_slug = 'event-tickets';

	/**
	 * The "plugin path" for the Event Tickets main file.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $plugin_path = 'event-tickets.php';

	/**
	 * Filters the modal optin args to be specific to Event Tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string|mixed> $original_optin_args The original args, provided by Common.
	 *
	 * @return array<string|mixed> The filtered args.
	 */
	public function filter_tec_common_telemetry_optin_args( $original_optin_args ): array {
		$user_name   = esc_html( wp_get_current_user()->display_name );

		$et_optin_args = [
			'plugin_logo_alt'       => 'Event Tickets Logo',
			'plugin_name'           => 'Event Tickets',
			'heading'               => __( 'We hope you love Event Tickets!', 'event-tickets' ),
			'intro'                 => __( "Hi, {$user_name}! This is an invitation to help our StellarWP community. If you opt-in, some data about your usage of Event Tickets and future StellarWP Products will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on WordPress, and our products from time to time. And if you skip this, that’s okay! Our products still work just fine.", 'event-tickets' ),
		];

		return array_merge( $original_optin_args, $et_optin_args );
	}


	/**
	 * Adds the opt in/out control to the general tab debug section.
	 *
	 * @TODO: this is a stub for now that inserts in the wrong page - it needs to be corrected and hooked in the Provider class.
	 *
	 * @since TBD
	 *
	 * @param array<string|mixed> $fields The fields for the general tab Debugging section.
	 *
	 * @return array<string|mixed> The fields, with the optin control appended.
	 */
	public function filter_tec_tickets_authentication_settings(  $fields ): array {
		$status = Config::get_container()->get( Status::class );
		$opted = $status->get();

		switch( $opted ) {
			case $status::STATUS_ACTIVE :
				$label = esc_html_x( 'Opt out of Telemetry', 'Settings label for opting out of Telemetry.', 'the-events-calendar' );
			default :
				$label = esc_html_x( 'Opt in to Telemetry', 'the-events-calendar' );
		}

		$fields['ticket-telemetry-optin-heading'] =  [
			'type' => 'html',
			'html' => '<h3 id="event-tickets-telemetry-settings">' . __( 'Telemetry', 'event-tickets' ) . '</h3>',
		];

		$fields['opt-in-status'] = [
			'type'            => 'checkbox_bool',
			'label'           => $label,
			'tooltip'         => sprintf(
				/* Translators: Description of the Telemetry optin setting.
				%1$s: opening anchor tag for permissions link.
				%2$s: opening anchor tag for terms of service link.
				%3$s: opening anchor tag for privacy policy link.
				%4$s: closing anchor tags.
				*/
				_x( 'Enable this option to share usage data with Event Tickets and StellarWP. %1$sWhat permissions are being granted?%4$s %2$sRead our Terms of Service%4$s %3$sRead our Privacy Policy%4$s', 'Description of optin setting.', 'the-events-calendar' ),
				'<a href=" ' . Common_Telemetry::get_permissions_url() . ' ">',
				'<a href=" ' . Common_Telemetry::get_terms_url() . ' ">',
				'<a href=" ' . Common_Telemetry::get_privacy_url() . ' ">',
				'</a>'
			),
			'default'         => false,
			'validation_type' => 'boolean',
		];

		return $fields;
	}

	/**
	 * Ensures the admin control reflects the actual opt-in status.
	 * We save this value in tribe_options but since that could get out of sync,
	 * we always display the status from TEC\Common\StellarWP\Telemetry\Opt_In\Status directly.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value  The value of the attribute.
	 * @param string $field  The field object id.
	 *
	 * @return mixed $value
	 */
	public function filter_tribe_field_opt_in_status( $value, $id ) {
		if ( 'opt-in-status' !== $id ) {
			return $value;
		}

		// We don't care what the value stored in tribe_options is - give us the Opt_In\Status value.
		$status = Config::get_container()->get( Status::class );
		// Rather than test for STATUS_ACTIVE, we just make sure it's not inactive (as there is also a "mixed" status)
		$value = $status->get() !== $status::STATUS_INACTIVE;

		return $value;
	}

	/**
	 * Adds Event Tickets to the list of plugins
	 * to be opted in/out alongside tribe-common.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $slugs The default array of slugs in the format  [ 'plugin_slug' => 'plugin_path' ]
	 *
	 * @see \TEC\Common\Telemetry\Telemetry::get_tec_telemetry_slugs()
	 *
	 * @return array<string,string> $slugs The same array with The Events Calendar added to it.
	 */
	public function filter_tec_telemetry_slugs( $slugs ) {
		$dir = Tribe__Tickets__Main::instance()->plugin_dir;
		$slugs[self::$plugin_slug] =  $dir . self::$plugin_path;
		return array_unique( $slugs, SORT_STRING );
	}
}
