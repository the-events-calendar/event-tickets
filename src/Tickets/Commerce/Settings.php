<?php
/**
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Admin\Featured_Settings;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Traits\Has_Mode;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Settings as Tickets_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Template;
use Tribe__Field_Conditional;
use Tribe__Tickets__Main;
use WP_Admin_Bar;

/**
 * The Tickets Commerce Global settings.
 *
 * This class will contain all of the settings handling and admin settings config implementation from
 * Tribe__Tickets__Commerce__PayPal__Main that is gateway-agnostic.
 *
 * @since 5.1.6
 * @package Tribe\Tickets\Commerce\Tickets_Commerce
 */
class Settings {

	use Has_Mode;

	/**
	 * The option key for sandbox.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_sandbox = 'tickets-commerce-sandbox';

	/**
	 * The option key for currency code.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_currency_code = 'tickets-commerce-currency-code';

	/**
	 * The option key for currency decimal separator.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $option_currency_decimal_separator = 'tickets-commerce-currency-decimal-separator';

	/**
	 * The option key for currency thousands separator.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $option_currency_thousands_separator = 'tickets-commerce-currency-thousands-separator';

	/**
	 * The option key for currency number of decimals.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $option_currency_number_of_decimals = 'tickets-commerce-currency-number-of-decimals';

	/**
	 * The option key for currency position.
	 *
	 * @since 5.4.2
	 *
	 * @var string
	 */
	public static $option_currency_position = 'tickets-commerce-currency-position';

	/**
	 * The option key for stock handling.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_stock_handling = 'tickets-commerce-stock-handling';

	/**
	 * The option key for success page.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_success_page = 'tickets-commerce-success-page';

	/**
	 * The option key for checkout page.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_checkout_page = 'tickets-commerce-checkout-page';

	/**
	 * The option key for confirmation email sender email.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_confirmation_email_sender_email = 'tickets-commerce-confirmation-email-sender-email';

	/**
	 * The option key for confirmation email sender name.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_confirmation_email_sender_name = 'tickets-commerce-confirmation-email-sender-name';

	/**
	 * The option key for confirmation email subject.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_confirmation_email_subject = 'tickets-commerce-confirmation-email-subject';

	/**
	 * Stores the instance of the template engine that we will use for rendering differentelements.
	 *
	 * @since 5.3.0
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Settings constructor.
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		// Configure which mode we are in.
		$this->set_mode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' );
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 5.3.0
	 *
	 * @return Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/tickets-commerce' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Gets the URL to the Tickets Commerce settings page.
	 *
	 * @since 5.24.0
	 *
	 * @param array $args Optional. Additional arguments to add to the URL. Default empty array.
	 *
	 * @return string The URL to the Tickets Commerce settings page.
	 */
	public function get_url( array $args = [] ) {
		$defaults = [
			'page' => 'tec-tickets-settings',
			'tab'  => 'payments',
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Determine whether Tickets Commerce is in test mode.
	 *
	 * @since 5.3.0    moved to Settings class
	 * @since 5.1.6
	 * @since 5.24.0 Use tec_tickets_commerce_is_sandbox_mode() instead.
	 *
	 * @return bool Whether Tickets Commerce is in test mode.
	 */
	public static function is_test_mode() {
		return tec_tickets_commerce_is_sandbox_mode();
	}

	/**
	 * Display admin bar when using the Test Mode for payments.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
	 *
	 * @return bool
	 */
	public function include_admin_bar_test_mode( WP_Admin_Bar $wp_admin_bar ) {
		if (
			! $this->is_sandbox() ||
			! current_user_can( 'manage_options' )
		) {
			return false;
		}
		$url = tribe( Plugin_Settings::class )->get_url( [ 'tab' => 'payments' ] );

		// Add the main site admin menu item.
		$wp_admin_bar->add_menu(
			[
				'id'     => 'tec-tickets-commerce-sandbox-notice',
				'href'   => $url,
				'parent' => 'top-secondary',
				'title'  => __( 'Tickets Commerce Test Mode Active', 'event-tickets' ),
				'meta'   => [
					'class' => 'tec-tickets-commerce-sandbox-mode-active',
				],
			]
		);

		// Force this asset to load whn we add this to the menu.
		tribe_asset_enqueue( 'tec-tickets-commerce-gateway-paypal-global-admin-styles' );

		return true;
	}

	/**
	 * Get the list of settings for Tickets Commerce.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for Tickets Commerce.
	 */
	public function get_settings() {

		$section_gateway = tribe( Payments_Tab::class )->get_section_gateway();
		if ( ! empty( $section_gateway ) ) {
			return $section_gateway->get_settings();
		}

		// @todo Replace this with a better and more performant REST API based solution.
		$page_args = [
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		];

		$pages = get_pages( $page_args );

		if ( ! empty( $pages ) ) {
			$pages = wp_list_pluck( $pages, 'post_title', 'ID' );
		}

		// Add an initial empty selection to the start.
		$pages = [ 0 => __( '-- No page set --', 'event-tickets' ) ] + $pages;

		$success_shortcode  = Shortcodes\Success_Shortcode::get_wp_slug();
		$checkout_shortcode = Shortcodes\Checkout_Shortcode::get_wp_slug();

		$tc_currency_options = tribe( Currency::class )->get_currency_code_options();

		$current_user = get_user_by( 'id', get_current_user_id() );

		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.LongIndexSpaceBeforeDoubleArrow, WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		$settings = [
			'tickets-commerce-settings-general-group-start'     => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tickets-commerce-settings-general-heading'         => [
				'type' => 'html',
				'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . __( 'General', 'event-tickets' ) . '</h3>',
			],
			static::$option_sandbox                             => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Enable Test Mode', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables Test mode for testing payments. Any payments made will be done on "sandbox" accounts.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			static::$option_stock_handling                      => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Stock Handling', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
					// Translators: %s: The word "ticket" in lowercase.
						_x( 'When a customer purchases a %s, the payment gateway might flag the order as Pending. The order will be Complete once payment is confirmed by the payment gateway.', 'tickets fields settings paypal stock handling', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_stock_handling' )
					)
				),
				'default'         => Pending::SLUG,
				'validation_type' => 'options',
				'options'         => [
					Pending::SLUG   => sprintf(
					// Translators: %1$s: The word "ticket" in lowercase. %2$s: `<strong>` opening tag. %3$s: `</strong>` closing tag.
						esc_html__( 'Decrease available %1$s stock and send the %1$s to the customer as soon as a %2$sPending%3$s order is created.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' ),
						'<strong>',
						'</strong>'
					),
					Completed::SLUG => sprintf(
					// Translators: %1$s: The word "ticket" in lowercase. %2$s: `<strong>` opening tag. %3$s: `</strong>` closing tag.
						esc_html__( 'Only decrease available %1$s stock and send the %1$s to the customer if an order is confirmed as %2$sCompleted%3$s by the payment gateway.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' ),
						'<strong>',
						'</strong>'
					),
				],
				'tooltip_first'   => true,
			],
			'tickets-commerce-settings-general-group-end'       => [
				'type' => 'html',
				'html' => '</div>',
			],
			'tickets-commerce-settings-currency-group-start'    => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tickets-commerce-settings-currency-heading'        => [
				'type' => 'html',
				'html' => '<h3 id="tickets-commerce-settings-currency" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . __( 'Currency', 'event-tickets' ) . '</h3>',
			],
			static::$option_currency_code                       => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The currency that will be used for Tickets Commerce transactions.', 'event-tickets' ),
				'default'         => Currency::$currency_code_fallback,
				'validation_type' => 'options',
				'options'         => $tc_currency_options,
			],
			static::$option_currency_decimal_separator          => [
				'type'            => 'text',
				'label'           => esc_html__( 'Decimal Separator', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This sets the decimal separator of displayed prices.', 'event-tickets' ),
				'default'         => Currency::$currency_code_decimal_separator,
				'validation_callback' => 'is_string',
			],
			static::$option_currency_thousands_separator        => [
				'type'            => 'text',
				'label'           => esc_html__( 'Thousands Separator', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This sets the thousand separator of displayed prices', 'event-tickets' ),
				'default'         => Currency::$currency_code_thousands_separator,
				'validation_callback' => 'is_string',
			],
			static::$option_currency_number_of_decimals         => [
				'type'            => 'text',
				'label'           => esc_html__( 'Number of Decimals', 'event-tickets' ),
				'tooltip'         => esc_html__( 'This sets the number of decimal points shown in displayed prices.', 'event-tickets' ),
				'default'         => Currency::$currency_code_number_of_decimals,
				'validation_type' => 'int',
			],
			static::$option_currency_position                   => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Position', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The position of the currency symbol as it relates to the ticket values.', 'event-tickets' ),
				'default'         => 'prefix',
				'validation_type' => 'options',
				'options'         => [
					'prefix'  => esc_html__( 'Before', 'event-tickets' ),
					'postfix' => esc_html__( 'After', 'event-tickets' ),
				],
			],
			'tickets-commerce-settings-currency-group-end'      => [
				'type' => 'html',
				'html' => '</div>',
			],
			'tickets-commerce-settings-page-config-group-start' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tickets-commerce-settings-page-heading'            => [
				'type' => 'html',
				'html' => '<h3  class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . __( 'Pages Configuration', 'event-tickets' ) . '</h3>',
			],
			static::$option_checkout_page                       => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Checkout page', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
					// Translators: %s: The [shortcode] for the success page.
						__( 'This is the page where customers go to complete their purchase. Use the %s shortcode to display the checkout experience in the page content.', 'event-tickets' ),
						"[$checkout_shortcode]"
					)
				),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			],
			static::$option_success_page                        => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Success page', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
					// Translators: %s: The [shortcode] for the success page.
						__( 'After a successful order, users will be redirected to this page. Use the %s shortcode to display the order confirmation to the user in the page content.', 'event-tickets' ),
						"[$success_shortcode]"
					)
				),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			],
			'tickets-commerce-settings-page-config-group-end'   => [
				'type' => 'html',
				'html' => '</div>',
			],
		];

		if ( ! tec_tickets_emails_is_enabled() ) {
			$email_settings = [
				'tickets-commerce-settings-email-group-start'   => [
					'type' => 'html',
					'html' => '<div class="tec-settings-form__content-section">',
				],
				'tickets-commerce-email-settings-heading'       => [
					'type' => 'html',
					'html' => '<h3  class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . __( 'Emails', 'event-tickets' ) . '</h3>',
				],
				static::$option_confirmation_email_sender_email => [
					'type'            => 'email',
					'label'           => esc_html__( 'Confirmation email sender address', 'event-tickets' ),
					'tooltip'         => esc_html(
						sprintf(
						// Translators: %s: The word "tickets" in lowercase.
							_x( 'Email address that %s customers will receive confirmation from. Leave empty to use the default WordPress site email address.', 'tickets fields settings confirmation email', 'event-tickets' ),
							tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_paypal_confirmation_email' )
						)
					),
					'size'            => 'medium',
					'default'         => $current_user->user_email,
					'validation_type' => 'email',
					'can_be_empty'    => true,
				],
				static::$option_confirmation_email_sender_name  => [
					'type'                => 'text',
					'label'               => esc_html__( 'Confirmation email sender name', 'event-tickets' ),
					'tooltip'             => esc_html(
						sprintf(
						// Translators: %s: The word "ticket" in lowercase.
							_x( 'Sender name of the confirmation email sent to customers when confirming a %s purchase.', 'tickets fields settings paypal email sender', 'event-tickets' ),
							tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_email_sender' )
						)
					),
					'size'                => 'medium',
					'default'             => $current_user->user_nicename,
					'validation_callback' => 'is_string',
					'validation_type'     => 'textarea',
				],
				static::$option_confirmation_email_subject      => [
					'type'                => 'text',
					'label'               => esc_html__( 'Confirmation email subject', 'event-tickets' ),
					'tooltip'             => esc_html(
						sprintf(
						// Translators: %s: The word "ticket" in lowercase.
							_x( 'Subject of the confirmation email sent to customers when confirming a %s purchase.', 'tickets fields settings paypal email subject', 'event-tickets' ),
							tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_email_subject' )
						)
					),
					'size'                => 'large',
					'default'             => esc_html(
						sprintf(
						// Translators: %s: The word "tickets" in lowercase.
							_x( 'You have %s!', 'tickets fields settings paypal email subject', 'event-tickets' ),
							tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_paypal_email_subject' )
						)
					),
					'validation_callback' => 'is_string',
					'validation_type'     => 'textarea',
				],
				'tickets-commerce-settings-email-group-end'     => [
					'type' => 'html',
					'html' => '</div>',
				],
			];
			// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.LongIndexSpaceBeforeDoubleArrow, WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

			$settings = array_merge( $settings, $email_settings );
		}

		// Add featured settings to top of other settings.
		$featured_settings = [
			'tc_featured_settings' => [
				'type' => 'html',
				'html' => tribe( Featured_Settings::class )->get_html(
					[
						'title'             => __( 'Payment Gateways', 'event-tickets' ),
						'description'       => __(
							'Set up a payment gateway to get started with Tickets Commerce. Enable multiple ' .
							'gateways for providing users additional options for users when purchasing tickets.',
							'event-tickets'
						),
						'content_template'  => $this->get_featured_gateways_html(),
						'links'             => [
							[
								'slug'     => 'help-1',
								'priority' => 10,
								'link'     => 'https://evnt.is/1axt',
								'html'     => __( 'Learn more about configuring payment options with Tickets Commerce', 'event-tickets' ),
								'target'   => '_blank',
								'classes'  => [],
							],
						],
						'classes'           => [],
						'container_classes' => [ 'tec-settings-form__content-section' ],
					]
				),
			],
		];

		$settings = array_merge( $featured_settings, $settings );

		/**
		 * Allow filtering the list of Tickets Commerce settings.
		 *
		 * @since 5.1.6
		 *
		 * @param array $settings The list of Tickets Commerce settings.
		 */
		$settings = apply_filters( 'tribe_tickets_commerce_settings', $settings );

		return array_merge( tribe( Payments_Tab::class )->get_fields(), $this->apply_commerce_enabled_conditional( $settings ) );
	}

	/**
	 * Returns the content for the main featured settings which displays the list of gateways.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_featured_gateways_html() {
		$manager  = tribe( Manager::class );
		$gateways = $manager->get_gateways();
		$template = $this->get_template();

		return $template->template( 'gateways/container', [ 'gateways' => $gateways, 'manager' => $manager ], false );
	}

	/**
	 * Handle setting up dependencies for all of the fields.
	 *
	 * @since 5.1.9
	 * @since 5.23.0 Return early if Tickets_Settings::$tickets_commerce_enabled is enabled.
	 *
	 * @param array[] $settings Which settings we are applying conditionals to.
	 *
	 * @return array[]
	 */
	public function apply_commerce_enabled_conditional( $settings ) {
		$validate_if = new Tribe__Field_Conditional( Tickets_Settings::$tickets_commerce_enabled, 'tribe_is_truthy' );

		if ( ! tribe_is_truthy( Tickets_Settings::$tickets_commerce_enabled ) ) {
			return $settings;
		}

		$fieldset_attributes = [
			'data-depends'              => '#' . Tickets_Settings::$tickets_commerce_enabled . '-input',
			'data-condition-is-checked' => '',
		];

		foreach ( $settings as $key => &$commerce_field ) {
			if ( isset( $commerce_field['class'] ) ) {
				$commerce_field['class'] .= ' tribe-dependent';
			} else {
				$commerce_field['class'] = 'tribe-dependent';
			}

			$commerce_field['fieldset_attributes'] = $fieldset_attributes;

			if ( 'checkbox_bool' === $commerce_field['type'] ) {
				$commerce_field['fieldset_attributes']['data-dependency-dont-disable'] = '1';
			}

			$commerce_field['validate_if'] = $validate_if;
		}

		return $settings;
	}

	/**
	 * If the provided meta value is from Ticket Commerce Module then re-slash the meta value.
	 *
	 * @since 5.1.10
	 *
	 * @param mixed $meta_value Metadata value.
	 *
	 * @return string
	 */
	public function skip_sanitization( $meta_value ) {
		if ( $meta_value === wp_unslash( Module::class ) ) {
			return Module::class;
		}

		return $meta_value;
	}

	/**
	 * Is a valid license of Event Tickets Plus available?
	 *
	 * @since 5.3.0
	 * @since 5.8.4 Added caching.
	 *
	 * @param bool $revalidate whether to submit a new validation API request.
	 *
	 * @return bool
	 */
	public static function is_licensed_plugin( $revalidate = false ) {
		if ( ! class_exists( 'Tribe__Tickets_Plus__PUE' ) ) {
			return false;
		}

		$pue = tribe( \Tribe__Tickets_Plus__PUE::class );

		// Attempt to use the immediate result of is_current_license_valid if revalidate is false.
		if ( ! $revalidate && $pue->is_current_license_valid() ) {
			return true;
		}

		$cache_key = __METHOD__;
		$cached    = get_transient( $cache_key );

		// Decode the JSON string. If $cached is false (transient not set), json_decode returns null.
		$cached_value = false !== $cached ? json_decode( $cached, true ) : null;

		// Check explicitly for null to determine if the transient was not set.
		if ( null !== $cached_value ) {
			return $cached_value;
		}

		$is_license_valid = $pue->is_current_license_valid( $revalidate );

		// Forcing a revalidate of is_current_license_valid can be expensive. Cache it for 1 hour.
		set_transient( $cache_key, wp_json_encode( $is_license_valid ), HOUR_IN_SECONDS );

		return $is_license_valid;
	}

	/**
	 * Determine if free ticket is allowed in Tickets Commerce.
	 *
	 * @since 5.10.0
	 *
	 * @return bool
	 */
	public static function is_free_ticket_allowed() {
		/**
		 * Filter to allow free tickets in Tickets Commerce.
		 *
		 * @since 5.10.0
		 *
		 * @param bool $is_free_ticket_allowed Whether free tickets are allowed in Tickets Commerce.
		 */
		return apply_filters( 'tec_tickets_commerce_is_free_ticket_allowed', true );
	}

	/**
	 * Wrapper for get_option that allows for environmental options.
	 *
	 * @since 5.24.0
	 *
	 * @param string $option     The option name.
	 * @param array  $args       Additional arguments.
	 * @param mixed  $by_default The default value if the option is not set.
	 *
	 * @return mixed The environmental option value.
	 */
	public static function get_option( string $option, array $args = [], $by_default = false ) {
		return get_option( self::get_key( $option, $args ), $by_default );
	}

	/**
	 * Wrapper for update_option that allows for environmental options.
	 *
	 * @since 5.24.0
	 *
	 * @param string $option   The option name.
	 * @param mixed  $value    The value to set.
	 * @param array  $args     Additional arguments.
	 * @param bool   $autoload Whether to autoload the option.
	 *
	 * @return bool Whether the option was updated.
	 */
	public static function update_option( string $option, $value, array $args = [], bool $autoload = true ): bool {
		return update_option( self::get_key( $option, $args ), $value, $autoload );
	}

	/**
	 * Wrapper for delete_option that allows for environmental options.
	 *
	 * @since 5.24.0
	 *
	 * @param string $option The option name.
	 * @param array  $args   Additional arguments.
	 *
	 * @return bool Whether the option was deleted.
	 */
	public static function delete_option( string $option, array $args = [] ): bool {
		return delete_option( self::get_key( $option, $args ) );
	}

	/**
	 * Wrapper for tribe_get_option that allows for environmental options.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since 5.24.0
	 *
	 * @param string $option     The option name.
	 * @param array  $args       Additional arguments.
	 * @param mixed  $by_default The default value if the option is not set.
	 *
	 * @return mixed The environmental option value.
	 */
	public static function get( string $option, array $args = [], $by_default = false ) {
		return tribe_get_option( self::get_key( $option, $args ), $by_default );
	}

	/**
	 * Wrapper for tribe_update_option that allows for environmental options.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since 5.24.0
	 *
	 * @param string $option The option name.
	 * @param mixed  $value  The value to set.
	 * @param array  $args   Additional arguments.
	 *
	 * @return bool Whether the option was updated.
	 */
	public static function set( string $option, $value, array $args = [] ): bool {
		return tribe_update_option( self::get_key( $option, $args ), $value );
	}

	/**
	 * Wrapper for tribe_remove_option that allows for environmental options.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since 5.24.0
	 *
	 * @param string $option The option name.
	 * @param array  $args   Additional arguments.
	 *
	 * @return bool Whether the option was deleted.
	 */
	public static function delete( string $option, array $args = [] ): bool {
		return tribe_remove_option( self::get_key( $option, $args ) );
	}

	/**
	 * Get the environmental key.
	 *
	 * Consider WHAT should be environmental and WHAT should not before using any of those methods.
	 *
	 * For example, an order's data should NOT be environmental, as it's specific to a single order that it happened in a specific environment.
	 * BUT an event's or a ticket's data should be environmental since those can be used BOTH in sandbox and live environments.
	 * A customer's data should be environmental, since the same customer instance can buy in both environments!
	 *
	 * @since 5.24.0
	 *
	 * @param string $option The option name.
	 * @param array  $args   Additional arguments.
	 *
	 * @return string The environmental key.
	 */
	public static function get_key( string $option, array $args = [] ): string {
		$mode = self::is_test_mode() ? 'sandbox' : 'live';
		return sprintf( $option, $mode, ...$args );
	}
}
