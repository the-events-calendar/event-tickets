<?php

use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tickets\Admin\Settings;

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
	 * @since 5.18.0 Removed FSE AR Page notice.
	 */
	public function hook() {
		// Bail if some missing component does not exist.
		if (
			! function_exists( 'tribe_installed_before' )
			|| ! class_exists( 'Tribe__Admin__Notices' )
		) {
			return;
		}

		add_action( 'admin_init', [ $this, 'maybe_display_rsvp_new_views_options_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_display_classic_editor_ecp_recurring_tickets_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_display_plus_commerce_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_display_unsupported_currency_notice' ] );
		add_action( 'admin_init', [ $this, 'maybe_display_paystack_notice' ] );
	}

	/**
	 * Display dismissible notice about new RSVP view settings.
	 *
	 * @since 4.12.3
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

		// Bail if we aren't in Tickets > Settings.
		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// Bail if already at wp-admin > Tickets > Settings > Tickets tab to avoid redundancy/confusion by linking to itself.
		if ( 'event-tickets' === tribe_get_request_var( 'tab' ) ) {
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
	 * Display dismissible notice about recurring events tickets, per event.
	 *
	 * @since 5.0.4
	 */
	public function maybe_display_classic_editor_ecp_recurring_tickets_notice() {
		$post_id = absint( tribe_get_request_var( 'post' ) );

		if ( empty( $post_id ) ) {
			return;
		}

		// Warning: Changing this would invalidate all past dismissals.
		$slug_prefix = 'tribe_notice_classic_editor_ecp_recurring_tickets';
		$notice_slug = sprintf( '%s-%d', $slug_prefix, $post_id );

		// Bail if this notice was previously dismissed for this TEC event.
		if ( Tribe__Admin__Notices::instance()->has_user_dimissed( $notice_slug ) ) {
			return;
		}

		/** @var Tribe__Tickets__Editor__Template__Overwrite $template_overwrite */
		$template_overwrite = tribe( 'tickets.editor.template.overwrite' );

		if (
			! function_exists( 'tribe_is_recurring_event' )
			|| ! tribe_is_recurring_event( $post_id )
			|| ! tribe_events_has_tickets( $post_id )
			|| ! $template_overwrite->has_classic_editor( $post_id )
		) {
			return;
		}

		$heading = sprintf(
		// Translators: %1$s: dynamic "Tickets" text, %2$s: dynamic "Event" text.
			_x(
				'%1$s for Recurring %2$s',
				'heading for classic editor notice if Events Calendar Pro event has tickets',
				'event-tickets'
			),
			tribe_get_ticket_label_plural( $slug_prefix ),
			tribe_get_event_label_singular()
		);

		$text = sprintf(
		// Translators: %1$s: dynamic "event" text, %2$s: dynamic "ticket" text, %3$s: dynamic "tickets" text, %4$s: dynamic "RSVP" text, %5$s: dynamic "Ticket" text.
			_x(
				'Heads up! You saved a recurring %1$s with a %2$s. Please note that we do not currently support recurring %3$s. Only the first instance of this recurring series will have your %4$s or %5$s displayed.',
				'text for classic editor notice if Events Calendar Pro event has tickets',
				'event-tickets'
			),
			tribe_get_event_label_singular_lowercase(),
			tribe_get_ticket_label_singular_lowercase( $slug_prefix ),
			tribe_get_ticket_label_plural_lowercase( $slug_prefix ),
			tribe_get_rsvp_label_singular( $slug_prefix ),
			tribe_get_ticket_label_singular( $slug_prefix )
		);

		$message = sprintf(
			'<h3>%1$s</h3>%2$s',
			esc_html( $heading ),
			wpautop( esc_html( $text ) )
		);

		tribe_notice(
			$notice_slug,
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
			esc_html__( 'Event Tickets Plus', 'event-tickets' )
		);

		$plus_commerce_providers = [
			'woocommerce/woocommerce.php'                       => __( 'WooCommerce', 'event-tickets' ),
			'easy-digital-downloads/easy-digital-downloads.php' => __( 'Easy Digital Downloads', 'event-tickets' ),
		];

		foreach ( $plus_commerce_providers as $path => $provider ) {
			if ( ! is_plugin_active( $path ) ) {
				continue;
			}

			$message = sprintf(
			// translators: %1$s: The ticket commerce provider (WooCommerce, etc); %2$s: The Event Tickets Plus plugin name and link.
				esc_html__( 'Event Tickets does not support ticket sales via third party ecommerce plugins. If you want to sell tickets with %1$s, please purchase a license for %2$s.', 'event-tickets' ),
				esc_html( $provider ),
				$plus
			);

			// Wrap in <p> tag.
			$message = sprintf( '<p>%s</p>', $message );

			tribe_notice( "event-tickets-plus-missing-{$provider}-support", $message, 'dismiss=1&type=warning' );
		}
	}

	/**
	 * Display notices for unsupported currencies.
	 *
	 * @since 5.5.7
	 *
	 * @return void
	 */
	public function maybe_display_unsupported_currency_notice() {
		if ( Currency::is_current_currency_supported() ) {
			return;
		}

		$message = sprintf(
			'<h3>%1$s</h3><p>%2$s</p>',
			esc_html( Currency::$unsupported_currency['heading'] ),
			esc_html( Currency::$unsupported_currency['message'] )
		);

		$notice_symbol = Currency::$unsupported_currency['symbol'];

		tribe_notice(
			"event-tickets-unsupported-currencies-{$notice_symbol}",
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			]
		);
	}

	/**
	 * Display notice for Paystack promotion.
	 *
	 * @since 5.6.3
	 *
	 * @return void
	 */
	function maybe_display_paystack_notice() {

		// Bail if we aren't in Tickets > Settings.
		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// Bail if Paystack plugin is installed and activated.
		if ( class_exists( 'paystack\tec\classes\Core', false ) ) {
			return;
		}

		// Bail if we aren't in the correct timezone.
		$timezone           = get_option( 'timezone_string' );
		$paystack_timezones = [
			'Africa/Lagos',
			'Africa/Accra',
			'Africa/Johannesburg',
		];
		if ( ! in_array( $timezone, $paystack_timezones, true ) ) {
			return;
		}

		$heading = _x(
			'Sell tickets with Paystack',
			'heading for Paystack notice',
			'event-tickets'
		);

		$learn_more_link = sprintf(
			'<a target="_blank" rel="noopener nofollow" href="%s">%s</a>',
			esc_attr( 'https://evnt.is/et-tc-paystack-in-app' ),
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$text = _x(
			sprintf(
			// Translators: %s: dynamic "Learn more" link.
				'Install and activate the Paystack for The Events Calendar plugin to start selling tickets with Paystack using our free commerce solution, Tickets Commerce. %s',
				$learn_more_link
			),
			'text for Paystack notice',
			'event-tickets'
		);

		$message = sprintf(
			'<h3>%1$s</h3><p>%2$s</p>',
			esc_html( $heading ),
			wp_kses( $text, 'post' )
		);

		tribe_notice(
			'event-tickets-commerce-paystack-notice',
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			]
		);

	}

	/**
	 * Will be removed. Doing nothing other than triggering a deprecation notice.
	 *
	 * @since 5.17.0
	 * @deprecated 5.18.0 This method will be removed in a future release.
	 *
	 * @return void
	 */
	public function maybe_display_fse_ar_page_notice() {
		_deprecated_function( __METHOD__, '5.18.0' );
	}
}
