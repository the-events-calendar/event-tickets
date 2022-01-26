<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;
use Tribe__Tickets__Main;

/**
 * The Stripe specific settings.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Settings extends Abstract_Settings {

	/**
	 * @inheritDoc
	 */
	public static $option_sandbox = 'tickets-commerce-stripe-sandbox';

	public static $option_statement_descriptor = 'tickets-commerce-stripe-statement-descriptor';

	public static $option_collect_billing_details = 'tickets-commerce-stripe-billing-details';

	public static $option_stripe_receipt_emails = 'tickets-commerce-stripe-receipt-emails';

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		return [
			'tickets-commerce-stripe-commerce-configure' => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			],
			'tickets-commerce-stripe-settings-heading'   => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Stripe Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			static::$option_statement_descriptor         => [
				'type'                => 'text',
				'label'               => esc_html__( 'Statement Descriptor', 'event-tickets' ),
				'tooltip'             => esc_html( 'This is the text that appears on the ticket purchaser bank statements. If left blank, the default settings from the Stripe account will be used.', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_collect_billing_details      => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Collect Billing Details', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables sending billing details to Stripe. This is not required, but may be necessary in some cases.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			static::$option_stripe_receipt_emails        => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Stripe Receipt Emails', 'event-tickets' ),
				'tooltip'         => esc_html__( 'If this option is selected, ticket buyers will get stripe receipts, as well as Event Tickets confirmation emails.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function get_connection_settings_html() {
		/** @var \Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'plugin_url' => Tribe__Tickets__Main::instance()->plugin_url,
//			'merchant'              => $merchant,
//			'is_merchant_connected' => $merchant->is_connected(),
//			'is_merchant_active'    => $merchant->is_active(),
			'signup'     => tribe( Signup::class ),
		];

		// $admin_views->add_template_globals( $context );

		return $admin_views->template( 'settings/tickets-commerce/stripe/main', $context, false );
	}
}