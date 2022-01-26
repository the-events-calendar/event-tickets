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

	const PAYMENT_ELEMENT_SLUG = 'payment';
	const CARD_ELEMENT_SLUG = 'card';

	/**
	 * @inheritDoc
	 */
	public static $option_sandbox = 'tickets-commerce-stripe-sandbox';

	/**
	 * Option name for the statement descriptor field
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_statement_descriptor = 'tickets-commerce-stripe-statement-descriptor';

	/**
	 * Option name for the collect billing details field
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_collect_billing_details = 'tickets-commerce-stripe-billing-details';

	/**
	 * Option name for the stripe receipt emails field
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_stripe_receipt_emails = 'tickets-commerce-stripe-receipt-emails';

	/**
	 * Option name for the stripe checkout element field
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_checkout_element = 'tickets-commerce-stripe-checkout-element';

	/**
	 * Option name for the card element credit card fields to use
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_checkout_element_card_fields = 'tickets-commerce-stripe-checkout-element-card-fields';

	/**
	 * Option name for the payment element payment methods allowed
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_checkout_element_payment_methods = 'tickets-commerce-stripe-checkout-element-payment-methods';

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		$settings = [
			'tickets-commerce-stripe-commerce-configure'        => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			],
			'tickets-commerce-stripe-settings-heading'          => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Stripe Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			static::$option_statement_descriptor                => [
				'type'                => 'text',
				'label'               => esc_html__( 'Statement Descriptor', 'event-tickets' ),
				'tooltip'             => esc_html( 'This is the text that appears on the ticket purchaser bank statements. If left blank, the default settings from the Stripe account will be used.', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_collect_billing_details             => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Collect Billing Details', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables sending billing details to Stripe. This is not required, but may be necessary in some cases.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			static::$option_stripe_receipt_emails               => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Stripe Receipt Emails', 'event-tickets' ),
				'tooltip'         => esc_html__( 'If this option is selected, ticket buyers will get stripe receipts, as well as Event Tickets confirmation emails.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			'tickets-commerce-stripe-checkout-settings-heading' => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Checkout Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			static::$option_checkout_element                    => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Checkout Type', 'event-tickets' ),
				'tooltip'         => esc_html( 'Stripe offers two main ways to pay at checkout. Card Element and Payment Element. You can read about them here.' ),
				'default'         => self::PAYMENT_ELEMENT_SLUG,
				'validation_type' => 'options',
				'options'         => [
					self::PAYMENT_ELEMENT_SLUG => esc_html__( 'Accept payments with one or multiple payment methods, including cards.', 'event-tickets' ),
					self::CARD_ELEMENT_SLUG    => esc_html__( 'Accept only card payments', 'event-tickets' ),
				],
				'tooltip_first'   => true,
			],
			static::$option_checkout_element_card_fields        => [
				'type'            => 'select',
				'label'           => esc_html__( 'Credit Card Fields (Card Element)', 'event-tickets' ),
				'tooltip'         => esc_html( 'Tooltip missing' ), // @todo add proper tooltip
				'default'         => 'compact',
				'conditional'     => tribe_get_option( static::$option_checkout_element ) === self::CARD_ELEMENT_SLUG,
				'validation_type' => 'options',
				'options'         => [
					'compact' => 'Compact Field. All CC fields in a single line.',
					'separate' => 'Separate Fields for each CC information.',
				],
				'tooltip_first'   => true,
			],
			static::$option_checkout_element_payment_methods        => [
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Payment Methods (Payment Element)', 'event-tickets' ),
				'tooltip'         => esc_html( 'Tooltip missing' ), // @todo add proper tooltip
				'default'         => 'a', // @todo add proper defaults
				'conditional'     => tribe_get_option( static::$option_checkout_element ) === self::PAYMENT_ELEMENT_SLUG,
				'validation_type' => 'options',
				'options'         => [
					'a' => 'A', // @todo fetch proper payment method options
					'b' => 'B',
				],
				'tooltip_first'   => true,
			],
		];

		/**
		 * Allow filtering the list of Stripe settings.
		 *
		 * @since TBD
		 *
		 * @param array $settings The list of Stripe Commerce settings.
		 */
		return apply_filters( 'tribe_tickets_commerce_stripe_settings', $settings );
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