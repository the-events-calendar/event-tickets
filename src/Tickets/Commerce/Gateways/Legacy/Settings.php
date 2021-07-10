<?php
/**
 *
 * @todo This file is not being used currently but we need to remove this before we launch Tickets Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\Legacy
 */

namespace TEC\Tickets\Commerce\Gateways\Legacy;

use TEC\Tickets\Commerce\Abstract_Settings;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;

/**
 * The PayPal Standard (Legacy) specific settings.
 *
 * This class will contain all of the settings handling and admin settings config implementation from
 * Tribe__Tickets__Commerce__PayPal__Main that is PayPal Standard (Legacy) specific.
 *
 * @since   5.1.6
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy
 */
class Settings extends Abstract_Settings {

	/**
	 * The option key for email.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_email = 'ticket-paypal-email';

	/**
	 * The option key for IPN enabled.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_ipn_enabled = 'ticket-paypal-ipn-enabled';

	/**
	 * The option key for IPN address set.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_ipn_address_set = 'ticket-paypal-ipn-address-set';

	/**
	 * The option key for IPN notify URL.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_ipn_notify_url = 'ticket-paypal-notify-url';

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for the gateway.
	 */
	public function get_settings() {
		$home_url = home_url();

		// The KB article URL will change depending on whether ET+ is active or not.
		$paypal_setup_kb_url  = class_exists( 'Tribe__Tickets_Plus__Main' ) ? 'https://evnt.is/19yk' : 'https://evnt.is/19yj';
		$paypal_setup_kb_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $paypal_setup_kb_url ),
			esc_html__( 'these instructions', 'event-tickets' )
		);
		$paypal_setup_note    = sprintf(
			// Translators: %1$s: The word "ticket" in lowercase, %2$s: The "these instructions" link.
			esc_html_x( 'Ie Tickets Commerce to sell %1$s, you must configure your PayPal account to communicate with your WordPress site. If you need help getting set up, follow %2$s', 'tickets fields settings PayPal setup', 'event-tickets' ),
			esc_html( tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_setup' ) ),
			$paypal_setup_kb_link
		);

		$ipn_setup_site_link    = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $home_url ),
			esc_html( $home_url )
		);
		$ipn_setup_site_address = sprintf(
			// Translators: %s: The site link.
			esc_html__( 'Your site address is: %s', 'event-tickets' ),
			$ipn_setup_site_link
		);
		$ipn_setup_line         = sprintf(
			'<span class="clear">%s</span><span class="clear">%s</span>',
			esc_html__( "Have you entered this site's address in the Notification URL field in IPN Settings?", 'event-tickets' ),
			$ipn_setup_site_address
		);

		/** @var \Tribe__Tickets__Commerce__PayPal__Handler__IPN $ipn_handler */
		$ipn_handler = tribe( 'tickets.commerce.paypal.handler.ipn' );

		$ipn_config_status = sprintf(
			'<strong>%1$s <span id="paypal-ipn-config-status" data-status="%2$s">%3$s</span></strong><p class="description"><i>%4$s</i></p>',
			esc_html__( 'PayPal configuration status:', 'event-tickets' ),
			esc_attr( $ipn_handler->get_config_status( 'slug' ) ),
			esc_html( $ipn_handler->get_config_status( 'label' ) ),
			esc_html__( 'For help creating and configuring your account, call PayPal at 1-844-720-4038 (USA)', 'event-tickets' )
		);

		$settings = [
			'ticket-paypal-configure'         => [
				'type'            => 'wrapped_html',
				'label'           => esc_html__( 'Configure PayPal Legacy:', 'event-tickets' ),
				'html'            => '<p>' . $paypal_setup_note . '</p>',
				'validation_type' => 'html',
			],
			$this->option_email             => [
				'type'            => 'email',
				'label'           => esc_html__( 'PayPal email to receive payments:', 'event-tickets' ),
				'size'            => 'large',
				'default'         => '',
				'validation_type' => 'email',
				'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
			],
			$this->option_ipn_enabled       => [
				'type'            => 'radio',
				'label'           => esc_html__( "Have you enabled instant payment notifications (IPN) in your PayPal account's Selling Tools?", 'event-tickets' ),
				'options'         => [
					'yes' => __( 'Yes', 'event-tickets' ),
					'no'  => __( 'No', 'event-tickets' ),
				],
				'size'            => 'large',
				'default'         => 'no',
				'validation_type' => 'options',
				'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
			],
			$this->option_ipn_address_set   => [
				'type'            => 'radio',
				'label'           => $ipn_setup_line,
				'options'         => [
					'yes' => __( 'Yes', 'event-tickets' ),
					'no'  => __( 'No', 'event-tickets' ),
				],
				'size'            => 'large',
				'default'         => 'no',
				'validation_type' => 'options',
				'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
			],
			'ticket-paypal-ipn-config-status' => [
				'type'            => 'wrapped_html',
				'html'            => $ipn_config_status,
				'size'            => 'large',
				'default'         => 'no',
				'validation_type' => 'html',
				'class'           => 'indent light-bordered',
			],
		];

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Links $paypal_links */
			$paypal_links = tribe( 'tickets.commerce.paypal.links' );

			$ipn_notify_note = sprintf(
				// Translators: %s: The PayPal notification history link.
				esc_html__( 'You can see and manage your IPN Notifications history from the IPN Notifications settings area (%s).', 'event-tickets' ),
				// The following string is returned already escaped.
				$paypal_links->ipn_notification_history( 'tag' )
			);

			$ipn_notify_url_tooltip = sprintf(
				// Translators: %s: The PayPal notification settings link.
				esc_html__( 'Override the default IPN notify URL with this value. This value must be the same set in PayPal IPN Notifications settings area (%s).', 'event-tickets' ),
				// The following string is returned already escaped.
				$paypal_links->ipn_notification_settings( 'tag' )
			);

			$settings['ticket-paypal-notify-history'] = [
				'type'            => 'wrapped_html',
				'html'            => '<p>' . $ipn_notify_note . '</p>',
				'size'            => 'medium',
				'validation_type' => 'html',
				'class'           => 'indent light-bordered',
			];

			$settings[ $this->option_ipn_notify_url ] = [
				'type'            => 'text',
				'label'           => esc_html__( 'IPN Notify URL', 'event-tickets' ),
				'tooltip'         => $ipn_notify_url_tooltip,
				'default'         => $home_url,
				'validation_type' => 'html',
			];
		}

		return $settings;
	}

}
