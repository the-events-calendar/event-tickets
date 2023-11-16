<?php

namespace TEC\Tickets\QR;

/**
 * Class Admin_Notice
 *
 * @since   5.7.0
 *
 * @package TEC\Tickets\QR
 */
class Notices {

	/**
	 * Registers the notices for the QR code handling.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function register_admin_notices(): void {
		tribe_notice(
			'tec-tickets-qr-dependency-notice',
			[ $this, 'get_dependency_notice_contents' ],
			[
				'type'    => 'warning',
				'dismiss' => 1,
				'wrap'    => 'p',
			],
			[ $this, 'should_display_dependency_notice' ]
		);
	}

	/**
	 * Determines if the Notice for QR code dependencies should be visible
	 *
	 * @since 5.7.0
	 *
	 * @return bool
	 */
	public function should_display_dependency_notice(): bool {
		// Only attempt to check the page if the user can't use the QR codes.
		if ( tribe( Controller::class )->can_use() ) {
			return false;
		}

		$active_page = tribe_get_request_var( 'page' );

		if ( $active_page ) {
			$valid_pages = [
				'tickets-attendees',
				'tickets-commerce-orders',
				'edd-orders',
				'tickets-orders',
				'tec-tickets',
				'tec-tickets-help',
				'tec-tickets-troubleshooting',
				'tec-tickets-settings',
			];

			if ( in_array( $active_page, $valid_pages, true ) ) {
				return true;
			}
		}

		if ( 'ticket-meta-fieldset' === tribe_get_request_var( 'post_type' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the notice for the QR code dependency.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_dependency_notice_contents(): string {
		$html  = '<h2>' . esc_html__( 'QR codes for tickets not available.', 'event-tickets' ) . '</h2>';
		$html .= esc_html__( 'In order to have QR codes for your tickets you will need to have both the `php_gd2` and `gzuncompress` PHP extensions installed on your server. Please contact your hosting provider.', 'event-tickets' );
		$html .= ' <a target="_blank" href="https://evnt.is/event-tickets-qr-support">' . esc_html__( 'Learn more.', 'event-tickets' ) . '</a>';

		return $html;
	}
}
