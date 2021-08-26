<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Order;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;

/**
 * Class Ajax_Request_Handler
 *
 * @todo This whole file will stop exsiting once we deprecate all Give's code usage.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class Ajax_Request_Handler {

	/**
	 * Return on boarding trouble notice.
	 *
	 * @TODO this method needs to be completely refactored into an admin page action.
	 *
	 * @since 5.1.6
	 */
	public function on_boarding_trouble_notice() {

		$action_list = sprintf(
			'<ol><li>%1$s</li><li>%2$s</li><li>%3$s %4$s</li></ol>',
			esc_html__( 'Make sure to complete the entire PayPal process. Do not close the window you have finished the process.', 'event-tickets' ),
			esc_html__( 'The last screen of the PayPal connect process includes a button to be sent back to your site. It is important you click this and do not close the window yourself.', 'event-tickets' ),
			esc_html__( 'If you’re still having problems connecting:', 'event-tickets' ),
			$this->settings->get_guidance_html()
		);

		$standard_error = sprintf(
			'<div id="give-paypal-onboarding-trouble-notice" class="tribe-common-a11y-hidden"><p class="error-message">%1$s</p><p>%2$s</p></div>',
			esc_html__( 'Having trouble connecting to PayPal?', 'event-tickets' ),
			$action_list
		);

		wp_send_json_success( $standard_error );
	}
}
