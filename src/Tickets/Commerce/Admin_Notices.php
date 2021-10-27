<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Admin_Notices
 *
 * @since TBD
 * 
 * @package TEC\Tickets\Commerce
 */
class Admin_Notices extends \Tribe__Tickets__Admin__Notices {

	/**
	 * @inheritdoc
	 */
	public function hook() {
		add_action( 'admin_init', [ $this, 'maybe_display_notices' ] );
	}

	/**
	 * @inheritdoc
	 */
	public function maybe_display_notices() {
		// Bail on the unexpected
		if (
			! function_exists( 'tribe_installed_before' )
			|| ! class_exists( 'Tribe__Admin__Notices' )
		) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->maybe_display_tickets_commerce_checkout_setting_notice();
		$this->maybe_display_tickets_commerce_success_setting_notice();
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a checkout page is not setup properly
	 *
	 * @since TBD
	 */
	public function maybe_display_tickets_commerce_checkout_setting_notice() {
		// If we're not on our own settings page, bail.
		if ( \Tribe__Settings::$parent_slug !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// If tickets commerce not enabled, bail.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		// If checkout page is set and has the appropriate shortcode, bail.
		$checkout = new Checkout();
		$checkout_page_id  = (int) $checkout->get_page_id();
		$checkout_page = get_post( $checkout_page_id );
		$checkout_shortcode = Shortcodes\Checkout_Shortcode::get_wp_slug();
		if ( ! empty( $checkout_page ) && has_shortcode( $checkout_page->post_content, $checkout_shortcode ) ) {
			return;
		}

		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1axv' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Set up your checkout page', 'event-tickets' );
		$notice_text = sprintf( 
			// translators: %1$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your checkout page. Please configure the setting on Settings > Payments and confirm that the page you have selected has the proper shortcode. %1$s', 'event-tickets' ),
			$notice_link
		);
		$notice_message_html = sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);

		tribe_notice(
			'event-tickets-tickets-commerce-checkout-not-set',
			$notice_message_html,
			[
				'dismiss' => false,
				'type'    => 'error',
			]
		);
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a success page is not setup properly
	 *
	 * @since TBD
	 */
	public function maybe_display_tickets_commerce_success_setting_notice() {
		// If we're not on our own settings page, bail.
		if ( \Tribe__Settings::$parent_slug !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		// If tickets commerce not enabled, bail.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		// If success page is set and has the appropriate shortcode, bail.
		$success = new Success();
		$success_page_id  = (int) $success->get_page_id();
		$success_page = get_post( $success_page_id );
		$success_shortcode = Shortcodes\Success_Shortcode::get_wp_slug();
		if ( ! empty( $success_page ) && has_shortcode( $success_page->post_content, $success_shortcode ) ) {
			return;
		}

		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1axv' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Set up your order success page', 'event-tickets' );
		$notice_text = sprintf( 
			// translators: %1$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your order success page. Please configure the setting on Settings > Payments and confirm that the page you have selected has the proper shortcode. %1$s', 'event-tickets' ),
			$notice_link
		);
		$notice_message_html = sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);

		tribe_notice(
			'event-tickets-tickets-commerce-success-not-set',
			$notice_message_html,
			[
				'dismiss' => false,
				'type'    => 'error',
			]
		);
	}
}
