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
		add_action( 'plugins_loaded', array( $this, 'maybe_display_plus_commerce_notice' ) );
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
			array(
				'utm_source'   => 'plugin-install',
				'utm_medium'   => 'plugin-event-tickets',
				'utm_campaign' => 'in-app',
			),
			'https://theeventscalendar.com/product/wordpress-event-tickets-plus/'
		);
		$plus      = sprintf( '<a target="_blank" href="%s">%s</a>', esc_attr( $plus_link ), esc_html( 'Event Tickets Plus', 'tribe-common' ) );

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
