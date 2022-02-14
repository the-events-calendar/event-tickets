<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Settings;
use TEC\Tickets\Commerce\Notice_Handler;
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
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_ELEMENT_SLUG = 'payment';

	/**
	 * DB identifier for the Card Element selection
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const CARD_ELEMENT_SLUG = 'card';

	/**
	 * DB identifier for the Card Element Compact Layout
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const COMPACT_CARD_ELEMENT_SLUG = 'compact';

	/**
	 * DB identifier for the Card Element Separate Layout
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SEPARATE_CARD_ELEMENT_SLUG = 'separate';

	/**
	 * DB identifier for the default methods set for the Payment Element
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	const DEFAULT_PAYMENT_ELEMENT_METHODS = [ 'card' ];

	/**
	 * Connection details fetched from the Stripe API on page-load
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	public $connection_status;

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
	 * Constructor
	 */
	public function __construct() {
		$this->set_connection_status();
	}

	/**
	 * Set the internal parameter w/ account details received from the Stripe API
	 *
	 * @since TBD
	 */
	public function set_connection_status() {
		$this->connection_status = tribe( Merchant::class )->check_account_status();
	}

	/**
	 * Trigger a dismissible admin notice if Tickets Commerce and Stripe currencies are not the same.
	 *
	 * @since TBD
	 */
	public function alert_currency_mismatch() {
		$stripe_currency = strtoupper( tribe( Merchant::class )->get_merchant_currency() );
		$site_currency = strtoupper( Currency::get_currency_code() );

		if ( $site_currency === $stripe_currency ) {
			return;
		}

		tribe( Notice_Handler::class )->trigger_admin( 'tc-stripe-currency-mismatch', [
			'content' =>
				sprintf(
				// Translators: %1$s The tickets commerce currency. %2$s: The currency from the Stripe account.
					__( 'Tickets Commerce is configured to use %1$s as its currency but the default currency for the connected stripe account is %2$s. If you believe this is an error, you can modify the Tickets Commerce currency in the main Payments tab. Using different currencies for Tickets Commerce and Stripe may result in exchange rates and conversions being handled by Stripe.', 'event-tickets' ),
					$site_currency, $stripe_currency ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		$currency_code = Currency::get_currency_code();
		$currency_name = Currency::get_currency_name( $currency_code );
		$plus_link     = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$checkout_type_tooltip = sprintf(
			// Translators: %1$s: Opening `<a>` tag for Stripe docs link. %2$s: Closing `<a>` tag for Stripe docs link.
			__( 'Additional payment methods are available based on currency and location and must be enabled individually within your Stripe account.  Learn more about Stripe checkout and payment configuration %1$shere%2$s.', 'event-tickets' ),
			'<a href="https://stripe.com/docs/payments/checkout/web#checkout-payment-method-options" target="_blank" rel="noopener noreferrer">', // @todo: @juanfra: Update this link with our KB article.
			'</a>'
		);

		$payment_methods_tooltip = sprintf(
			// Translators: %1$s: Opening `<strong>` tag. %2$s: The currency name. %3$s: Closing `</strong>` tag. %4$s: Opening `<a>` tag for Stripe link. %5$s: Closing `</a>` tag.
			__( 'Payment methods available for %1$s%2$s%3$s.<br /><br /> The payment methods listed here are dependent on the currency selected for Tickets Commerce and the currency each payment method support. You can review the payment methods and their availablity for each currency on %4$sStripe\'s documentation%5$s.<br /><br />', 'event-tickets' ),
			'<strong>',
			$currency_name,
			'</strong>',
			'<a href="https://stripe.com/docs/payments/payment-methods/integration-options" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		// @todo @fe @juanfra DQA note: Need adjustment on the wording.
		$stripe_message = sprintf(
			// Translators: %1$s: The Event Tickets Plus link.
			esc_html__( 'You are using the free Stripe payment gateway integration. This includes an additional 2%% fee for processing ticket sales. This fee is removed by activating Event Tickets Plus. %1$s.', 'event-tickets' ),
			$plus_link
		);
		$main_settings = [
			'tickets-commerce-stripe-commerce-description' => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-description">' . $stripe_message . '</div>',
			],
			'tickets-commerce-stripe-commerce-configure'   => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			]
		];
		
		// If gateway isn't connected/active, only show the connection settings.
		$is_connected = tribe( Gateway::class )->is_active();
		if ( ! $is_connected ) {
			/**
			 * Allow filtering the list of Stripe settings.
			 *
			 * @since TBD
			 *
			 * @param array $settings     The list of Stripe Commerce settings.
			 * @param bool  $is_connected Whether or not gateway is connected.
			 */
			return apply_filters( 'tec_tickets_commerce_stripe_settings', $main_settings, $is_connected );
		}
		
		$connected_settings = [
			'tickets-commerce-stripe-settings-heading'     => [
				'type' => 'html',
				'html' => '<h3 class="tribe-dependent -input">' . __( 'Stripe Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			'tickets-commerce-gateway-settings-group-header-general' => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . __( 'General', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			static::$option_statement_descriptor           => [
				'type'                => 'text',
				'label'               => esc_html__( 'Statement Descriptor', 'event-tickets' ),
				'tooltip'             => esc_html__( 'This is the text that appears on the ticket purchaser bank statements. If left blank, the descriptor set in Stripe will be used.', 'event-tickets' ),
				'size'                => 'medium',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
				'placeholder'         => ! empty( $this->connection_status['statement_descriptor'] ) ? esc_textarea( $this->connection_status['statement_descriptor'] ) : '',
			],
			static::$option_stripe_receipt_emails          => [
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

			'tickets-commerce-gateway-settings-group-header-checkout' => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . __( 'Checkout', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			static::$option_checkout_element               => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Checkout Type', 'event-tickets' ),
				'tooltip'         => $checkout_type_tooltip,
				'default'         => self::CARD_ELEMENT_SLUG,
				'validation_type' => 'options',
				'options'         => [
					self::CARD_ELEMENT_SLUG    => esc_html__( 'Accept only credit card payments.', 'event-tickets' ),
					self::PAYMENT_ELEMENT_SLUG => esc_html__( 'Accept credit card payments and additional payment methods configured in Stripe.', 'event-tickets' ),
				],
			],
			static::$option_checkout_element_card_fields   => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Credit Card field format', 'event-tickets' ),
				'default'         => self::COMPACT_CARD_ELEMENT_SLUG,
				'fieldset_attributes' => [
					'data-depends'              => '#tribe-field-' . static::$option_checkout_element . '-' . self::CARD_ELEMENT_SLUG,
					'data-condition-is-checked' => true,
				],
				'class'           => 'tribe-dependent',
				'validation_type' => 'options',
				'options'         => [
					self::COMPACT_CARD_ELEMENT_SLUG  => sprintf(
						// Translators: %1$s: Opening `<span>` tag. %2$s: Closing `</span>` tag.
						__( 'Single field. %1$sFor streamlined checkout.%2$s', 'event-tickets' ),
						'<span class="tribe_soft_note">',
						'</span>'
					),
					self::SEPARATE_CARD_ELEMENT_SLUG => sprintf(
						// Translators: %1$s: Opening `<span>` tag. %2$s: Closing `</span>` tag.
						__( 'Multiple fields. %1$sFor standard checkout.%2$s', 'event-tickets' ),
						'<span class="tribe_soft_note">',
						'</span>'
					),
				],
			],
			static::$option_checkout_element_payment_methods => [
				'type'            => 'checkbox_list',
				'label'           => esc_html__( 'Payment methods accepted', 'event-tickets' ),
				'tooltip'         => $payment_methods_tooltip,
				'default'         => self::DEFAULT_PAYMENT_ELEMENT_METHODS,
				'fieldset_attributes' => [
					'data-depends'              => '#tribe-field-' . static::$option_checkout_element . '-' . self::PAYMENT_ELEMENT_SLUG,
					'data-condition-is-checked' => true,
				],
				'class'           => 'tribe-dependent',
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
		 * @param array $settings     The list of Stripe Commerce settings.
		 * @param bool  $is_connected Whether or not gateway is connected.
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_settings', array_merge( $main_settings, $connected_settings ), $is_connected );
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
		return apply_filters( 'tec_tickets_commerce_stripe_payment_methods_by_currency', $available_methods, $currency, $payment_methods );
	}

	/**
	 * Returns the list of available Payment Methods.
	 *
	 * @link https://stripe.com/docs/payments/payment-methods/integration-options#payment-method-product-support
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	private function get_payment_methods_available() {
		$available_methods = [
			'afterpay_clearpay' => [
				'currencies' => [ 'AUD', 'CAD', 'GBP', 'NZD', 'USD' ],
				'label'      => esc_html__( 'AfterPay and ClearPay', 'event-tickets' ),
			],
			'alipay'            => [
				'currencies' => [ 'AUD', 'CAD', 'CNY', 'EUR', 'GBP', 'HKD', 'JPY', 'MYR', 'NZD', 'SGD', 'USD' ],
				'label'      => esc_html__( 'Alipay', 'event-tickets' ),
			],
			'giropay'           => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'Giropay', 'event-tickets' ),
			],
			'klarna'            => [
				'currencies' => [ 'DKK', 'EUR', 'GBP', 'NOK', 'SEK', 'USD' ],
				'label'      => esc_html__( 'Klarna', 'event-tickets' ),
			],
		];

		/**
		 * Allows for filtering the list of available payment methods.
		 *
		 * @since TBD
		 *
		 * @param array $available_methods the list of payment methods available.
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_payment_methods_available', $available_methods );
	}

	/**
	 * Setup basic defaults once a new account is onboarded.
	 *
	 * @since TBD
	 */
	public function setup_account_defaults() {
		if ( empty( $this->connection_status ) ) {
			$this->set_connection_status();
		}

		update_option( Merchant::$merchant_default_currency_option_key, $this->connection_status['default_currency'] );

		if ( empty( tribe_get_option( static::$option_checkout_element ) ) ) {
			tribe_update_option( static::$option_checkout_element, static::PAYMENT_ELEMENT_SLUG );
		}

		if ( empty( tribe_get_option( static::$option_checkout_element_card_fields ) ) ) {
			tribe_update_option( static::$option_checkout_element_card_fields, static::COMPACT_CARD_ELEMENT_SLUG );
		}

		if ( empty( tribe_get_option( static::$option_checkout_element_payment_methods ) ) ) {
			tribe_update_option( static::$option_checkout_element_payment_methods, static::DEFAULT_PAYMENT_ELEMENT_METHODS );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_connection_settings_html() {
		/** @var \Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'plugin_url'      => Tribe__Tickets__Main::instance()->plugin_url,
			'merchant_status' => $this->connection_status,
			'signup'          => tribe( Signup::class ),
			'merchant'        => tribe( Merchant::class ),
			'fee_is_applied'  => apply_filters( 'tec_tickets_commerce_stripe_fee_is_applied_notice', true ),
		];

		return $admin_views->template( 'settings/tickets-commerce/stripe/main', $context, false );
	}

}