<?php
/**
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Traits\Has_Mode;
use Tribe__Field_Conditional;
use WP_Admin_Bar;

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
	use Has_Mode;

	/**
	 * The option key for enable.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_enable = 'tickets-commerce-enable';

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
	 * Stores the instance of the template engine that we will use for retrieving notice HTML.
	 * 
	 * @since TBD
	 * 
	 * @var \Tribe_Template
	 */
	protected $template;

	/**
	 * Settings constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		// Configure which mode we are in.
		$this->set_mode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' );
	}

	/**
	 * Create the Tickets Commerce Payments Settings Tab.
	 *
	 * @since 5.1.9
	 */
	public function register_tab() {
		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_settings(),
			'show_save' => true,
		];

		new \Tribe__Settings_Tab( 'payments', esc_html__( 'Payments', 'event-tickets' ), $tab_settings );
	}

	/**
	 * Display admin bar when using the Test Mode for payments.
	 *
	 * @since TBD
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
		$url = \Tribe__Settings::instance()->get_url( [ 'tab' => 'payments' ] );

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
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since 5.1.9
	 *
	 *
	 * @return array[]
	 */
	public function get_top_level_settings() {

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
			esc_html_x( 'Tickets Commerce is a light implementation of a commerce gateway using PayPal and simplified stock handling. If you need more advanced features, take a look at %1$s. In addition to integrating with your favorite ecommerce provider, Event Tickets Plus includes options to collect custom information for attendees, check attendees in via QR codes, and share stock between %2$s. %3$s', 'about Tickets Commerce', 'event-tickets' ),
			$plus_link,
			esc_html( tribe_get_ticket_label_singular_lowercase( 'tickets_fields_settings_about_tribe_commerce' ) ),
			$plus_link_2
		);

		// @todo Fill this out and make it check if PayPal Legacy was previously active.
		$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

		$top_level_settings = [
			'tribe-form-content-start'     => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			'tickets-commerce-header'      => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-toggle-wrapper"><label class="tec-tickets__admin-settings-tickets-commerce-toggle"><input type="checkbox" name="' . static::$option_enable . '" value="' . $is_tickets_commerce_enabled . '" ' . checked( $is_tickets_commerce_enabled, true, false ) . ' id="tickets-commerce-enable-input" class="tec-tickets__admin-settings-tickets-commerce-toggle-checkbox tribe-dependency tribe-dependency-verified"><span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span><span class="tec-tickets__admin-settings-tickets-commerce-toggle-label">' . esc_html__( 'Enable Tickets Commerce', 'event-tickets' ) . '</span></label></div>',

			],
			'tickets-commerce-description' => [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-description">' . $plus_message . '</div>',
			],
			static::$option_enable         => [
				'type'            => 'hidden',
				'validation_type' => 'boolean',
			],
		];

		/**
		 * Hook to modify the top level settings for Tickets Commerce.
		 *
		 * @since 5.1.9
		 *
		 * @param array[] $top_level_settings Top level settings.
		 */
		return apply_filters( 'tec_tickets_commerce_settings_top_level', $top_level_settings );
	}

	/**
	 * Gets the template instance used to show notices.
	 * 
	 * @since TBD
	 * 
	 * @return \Tribe_Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/tickets-commerce' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Show TC settings notice
	 * 
	 * @since TBD
	 * 
	 * @param string $option_key The options setting key.
	 * 
	 * @return string HTML of the notice.
	 */
	public function get_notice( $option_key = '', $shortcode = '' ) {

		$hide_notice = $this->is_set_with_shortcode( $option_key, $shortcode );
		if( $hide_notice ) {
			return '';
		}

		switch( $option_key ) {
			case static::$option_checkout_page:
				$notice_heading = esc_html__( 'Set up your checkout page', 'event-tickets' );
				$notice_content = sprintf( 
					esc_html__( 
						"In order to start selling with Tickets Commerce, you'll need to set up " . 
						'your checkout page. Please configure the setting on Settings > Payments and ' . 
						'confirm that the page you have selected has the proper shortcode. ' . 
						'%sLearn more%s',
						'event-tickets'
					),
					'<a href="https://evnt.is/1axv" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
				break;
			case static::$option_success_page:
				$notice_heading = esc_html__( 'Set up your order success page', 'event-tickets' );
				$notice_content = sprintf( 
					esc_html__( 
						"In order to start selling with Tickets Commerce, you'll need to set up your " .
						'order success page. Please configure the setting on Settings > Payments and ' .
						'confirm that the page you have selected has the proper shortcode. ' . 
						'%sLearn more%s',
						'event-tickets'
					),
					'<a href="https://evnt.is/1axv" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
				break;
			default:
				return '';
		}

		$template = $this->get_template();
		return $template->template( 'notice', [
			'notice_heading' => $notice_heading,
			'notice_content' => $notice_content,
		], false );
	}

	/**
	 * Determine whether setting is set and page has shortcode included in the content.
	 * 
	 * @since TBD
	 * 
	 * @param string $option_key The option to check if set.
	 * @param string $shortcode  Shortcode to see if included on page.
	 * 
	 * @return bool If setting is set and shortcode included in page content.
	 */
	public function is_set_with_shortcode( $option_key = '', $shortcode = '' ) {
		if ( empty( $option_key ) || empty( $shortcode ) ) {
			return false;
		}
		$page_id = intval( tribe_get_option( $option_key ) );
		if ( 0 === $page_id ) {
			return false;
		}
		$page = get_post( $page_id );
		if ( ! $page ) {
			return false;
		}
		return has_shortcode( $page->post_content, $shortcode );
	}

	/**
	 * Get the list of settings for Tickets Commerce.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for Tickets Commerce.
	 */
	public function get_settings() {
		$gateways_manager = tribe( Manager::class );

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

		/** @var \Tribe__Tickets__Commerce__Currency $commerce_currency */
		$commerce_currency = tribe( 'tickets.commerce.currency' );

		$paypal_currency_code_options = $commerce_currency->generate_currency_code_options();

		$current_user = get_user_by( 'id', get_current_user_id() );

		$notice_html  = '';
		$notice_html .= $this->get_notice( static::$option_checkout_page, $checkout_shortcode );
		$notice_html .= $this->get_notice( static::$option_success_page, $success_shortcode );

		$settings = [
			'tickets-commerce-notices'						=> [
				'type' => 'html',
				'html' => $notice_html,
			],
			'tickets-commerce-general-settings-heading'     => [
				'type' => 'html',
				'html' => '<h3 class="my-awesome-class tribe-dependent"  data-depends="#' . static::$option_enable . '-input" data-condition-is-checked>' . __( 'Tickets Commerce Settings', 'event-tickets' ) . '</h3><div class="clear"></div>',
			],
			static::$option_sandbox                         => [
				'type'            => 'checkbox_bool',
				'label'           => esc_html__( 'Enable Test Mode', 'event-tickets' ),
				'tooltip'         => esc_html__( 'Enables Test mode for testing payments. Any payments made will be done on "sandbox" accounts.', 'event-tickets' ),
				'default'         => false,
				'validation_type' => 'boolean',
			],
			static::$option_currency_code                   => [
				'type'            => 'dropdown',
				'label'           => esc_html__( 'Currency Code', 'event-tickets' ),
				'tooltip'         => esc_html__( 'The currency that will be used for Tickets Commerce transactions.', 'event-tickets' ),
				'default'         => 'USD',
				'validation_type' => 'options',
				'options'         => $paypal_currency_code_options,
			],
			static::$option_stock_handling                  => [
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
						esc_html__( 'Decrease available %1$s stock as soon as a %2$sPending%3$s order is created.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' ),
						'<strong>',
						'</strong>'
					),
					Completed::SLUG => sprintf(
					// Translators: %1$s: The word "ticket" in lowercase. %2$s: `<strong>` opening tag. %3$s: `</strong>` closing tag.
						esc_html__( 'Only decrease available %1$s stock if an order is confirmed as %2$sCompleted%3$s by the payment gateway.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'stock_handling' ),
						'<strong>',
						'</strong>'
					),
				],
				'tooltip_first'   => true,
			],
			static::$option_checkout_page                   => [
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
			static::$option_success_page                    => [
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
		];

		$settings = array_merge( $gateways_manager->get_gateway_settings(), $settings );

		/**
		 * Allow filtering the list of Tickets Commerce settings.
		 *
		 * @since 5.1.6
		 *
		 * @param array $settings The list of Tickets Commerce settings.
		 */
		$settings = apply_filters( 'tribe_tickets_commerce_settings', $settings );

		return array_merge( $this->get_top_level_settings(), $this->apply_commerce_enabled_conditional( $settings ) );
	}

	/**
	 * Handle setting up dependencies for all of the fields.
	 *
	 * @since 5.1.9
	 *
	 * @param array[] $settings Which settings we are applying conditionals to.
	 *
	 * @return array[]
	 */
	public function apply_commerce_enabled_conditional( $settings ) {
		$validate_if         = new Tribe__Field_Conditional( static::$option_enable, 'tribe_is_truthy' );
		$fieldset_attributes = [
			'data-depends'              => '#' . static::$option_enable . '-input',
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

}
