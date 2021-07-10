<?php
/**
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager;
use Tribe__Field_Conditional;

/**
 * The Tickets Commerce settings.
 *
 * This class will contain all of the settings handling and admin settings config implementation from
 * Tribe__Tickets__Commerce__PayPal__Main that is gateway-agnostic.
 *
 * @since   5.1.6
 * @package Tribe\Tickets\Commerce\Tickets_Commerce
 */
class Settings extends Abstract_Settings {

	/**
	 * The option key for enable.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_enable = 'tickets-commerce-enable';

	/**
	 * The option key for sandbox.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_sandbox = 'ticket-paypal-sandbox';

	/**
	 * The option key for currency code.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_currency_code = 'ticket-paypal-currency-code';

	/**
	 * The option key for stock handling.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_stock_handling = 'ticket-paypal-stock-handling';

	/**
	 * The option key for success page.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_success_page = 'ticket-paypal-success-page';

	/**
	 * The option key for checkout page.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_checkout_page = 'tickets-commerce-checkout-page';

	/**
	 * The option key for confirmation email sender email.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_confirmation_email_sender_email = 'ticket-paypal-confirmation-email-sender-email';

	/**
	 * The option key for confirmation email sender name.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_confirmation_email_sender_name = 'ticket-paypal-confirmation-email-sender-name';

	/**
	 * The option key for confirmation email subject.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $option_confirmation_email_subject = 'ticket-paypal-confirmation-email-subject';

	/**
	 * Get the list of settings for Tickets Commerce.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for Tickets Commerce.
	 */
	public function get_settings() {
		$plus_link    = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Event Tickets Plus', 'event-tickets' )
		);
		$plus_link_2  = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Check it out!', 'event-tickets' )
		);
		$plus_message = sprintf(
			// Translators: %1$s: The Event Tickets Plus link, %2$s: The word "ticket" in lowercase, %3$s: The "Check it out!" link.
			esc_html_x( 'Tickets Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you need more advanced features, take a look at %1$s. In addition to integrating with your favorite ecommerce provider, Event Tickets Plus includes options to collect custom information for attendees, check users in via QR codes, and share stock between %2$s. %3$s', 'about Tickets Commerce', 'event-tickets' ),
			$plus_link,
			esc_html( tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_about_tribe_commerce' ) ),
			$plus_link_2
		);

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

		$tpp_success_shortcode               = 'tribe-tpp-success';
		$tickets_commerce_checkout_shortcode = 'tribe_tickets_checkout';

		/** @var \Tribe__Tickets__Commerce__Currency $commerce_currency */
		$commerce_currency = tribe( 'tickets.commerce.currency' );

		$paypal_currency_code_options = $commerce_currency->generate_currency_code_options();

		$current_user = get_user_by( 'id', get_current_user_id() );

		// @todo Fill this out and make it check if PayPal Legacy was previously active.
		$is_tickets_commerce_enabled = false;

		$top_level_settings = [
			'tickets-commerce-heading'        => [
				'type' => 'html',
				'html' => '<h3>' . __( 'Tickets Commerce', 'event-tickets' ) . '</h3>',
			],
			'tickets-commerce-et-plus-header' => [
				'type' => 'html',
				'html' => '<p>' . $plus_message . '</p>',
			],
			$this->option_enable              => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Tickets Commerce', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Check this box if you wish to turn on Tickets Commerce functionality.', 'event-tickets' ),
				'size'            => 'medium',
				'default'         => $is_tickets_commerce_enabled,
				'validation_type' => 'boolean',
				'attributes'      => [
					'id' => $this->option_enable . '-input',
				],
			],
		];

		$settings = [
			$this->option_sandbox                         => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Test Mode', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables Test mode for testing payments. Any payments made will be done on "sandbox" accounts.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			$this->option_currency_code                   => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The currency that will be used for Tickets Commerce transactions.', 'event-tickets' ),
				'default'         => 'USD',
				'validation_type' => 'options',
				'options'         => $paypal_currency_code_options,
			],
			$this->option_stock_handling                  => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Stock Handling', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The word "ticket" in lowercase.
						_x( 'When a customer purchases a %s, the payment gateway might flag the order as Pending. The order will be Complete once payment is confirmed by the payment gateway.', 'tickets fields settings paypal stock handling', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_stock_handling' )
					)
				),
				'default'         => 'on-pending',
				'validation_type' => 'options',
				'options'         => [
					'on-pending'  => sprintf(
						// Translators: %s: The word "ticket" in lowercase.
						esc_html__( 'Decrease available %s stock as soon as a Pending order is created.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' )
					),
					'on-complete' => sprintf(
						// Translators: %s: The word "ticket" in lowercase.
						esc_html__( 'Only decrease available %s stock if an order is confirmed as Completed by the payment gateway.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' )
					),
				],
				'tooltip_first'   => true,
			],
			$this->option_checkout_page                   => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Checkout page', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The [shortcode] for the success page.
						__( 'This is the page where customers go to complete their purchase. Use the %s shortcode to display the checkout experience in the page content.', 'event-tickets' ),
						"[$tickets_commerce_checkout_shortcode]"
					)
				),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			],
			$this->option_success_page                    => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Success page', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The [shortcode] for the success page.
						__( 'After a successful order, users will be redirected to this page. Use the %s shortcode to display the order confirmation to the user in the page content.', 'event-tickets' ),
						"[$tpp_success_shortcode]"
					)
				),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			],
			$this->option_confirmation_email_sender_email => [
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
			$this->option_confirmation_email_sender_name  => [
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
			$this->option_confirmation_email_subject      => [
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
		];

		/** @var Manager $manager */
		$manager = tribe( Manager::class );

		$gateways = $manager->get_gateways();

		$gateway_setting_groups = [];

		// Get all of the gateway settings.
		foreach ( $gateways as $gateway ) {
			/** @var Abstract_Gateway $gateway_object */
			$gateway_object = $gateway['object'];

			if ( ! $gateway_object::should_show() ) {
				continue;
			}

			// Get the gateway settings.
			$gateway_settings = $gateway_object->get_settings();

			// If there are no gateway settings, don't show this section at all.
			if ( empty( $gateway_settings ) ) {
				continue;
			}

			$heading = [
				'tickets-commerce-' . $gateway_object::get_key() => [
					'type'            => 'wrapped_html',
					'html'            => '<h3 class="event-tickets--admin_settings_subheading">' . $gateway['label'] . '</h3>',
					'validation_type' => 'html',
				],
			];

			// Add the gateway label to the start of settings.
			$gateway_setting_groups[] = $heading;

			$gateway_setting_groups[] = $gateway_settings;
		}

		if ( ! empty( $gateway_setting_groups ) ) {
			// Add the gateway setting groups.
			$settings = array_merge( $settings, array_merge( ...$gateway_setting_groups ) );
		}

		/**
		 * Allow filtering the list of Tickets Commerce settings.
		 *
		 * @since 5.1.6
		 *
		 * @param array $settings The list of Tickets Commerce settings.
		 */
		$settings = apply_filters( 'tribe_tickets_commerce_settings', $settings );

		// Handle setting up dependencies for all of the fields.
		$validate_if         = new Tribe__Field_Conditional( $this->option_enable, 'tribe_is_truthy' );
		$fieldset_attributes = [
			'data-depends'              => '#' . $this->option_enable . '-input',
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

		unset( $commerce_field );

		return array_merge( $top_level_settings, $settings );
	}

}
