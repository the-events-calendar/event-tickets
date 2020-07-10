<?php

/**
 * Class Tribe__Tickets__Admin__Notices
 *
 * @since 4.7
 */
class Tribe__Tickets__Admin__Notices {

	/**
	 * Hooks the actions and filters used by the class
	 *
	 * @since 4.7
	 */
	public function hook() {
		add_action( 'admin_init', array( $this, 'maybe_display_notices' ) );
	}

	/**
	 * Maybe display admin notices.
	 *
	 * @since TBD
	 */
	public function maybe_display_notices() {
		// Bail on the unexpected
		if (
			! function_exists( 'tribe_installed_before' )
			|| ! class_exists( 'Tribe__Admin__Notices' )
		) {
			return;
		}

		$this->maybe_display_plus_commerce_notice();

		$this->maybe_display_rsvp_new_views_options_notice();
	}

	/**
	 * Display dismissible notice about new RSVP view settings.
	 *
	 * @since TBD
	 */
	public function maybe_display_rsvp_new_views_options_notice() {
		// Bail if previously dismissed this notice.
		if ( Tribe__Admin__Notices::instance()->has_user_dimissed( __FUNCTION__ ) ) {
			return;
		}

		/** @var Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Bail if user cannot change settings.
		if ( ! current_user_can( $settings->requiredCap ) ) {
			return;
		}

		// Only show to previously existing installs.
		if ( ! tribe_installed_before( 'Tribe__Tickets__Main', '5.0' ) ) {
			return;
		}

		// Bail if we aren't in Events > Settings.
		if ( 'tribe-common' !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// Bail if already at wp-admin > Events > Settings > Tickets tab to avoid redundancy/confusion by linking to itself.
		if ( 'display' === tribe_get_request_var( 'tab' ) ) {
			return;
		}

		// Bail if the option is already in use.
		if ( tribe_tickets_rsvp_new_views_is_enabled() ) {
			return;
		}

		// Get link to Display Tab.
		$url = $settings->get_url( [
			'page' => 'tribe-common',
			'tab'  => 'display',
		] );

		$link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html_x( 'RSVP Display Settings', 'Admin notice link text', 'event-tickets' )
		);

		// Set heading text.
		$heading = __( 'Event Tickets', 'event-tickets' );

		// Build notice text.
		$text = sprintf(
			// translators: %1$s: RSVP singular text, %2$s: Link to settings page.
			__( 'With this new version, we\'ve introduced newly redesigned %1$s frontend views. If you have customized the %1$s section, this update will likely impact your customizations.
			
			To upgrade to the new frontend views, please enable them in the %2$s.', 'event-tickets' ),
			tribe_get_rsvp_label_singular( 'admin_notices' ),
			$link
		);

		// Build notice message.
		$message = sprintf( '<h3>%1$s</h3>%2$s', $heading, wpautop( $text ) );

		tribe_notice(
			__FUNCTION__,
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			]
		);
	}

	/**
	 * Display a notice for the user about missing support if ET+ supported commerce providers are active
	 * but ET+ is not.
	 *
	 * @since 4.7
	 */
	public function maybe_display_plus_commerce_notice() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		$plus_link = add_query_arg(
			[
				'utm_source'   => 'plugin-install',
				'utm_medium'   => 'plugin-event-tickets',
				'utm_campaign' => 'in-app',
			],
			'https://theeventscalendar.com/product/wordpress-event-tickets-plus/'
		);

		$plus = sprintf(
			'<a target="_blank" rel="noopener nofollow" href="%s">%s</a>',
			esc_attr( $plus_link ),
			esc_html( 'Event Tickets Plus', 'event-tickets' )
		);

		$plus_commerce_providers = array(
			esc_html( 'WooCommerce', 'event-tickets' )            => 'woocommerce/woocommerce.php',
			esc_html( 'Easy Digital Downloads', 'event-tickets' ) => 'easy-digital-downloads/easy-digital-downloads.php',
		);

		foreach ( $plus_commerce_providers as $provider => $path ) {
			if ( ! is_plugin_active( $path ) ) {
				continue;
			}

			$message = sprintf(
				__( 'Event Tickets does not support ticket sales via third party ecommerce plugins. If you want to sell tickets with %1$s, please purchase a license for %2$s.' ),
				$provider,
				$plus
			);

			tribe_notice( "event-tickets-plus-missing-{$provider}-support", "<p>{$message}</p>", 'dismiss=1&type=warning' );
		}
	}
}
