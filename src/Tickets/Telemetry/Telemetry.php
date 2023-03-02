<?php
/**
 * Class that handles interfacing with tec-common Telemetry.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Telemetry
 */

namespace TEC\Tickets\Telemetry;

/**
 * Class Telemetry
 *
 * @since   TBD

 * @package TEC\Tickets\Telemetry
 */
class Telemetry {
	public function filter_tec_common_telemetry_optin_args( $original_optin_args ) {
		$user_name   = esc_html( wp_get_current_user()->display_name );

		$et_optin_args = [
			'plugin_logo'           => tribe_resource_url( 'images/event-tickets.svg', false, null, \Tribe__Tickets__Main::instance() ),
			'plugin_logo_alt'       => 'Event Tickets Logo',
			'plugin_name'           => 'Event Tickets',
			'heading'               => __( 'We hope you love The Event Tickets!', 'event-tickets' ),
			'intro'                 => __( "Hi, {$user_name}! This is an invitation to help our StellarWP community. If you opt-in, some data about your usage of Event Tickets and future StellarWP Products will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on WordPress, and our products from time to time. And if you skip this, that’s okay! Our products still work just fine.", 'event-tickets' ),
		];

		return array_merge( $original_optin_args, $et_optin_args );
	}
}
