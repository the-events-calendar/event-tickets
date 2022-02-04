<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main;

/**
 * The Stripe specific settings.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Settings extends Abstract_Settings {

	/**
	 * DB identifier for the Payment Element selection
	 *
	 * @var string
	 */
	const PAYMENT_ELEMENT_SLUG = 'payment';

	/**
	 * DB identifier for the Card Element selection
	 *
	 * @var string
	 */
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
			'tickets-commerce-stripe-commerce-configure'             => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			],
			'tickets-commerce-stripe-settings-heading'               => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Stripe Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			'tickets-commerce-gateway-settings-group-header-general' => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . __( 'General', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			static::$option_statement_descriptor                     => [
				'type'                => 'text',
				'label'               => esc_html__( 'Statement Descriptor', 'event-tickets' ),
				'tooltip'             => esc_html( 'This is the text that appears on the ticket purchaser bank statements. If left blank, the default settings from the Stripe account will be used.', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
			],
			static::$option_collect_billing_details                  => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Collect Billing Details', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables sending billing details to Stripe. This is not required, but may be necessary in some cases.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			static::$option_stripe_receipt_emails                    => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Stripe Receipt Emails', 'event-tickets' ),
				'tooltip'         => esc_html__( 'If this option is selected, ticket buyers will get stripe receipts, as well as Event Tickets confirmation emails.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			'tickets-commerce-stripe-checkout-settings-heading'      => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Checkout Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],

			'tickets-commerce-gateway-settings-group-header-checkout' => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . __( 'Checkout', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			static::$option_checkout_element                          => [
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
			static::$option_checkout_element_card_fields              => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Credit Card Fields (Card Element)', 'event-tickets' ),
				'tooltip'         => esc_html( 'Tooltip missing' ), // @todo add proper tooltip
				'default'         => 'compact',
				'conditional'     => tribe_get_option( static::$option_checkout_element ) === self::CARD_ELEMENT_SLUG,
				'validation_type' => 'options',
				'options'         => [
					'compact'  => 'Compact Field. All CC fields in a single line using default Stripe styles.',
					'separate' => 'Separate Fields for each CC information, unstyled.',
				],
				'tooltip_first'   => true,
			],
			static::$option_checkout_element_payment_methods          => [
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Payment Methods (Payment Element)', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Which payment methods should be offered to your customers? Only select methods previously enabled in your Stripe account.' ),
				// @todo add proper tooltip
				'default'         => 'card',
				'conditional'     => tribe_get_option( static::$option_checkout_element ) === self::PAYMENT_ELEMENT_SLUG,
				'validation_type' => 'options_multi',
				'options'         => $this->get_payment_methods_available_by_currency(),
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
		return apply_filters( 'tec_tickets_commerce_stripe_settings', $settings );
	}

	/**
	 * Filters the general list of payment methods to grab only those available to the currency configured in Tickets
	 * Commerce.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_payment_methods_available_by_currency() {
		$currency          = Currency::get_currency_code();
		$payment_methods   = $this->get_payment_methods_available();
		$available_methods = [];

		foreach ( $payment_methods as $method => $configs ) {
			if ( ! in_array( $currency, $configs['currencies'], true ) ) {
				continue;
			}

			$available_methods[ $method ] = $configs['label'];
		}

		$available_methods['card'] = esc_html__( 'Credit Cards', 'event-tickets' );

		/**
		 * Allows filtering the list of available Payment Methods
		 *
		 * @since TBD
		 *
		 * @param array   $available_methods the list of payment methods available to the current currency
		 * @param string  $currency          the currency configured for Tickets Commerce
		 * @param array[] $payment_methods   the complete list of available Payment Methods in Stripe
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_payment_methods_available', $available_methods, $currency, $payment_methods );
	}

	/**
	 * Returns the list of available Payment Methods.
	 * Methods and currencies sourced from
	 * https://stripe.com/docs/payments/payment-methods/integration-options#payment-method-product-support
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	private function get_payment_methods_available() {
		$available_methods = [
			'afterpay_clearpay' => [
				'currencies' => [ 'AUD', 'CAD', 'GBP', 'NZD' ],
				'label'      => esc_html__( 'AfterPay and ClearPay', 'event-tickets' ),
			],
			'alipay'            => [
				'currencies' => [ 'AUD', 'CAD', 'CNY', 'EUR', 'GBP', 'HKD', 'JPY', 'MYR', 'NZD', 'SGD', 'USD' ],
				'label'      => esc_html__( 'Alipay', 'event-tickets' ),
			],
			'bacs_debit'        => [
				'currencies' => [ 'GBP' ],
				'label'      => esc_html__( 'Bacs Direct Debit', 'event-tickets' ),
			],
			'giropay'           => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'Giropay', 'event-tickets' ),
			],
			'klarna'            => [
				'currencies' => [ 'DKK', 'EUR', 'GBP', 'NOK', 'SEK', 'USD' ],
				'label'      => esc_html__( 'Klarna', 'event-tickets' ),
			],
			'acss_debit'        => [
				'currencies' => [ 'CAD', 'USD' ],
				'label'      => esc_html__( 'Pre-authorized debit in Canada', 'event-tickets' ),
			],
		];

		$tickets_plus_methods = [];

		if ( $this->is_licensed_plugin() ) {
			if ( class_exists( 'TEC\Tickets_Plus\Commerce\Gateways\Stripe\Settings' ) ) {
				$tickets_plus_methods = tribe( \TEC\Tickets_Plus\Commerce\Gateways\Stripe\Settings::class )->get_payment_methods_available();
			}
		}

		return array_merge( $available_methods, $tickets_plus_methods );
	}

	/**
	 * @inheritDoc
	 */
	public function get_connection_settings_html() {
		/** @var \Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'plugin_url'      => Tribe__Tickets__Main::instance()->plugin_url,
			'merchant_status' => tribe( Merchant::class )->get_connection_status(),
			'signup'          => tribe( Signup::class ),
			'merchant'        => tribe( Merchant::class ),
			'fee_is_applied'  => ! $this->is_licensed_plugin( true ),
		];

		return $admin_views->template( 'settings/tickets-commerce/stripe/main', $context, false );
	}

	/**
	 * Should the processor fee be applied to this site's transactions?
	 *
	 * @since TBD
	 *
	 * @param bool $revalidate whether to submit a new validation API request
	 *
	 * @return bool
	 */
	public function is_licensed_plugin( $revalidate = false ) {

		if ( class_exists( 'Tribe__Tickets_Plus__PUE' ) ) {
			if ( tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid( $revalidate ) ) {
				return true;
			}
		}

		return false;
	}
}