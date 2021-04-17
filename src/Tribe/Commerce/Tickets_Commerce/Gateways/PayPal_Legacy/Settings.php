<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\Abstract_Settings;
use Tribe__Field_Conditional;
use Tribe__Main;

/**
 * The PayPal Standard (Legacy) specific settings.
 *
 * This class will contain all of the settings handling and admin settings config implementation from
 * Tribe__Tickets__Commerce__PayPal__Main that is PayPal Standard (Legacy) specific.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy
 */
class Settings extends Abstract_Settings {

	/**
	 * Get the list of admin settings for this gateway.
	 *
	 * @since TBD
	 *
	 * @return array The list of admin settings for this gateway.
	 */
	public function get_admin_settings() {
		$home_url = home_url();

		$plus_link   = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Event Tickets Plus', 'tribe-common' )
		);
		$plus_link_2 = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Check it out!', 'tribe-common' )
		);

		$plus_message = sprintf(
			// Translators: %1$s: The Event Tickets Plus link, %2$s: The word "ticket" in lowercase, %3$s: The "Check it out!" link.
			esc_html_x( 'Tribe Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you need more advanced features, take a look at %1$s. In addition to integrating with your favorite ecommerce provider, Event Tickets Plus includes options to collect custom information for attendees, check users in via QR codes, and share stock between %2$s. %3$s', 'about Tribe Commerce', 'event-tickets' ),
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

		$tpp_success_shortcode = 'tribe-tpp-success';

		/** @var \Tribe__Tickets__Commerce__Currency $commerce_currency */
		$commerce_currency = tribe( 'tickets.commerce.currency' );

		$paypal_currency_code_options = $commerce_currency->generate_currency_code_options();

		$current_user = get_user_by( 'id', get_current_user_id() );

		// The KB article URL will change depending on whether ET+ is active or not.
		$paypal_setup_kb_url  = class_exists( 'Tribe__Tickets_Plus__Main' ) ? 'https://evnt.is/19yk' : 'https://evnt.is/19yj';
		$paypal_setup_kb_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $paypal_setup_kb_url ),
			esc_html__( 'these instructions', 'event-tickets' )
		);
		$paypal_setup_note    = sprintf(
			// Translators: %1$s: The word "ticket" in lowercase, %2$s: The "these instructions" link.
			esc_html_x( 'In order to use Tribe Commerce to sell %1$s, you must configure your PayPal account to communicate with your WordPress site. If you need help getting set up, follow %2$s', 'tickets fields settings PayPal setup', 'event-tickets' ),
			esc_html( tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_paypal_setup' ) ),
			$paypal_setup_kb_link
		);

		$ipn_setup_site_link    = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $home_url ),
			esc_html( $home_url )
		);
		$ipn_setup_site_address = sprintf(
			esc_html__( 'Your site address is: %s', 'event-tickets' ),
			$ipn_setup_site_link
		);
		$ipn_setup_line         = sprintf(
			'<span class="clear">%s</span><span class="clear">%s</span>',
			esc_html__( "Have you entered this site's address in the Notification URL field in IPN Settings?", 'event-tickets' ),
			$ipn_setup_site_address
		);

		$paypal_fields = [
			'ticket-paypal-heading'        => [
				'type' => 'html',
				'html' => '<h3>' . __( 'Tribe Commerce', 'event-tickets' ) . '</h3>',
			],
			'ticket-paypal-et-plus-header' => [
				'type' => 'html',
				'html' => '<p>' . $plus_message . '</p>',
			],
			'ticket-paypal-enable'         => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Tribe Commerce ', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Check this box if you wish to turn on Tribe Commerce functionality.', 'event-tickets' ),
				'size'            => 'medium',
				'default'         => false,
				'validation_type' => 'boolean',
				'attributes'      => [
					'id' => 'ticket-paypal-enable-input',
				],
			],
		];

		/** @var \Tribe__Tickets__Commerce__PayPal__Handler__IPN $ipn_handler */
		$ipn_handler = tribe( 'tickets.commerce.paypal.handler.ipn' );

		$ipn_config_status = sprintf(
			'<strong>%1$s <span id="paypal-ipn-config-status" data-status="%2$s">%3$s</span></strong><p class="description"><i>%4$s</i></p>',
			esc_html__( 'PayPal configuration status:', 'event-tickets' ),
			esc_attr( $ipn_handler->get_config_status( 'slug' ) ),
			esc_html( $ipn_handler->get_config_status( 'label' ) ),
			esc_html__( 'For help creating and configuring your account, call PayPal at 1-844-720-4038 (USA)', 'event-tickets' )
		);

		$paypal_subfields = [
			'ticket-paypal-configure'                       => [
				'type'            => 'wrapped_html',
				'label'           => esc_html__( 'Configure PayPal:', 'event-tickets' ),
				'html'            => '<p>' . $paypal_setup_note . '</p>',
				'validation_type' => 'html',
			],
			'ticket-paypal-email'                           => [
				'type'            => 'email',
				'label'           => esc_html__( 'PayPal email to receive payments:', 'event-tickets' ),
				'size'            => 'large',
				'default'         => '',
				'validation_type' => 'email',
				'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
			],
			'ticket-paypal-ipn-enabled'                     => [
				'type'            => 'radio',
				'label'           => esc_html__( "Have you enabled instant payment notifications (IPN) in your PayPal account's Selling Tools?", 'event-tickets' ),
				'options'         => [
					'yes' => __( 'Yes', 'event-tickets' ),
					'no'  => __( 'No', 'event-tic->valuekets' ),
				],
				'size'            => 'large',
				'default'         => 'no',
				'validation_type' => 'options',
				'class'           => 'indent light-bordered checkmark checkmark-right checkmark-hide ipn-required',
			],
			'ticket-paypal-ipn-address-set'                 => [
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
			'ticket-paypal-ipn-config-status'               => [
				'type'            => 'wrapped_html',
				'html'            => $ipn_config_status,
				'size'            => 'large',
				'default'         => 'no',
				'validation_type' => 'html',
				'class'           => 'indent light-bordered',
			],
			'ticket-paypal-sandbox'                         => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'PayPal Sandbox', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables PayPal Sandbox mode for testing.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			'ticket-commerce-currency-code'                 => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The currency that will be used for Tribe Commerce transactions.', 'event-tickets' ),
				'default'         => 'USD',
				'validation_type' => 'options',
				'options'         => $paypal_currency_code_options,
			],
			'ticket-paypal-stock-handling'                  => [
				'type'            => 'radio',
				'label'           => esc_html__( 'Stock Handling', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The word "ticket" in lowercase.
						_x( 'When a customer purchases a %s, PayPal might flag the order as Pending. The order will be Complete once payment is confirmed by PayPal.', 'tickets fields settings paypal stock handling', 'event-tickets' ),
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
						esc_html__( 'Only decrease available %s stock if an order is confirmed as Completed by PayPal.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' )
					),
				],
				'tooltip_first'   => true,
			],
			'ticket-paypal-success-page'                    => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Success page', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The [shortcode] for the success page.
						__( 'After a successful PayPal order users will be redirected to this page; use the %s shortcode to display the order confirmation to the user in the page content.', 'event-tickets' ),
						"[$tpp_success_shortcode]"
					)
				),
				'size'            => 'medium',
				'validation_type' => 'options',
				'options'         => $pages,
				'required'        => true,
			],
			'ticket-paypal-confirmation-email-sender-email' => [
				'type'            => 'email',
				'label'           => esc_html__( 'Confirmation email sender address', 'event-tickets' ),
				'tooltip'         => esc_html(
					sprintf(
						// Translators: %s: The word "tickets" in lowercase.
						_x( 'Email address PayPal %s customers will receive confirmation from. Leave empty to use the default WordPress site email address.', 'tickets fields settings paypal confirmation email', 'event-tickets' ),
						tribe_get_ticket_label_plural_lowercase( 'tickets_fields_settings_paypal_confirmation_email' )
					)
				),
				'size'            => 'medium',
				'default'         => $current_user->user_email,
				'validation_type' => 'email',
				'can_be_empty'    => true,
			],
			'ticket-paypal-confirmation-email-sender-name'  => [
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
			'ticket-paypal-confirmation-email-subject'      => [
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

			$ipn_fields = [
				'ticket-paypal-notify-history' => [
					'type'            => 'wrapped_html',
					'html'            => '<p>' . $ipn_notify_note . '</p>',
					'size'            => 'medium',
					'validation_type' => 'html',
					'class'           => 'indent light-bordered',
				],
				'ticket-paypal-notify-url'     => [
					'type'            => 'text',
					'label'           => esc_html__( 'IPN Notify URL', 'event-tickets' ),
					'tooltip'         => $ipn_notify_url_tooltip,
					'default'         => $home_url,
					'validation_type' => 'html',
				],
			];

			$paypal_subfields = Tribe__Main::array_insert_after_key( 'ticket-paypal-ipn-config-status', $paypal_subfields, $ipn_fields );
		}

		// Handle setting up dependencies for all of the fields.
		$validate_if         = new Tribe__Field_Conditional( 'ticket-paypal-enable', 'tribe_is_truthy' );
		$fieldset_attributes = [
			'data-depends'              => '#ticket-paypal-enable-input',
			'data-condition-is-checked' => '',
		];

		foreach ( $paypal_subfields as $key => &$commerce_field ) {
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

		return array_merge( $paypal_fields, $paypal_subfields );
	}

}
