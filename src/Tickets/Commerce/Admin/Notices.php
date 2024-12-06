<?php

namespace TEC\Tickets\Commerce\Admin;

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Success;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;

/**
 * Class Notices
 *
 * @since 5.2.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Notices extends Service_Provider {

	/**
	 * @inheritdoc
	 */
	public function register() {
		if ( ! is_admin() ) {
			return;
		}

		$notices = [
			[
				'event-tickets-tickets-commerce-checkout-not-set',
				[ $this, 'render_checkout_notice' ],
				[ 'dismiss' => false, 'type' => 'error' ],
				[ $this, 'should_render_checkout_notice' ],
			],
			[
				'event-tickets-tickets-commerce-success-not-set',
				[ $this, 'render_success_notice' ],
				[ 'dismiss' => false, 'type' => 'error' ],
				[ $this, 'should_render_success_notice' ],
			],
			[
				'event-tickets-tickets-commerce-permalinks',
				[ $this, 'render_permalinks_notice' ],
				[ 'dismiss' => true, 'type' => 'error' ],
				[ $this, 'should_render_permalinks_notice' ],
			],
		];

		/**
		 * Filters admin notices.
		 *
		 * @since 5.3.2
		 *
		 * @param array[] $notices Array of admin notice parameters.
		 */
		$notices = apply_filters( 'tec_tickets_commerce_admin_notices', $notices );

		foreach ( $notices as $notice ) {
			call_user_func_array( 'tribe_notice', $notice );
		}
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a checkout page is not setup properly.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	public function should_render_checkout_notice() {
		// If we're not on our own settings page, bail.
		if ( tribe_get_request_var( 'page' ) !== \Tribe\Tickets\Admin\Settings::$settings_page_id ) {
			return false;
		}

		if ( tribe( Checkout::class )->page_has_shortcode() ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the HTML for the notice that is shown when checkout setting is not set.
	 *
	 * @since 5.2.0
	 *
	 * @return string Notice HTML.
	 */
	public function render_checkout_notice() {
		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1axv' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Set up your checkout page', 'event-tickets' );
		$notice_text = sprintf(
			// translators: %1$s: Opening `<a>` tag for the Payments tab on the Tickets Settings. %2$s: Closing `</a>` tag. %3$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your checkout page. Please configure the setting on %1$sTickets > Settings > Payments%2$s and confirm that the page you have selected has the proper shortcode. %3$s', 'event-tickets' ),
			'<a href="' . tribe( Plugin_Settings::class )->get_url( [ 'tab' => 'payments' ] ) . '">',
			'</a>',
			$notice_link
		);

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a success page is not setup properly.
	 *
	 * @since 5.2.0
	 */
	public function should_render_success_notice() {
		// If we're not on our own settings page, bail.
		if ( tribe_get_request_var( 'page' ) !== \Tribe\Tickets\Admin\Settings::$settings_page_id ) {
			return false;
		}

		if ( tribe( Success::class )->page_has_shortcode() ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the HTML for the notice that is shown when success setting is not set.
	 *
	 * @since 5.2.0
	 *
	 * @return string Notice HTML.
	 */
	public function render_success_notice() {
		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1axv' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);
		$notice_header = esc_html__( 'Set up your order success page', 'event-tickets' );
		$notice_text   = sprintf(
			// translators: %1$s: Opening `<a>` tag for the Payments tab on the Tickets Settings. %2$s: Closing `</a>` tag.  %3$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your order success page. Please configure the setting on %1$sTickets > Settings > Payments%2$s and confirm that the page you have selected has the proper shortcode. %3$s', 'event-tickets' ),
			'<a href="' . tribe( Plugin_Settings::class )->get_url( [ 'tab' => 'payments' ] ) . '">',
			'</a>',
			$notice_link
		);

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, and the site is not using pretty permalinks.
	 *
	 * @since 5.4.1
	 *
	 * @return bool Whether or not to render the notice.
	 */
	public function should_render_permalinks_notice() {
		// If the site is using pretty permalinks, bail.
		if ( '' !== get_option( 'permalink_structure' ) ) {
			return false;
		}

		// If we're not on our own settings page, bail.
		if ( tribe_get_request_var( 'page' ) !== \Tribe\Tickets\Admin\Settings::$settings_page_id ) {
			return false;
		}

		// If Tickets Commerce is not enabled, bail.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the HTML for the notice that is shown when permalinks are not set.
	 *
	 * @since 5.4.1
	 *
	 * @return string Notice HTML.
	 */
	public function render_permalinks_notice() {
		$notice_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/tec-tc-permalinks' ),
			esc_html__( 'Learn More', 'event-tickets' )
		);

		$notice_header = esc_html__( 'Set up your permalinks to sell with Tickets Commerce', 'event-tickets' );
		$notice_text   = sprintf(
			// translators: %3$s: Link to knowledgebase article.
			esc_html__( 'In order to start selling with Tickets Commerce, you\'ll need to set up your permalinks setting to an option different than "Plain". Please configure the setting on %1$sSettings > Permalinks%2$s and confirm that you are not using plain permalinks. %3$s', 'event-tickets' ),
			'<a href="' . get_admin_url( null, 'options-permalink.php' ) . '">',
			'</a>',
			$notice_link
		);

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			$notice_header,
			$notice_text
		);
	}
}
