<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway as Gateway;
use TEC\Tickets\Settings as Tickets_Commerce_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use TEC\Common\Contracts\Service_Provider;
use Tribe__Settings_Tab;
use Tribe__Template;
use Tribe__Tickets__Main;

/**
 * Class Payments_Tab
 *
 * @since 5.2.0
 * @since 5.23.0 Added horizontal layout blocks for improved visual organization.
 *
 * @package TEC\Tickets\Commerce
 */
class Payments_Tab extends Service_Provider {

	/**
	 * Slug for the tab.
	 *
	 * @since 5.2.1
	 *
	 * @var string
	 */
	public static $slug = 'payments';

	/**
	 * Tab ID for the Tickets Commerce settings.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	const TAB_ID = 'tickets-commerce';

	/**
	 * Meta key for page creation flag.
	 *
	 * @since 5.2.1
	 *
	 * @var string
	 */
	public static $option_page_created_meta_key = 'tec_tc_payments_page_created';

	/**
	 * Meta key for page creation flag.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_gateway_enabled_prefix = 'tec_tc_payments_gateway_enabled_';

	/**
	 * Key to determine current section.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $key_current_section = 'tec_tc_payments_current_section';

	/**
	 * Key to use in GET variable for currently selected section.
	 *
	 * @since 5.3.0
	 * @since 5.23.0 updated to new tab system.
	 *
	 * @var string
	 */
	public static $key_current_section_get_var = 'tab';

	/**
	 * Key to use for section menu.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $key_section_menu = 'tec_tc_section_menu';

	/**
	 * Stores the instance of the template engine that we will use for rendering different elements.
	 *
	 * @since 5.3.0
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 5.23.0
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * @inheritdoc
	 *
	 * @since 5.23.0 switched to using $tab_id const.
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$tab_id = self::TAB_ID;

		add_filter( 'tribe_settings_form_class', [ $this, 'include_form_class' ], 15, 3 );
		add_action( 'tribe_settings_do_tabs', [ $this, 'register_tab' ], 15 );
		add_action( "tribe_settings_after_save_{$tab_id}", [ $this, 'maybe_generate_pages' ] );
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'settings_add_tab_id' ] );
	}

	/**
	 * Create the Tickets Commerce Payments Settings Tab.
	 *
	 * @since 5.2.0
	 * @since 5.23.0 Updated to use new child tabs.
	 *
	 * @param string $admin_page The admin page to register the tab on.
	 */
	public function register_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && Plugin_Settings::$settings_page_id !== $admin_page ) {
			return;
		}

		// Create the main parent tab first.
		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_payments_tab_settings', $tab_settings );

		// Create the parent "Payments" tab.
		$parent_tab = new Tribe__Settings_Tab(
			static::$slug,
			esc_html__( 'Payments', 'event-tickets' ),
			$tab_settings
		);

		// Create the main Tickets Commerce child tab.
		$this->settings_tab = new Tribe__Settings_Tab(
			self::TAB_ID,
			esc_html__( 'Tickets Commerce', 'event-tickets' ),
			$tab_settings
		);
		$parent_tab->add_child( $this->settings_tab );

		// Get and register gateway tabs.
		$gateways = tribe( Manager::class )->get_gateways();
		$gateways = array_filter(
			$gateways,
			static function ( $gateway ) {
				return $gateway::should_show();
			}
		);

		foreach ( $gateways as $gateway_key => $gateway ) {
			$gateway_tab = new Tribe__Settings_Tab(
				$gateway_key,
				$gateway::get_label(),
				$tab_settings
			);
			$parent_tab->add_child( $gateway_tab );
		}
	}

	/**
	 * Include the form class for the Payments tab.
	 *
	 * @since 5.23.0
	 *
	 * @param array               $form_classes The form classes.
	 * @param string              $admin_page   The admin page.
	 * @param Tribe__Settings_Tab $tab_object   The tab object.
	 *
	 * @return array
	 */
	public function include_form_class( $form_classes, $admin_page, $tab_object ) {
		if ( ! $tab_object ) {
			return $form_classes;
		}

		if ( $tab_object->id !== static::$slug && $tab_object->get_parent_id() !== static::$slug ) {
			return $form_classes;
		}

		if ( tec_tickets_commerce_is_enabled() ) {
			return $form_classes;
		}

		$form_classes[] = 'tec-settings-form--no-gap';

		return $form_classes;
	}

	/**
	 * Gets the settings tab.
	 *
	 * @since 5.23.0
	 *
	 * @return Tribe__Settings_Tab
	 */
	public function get_settings_tab() {
		return $this->settings_tab;
	}

	/**
	 * Add the payments tab to the list of tab ids for the Tickets settings.
	 *
	 * @since 5.4.0
	 *
	 * @param array $tabs Array containing the tabs ids for Event Tickets settings.
	 *
	 * @return array $tabs Array containing the tabs ids for Event Tickets settings.
	 */
	public function settings_add_tab_id( $tabs ) {
		$tabs[] = static::$slug;

		return $tabs;
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
			$this->template->set_template_folder( 'src/admin-views/settings' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Gets the URL for the Payment Tab.
	 *
	 * @since 5.3.0
	 *
	 * @param array $args Which query args we are adding.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		if ( ! isset( $args['tab'] ) ) {
			// Force the payment tab.
			$args['tab'] = static::$slug;
		}

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Filters the redirect URL to include section, if applicable.
	 *
	 * @since 5.3.0
	 *
	 * @param string $url URL of redirection.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		if ( ! is_admin() ) {
			return $url;
		}

		$tab  = tribe_get_request_var( 'tab' );
		$page = tribe_get_request_var( 'page' );

		if ( empty( $tab ) || empty( $page ) ) {
			return $url;
		}

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return $url;
		}

		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== $page ) {
			return $url;
		}

		if ( static::$slug !== $tab ) {
			return $url;
		}

		$section = tribe_get_request_var( static::$key_current_section );
		if ( empty( $section ) ) {
			$section = tribe_get_request_var( static::$key_current_section_get_var );
		}

		// In the main section we don't need to do anything.
		if ( empty( $section ) || 'main' === $section ) {
			return $url;
		}

		return add_query_arg( static::$key_current_section_get_var, esc_attr( $section ), $url );
	}

	/**
	 * Returns the settings item for the section menu at the top of the Payments settings tab.
	 *
	 * @since 5.3.0
	 *
	 * @return Gateway|null
	 */
	public function get_section_gateway() {
		$selected_section = tribe_get_request_var( static::$key_current_section_get_var );

		return tribe( Manager::class )->get_gateway_by_key( $selected_section );
	}

	/**
	 * Gets the fields for the Tickets Commerce top level fields.
	 *
	 * @since 5.3.0
	 *
	 * @return array[]
	 */
	public function get_tickets_commerce_section_fields() {
		$fields = [];

		// If no gateway section is selected, show main settings.
		$plus_link = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Event Tickets Plus', 'event-tickets' )
		);

		$plus_message = sprintf(
		// Translators: %1$s: The Event Tickets Plus link.
			esc_html_x( 'Tickets Commerce provides a simple and flexible ecommerce checkout for purchasing tickets. Just choose your payment gateway and configure checkout options and you\'re all set.  If you need more advanced features like custom attendee information, QR code check in, and stock sharing between tickets, take a look at %1$s for these features and more.', 'about Tickets Commerce', 'event-tickets' ),
			$plus_link
		);

		$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

		$fields['tec-settings-payment-header-start'] = [
			'type' => 'html',
			'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">'
						. '<h3 id="tec-settings-addons-title" class="tec-settings-form__section-header">'
						. _x( 'Tickets Commerce', 'Tickets Commerce settings header', 'event-tickets' )
						. '</h3>'
						. '<p class="tec-settings-form__section-description">'
						. $plus_message
						. '</p>',
		];

		$fields['tec-settings-payment-enable'] = [
			'type' => 'html',
			'html' => '<label class="tec-tickets__admin-settings-toggle-large">
								<input
									type="checkbox"
									name="' . Tickets_Commerce_Settings::$tickets_commerce_enabled . '"
									' . checked( $is_tickets_commerce_enabled, true, false ) . '
									id="tickets-commerce-enable-input"
									class="tec-tickets__admin-settings-toggle-large-checkbox tribe-dependency tribe-dependency-verified">
									<span class="tec-tickets__admin-settings-toggle-large-switch"></span>
									<span class="tec-tickets__admin-settings-toggle-large-label">' . esc_html__( 'Enable Tickets Commerce', 'event-tickets' ) . '</span>
							</label>
						',

		];

		$fields['tec-settings-payment-header-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];


		$fields[ Tickets_Commerce_Settings::$tickets_commerce_enabled ] = [
			'type'            => 'hidden',
			'validation_type' => 'boolean',
		];

		return $fields;
	}

	/**
	 * Get selected section top level menu.
	 *
	 * @since 5.3.0
	 * @since 5.23.0 Wrapped elements in new HTML.
	 * @since 5.24.0 Consider solo render gateways for the disabled status.
	 *
	 * @param Gateway $section_gateway Gateway class.
	 *
	 * @return array[]
	 */
	public function get_gateway_section_fields( $section_gateway ): array {
		$fields = [];

		// Show the switch to enable/disable gateway at the top.
		$option_key        = $section_gateway::get_enabled_option_key();
		$enable_label      = esc_html__( 'Enable payment gateway', 'event-tickets' );
		$enable_label_a11y = sprintf(
		// Translators: %s: Name of payment gateway.
			esc_html__( 'Enable %s as a payment gateway', 'event-tickets' ),
			$section_gateway::get_label()
		);

		$disabled                      = ! $section_gateway::is_connected();
		$we_already_use_a_solo_gateway = false;

		if ( ! $disabled && $section_gateway->renders_solo() ) {
			$available_gateways = tribe( Manager::class )->get_available_gateways();
			if ( ! isset( $available_gateways[ $section_gateway::get_key() ] ) ) {
				foreach ( $available_gateways as $gateway ) {
					if ( ! $gateway->renders_solo() ) {
						continue;
					}

					$disabled                      = true;
					$we_already_use_a_solo_gateway = true;
					break;
				}
			}
		}

		$disabled_message     = esc_html__( 'You can have only Stripe or Square enabled, but not both.', 'event-tickets' );
		$disabled_explanation = $we_already_use_a_solo_gateway ? '<p class="tec-tickets__admin-settings-tickets-commerce-gateway-currency-message--error">' . $disabled_message . '</p>' : '';

		$attributes = tribe_get_attributes(
			[
				'type'     => 'checkbox',
				'name'     => $option_key,
				'id'       => 'tickets-commerce-enable-input-' . $section_gateway::get_key(),
				'class'    => 'tec-tickets__admin-settings-toggle-large-checkbox tribe-dependency tribe-dependency-verified',
				'disabled' => $disabled,
				'checked'  => ! $disabled && $section_gateway::is_enabled(),
			]
		);

		$fields['tec-settings-payment-header-start'] = [
			'type' => 'html',
			'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">',
		];

		/**
		 * @todo this needs to move into a template
		 */
		$fields['tickets-commerce-header'] = [
			'type' => 'html',
			'html' => '<label class="tec-tickets__admin-settings-toggle-large" aria-label="' . $enable_label_a11y . '" for="tickets-commerce-enable-input-' . $section_gateway::get_key() . '">
							<input ' . implode( ' ', $attributes ) . ' />
							<span class="tec-tickets__admin-settings-toggle-large-switch"></span>
							<span class="tec-tickets__admin-settings-toggle-large-label">' . $enable_label . '</span>
							' . $disabled_explanation . '
						</label>',
		];

		$fields['tec-settings-payment-header-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];

		$fields[ $option_key ] = [
			'type'            => 'hidden',
			'validation_type' => 'boolean',
		];

		return $fields;
	}


	/**
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since 5.3.0
	 * @since 5.23.0 Updated classes to display section as a horizontal block.
	 *
	 * @return array[]
	 */
	public function get_fields(): array {
		$section_gateway = $this->get_section_gateway();

		if ( empty( $section_gateway ) ) {
			$fields = $this->get_tickets_commerce_section_fields();
		} else {
			$fields = $this->get_gateway_section_fields( $section_gateway );
		}

		/**
		 * Hook to modify the top level settings for Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array[] $top_level_settings Top level settings.
		 */
		$fields = apply_filters( 'tec_tickets_commerce_settings_top_level', $fields );

		return $fields;
	}

	/**
	 * Maybe Generate Checkout and Success page if not found.
	 *
	 * @since 5.2.1
	 */
	public function maybe_generate_pages() {

		$tc_enabled = tribe_get_request_var( Tickets_Commerce_Settings::$tickets_commerce_enabled );

		if ( ! tribe_is_truthy( $tc_enabled ) ) {
			return;
		}

		$this->maybe_auto_generate_checkout_page();
		$this->maybe_auto_generate_order_success_page();
	}

	/**
	 * Generate Checkout page with the shortcode if the page is non-existent.
	 *
	 * @since 5.2.1
	 *
	 * @return bool
	 */
	public function maybe_auto_generate_checkout_page() {
		if ( tribe( Checkout::class )->page_has_shortcode() ) {
			return false;
		}

		$page_slug = 'tickets-checkout';
		$shortcode = Checkout_Shortcode::get_wp_slug();

		if ( $this->is_page_created( $shortcode ) ) {
			return false;
		}

		$page_name = __( 'Tickets Checkout', 'event-tickets' );
		$page_id   = $this->create_page_with_shortcode( $page_slug, $page_name, $shortcode );

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		return tribe_update_option( Settings::$option_checkout_page, $page_id );
	}

	/**
	 * Generate Order Success page with the shortcode if the page is non-existent.
	 *
	 * @since 5.2.1
	 *
	 * @return bool
	 */
	public function maybe_auto_generate_order_success_page() {
		if ( tribe( Success::class )->page_has_shortcode() ) {
			return false;
		}

		$page_slug = 'tickets-order';
		$shortcode = Success_Shortcode::get_wp_slug();

		if ( $this->is_page_created( $shortcode ) ) {
			return false;
		}

		$page_name = __( 'Order Completed', 'event-tickets' );
		$page_id   = $this->create_page_with_shortcode( $page_slug, $page_name, $shortcode );

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		return tribe_update_option( Settings::$option_success_page, $page_id );
	}

	/**
	 * Create a page with given properties.
	 *
	 * @since 5.2.1
	 *
	 * @param string $page_slug      URL slug of the page.
	 * @param string $page_name      Name for page title.
	 * @param string $shortcode_name Shortcode name that needs to be inserted in page content.
	 *
	 * @return int|bool|\WP_Error
	 */
	public function create_page_with_shortcode( $page_slug, $page_name, $shortcode_name ) {

		if ( ! current_user_can( 'edit_pages' ) ) {
			return false;
		}

		$page_data = [
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => get_current_user_id(),
			'post_name'      => $page_slug,
			'post_title'     => $page_name,
			'post_content'   => '<!-- wp:shortcode -->[' . $shortcode_name . ']<!-- /wp:shortcode -->',
			'post_parent'    => 0,
			'comment_status' => 'closed',
			'meta_input'     => [
				static::$option_page_created_meta_key => $shortcode_name,
			],
		];

		return wp_insert_post( $page_data );
	}

	/**
	 * Check if the provided page was created.
	 *
	 * @since 5.2.1
	 *
	 * @param string $shortcode_name Shortcode name that was inserted in page content.
	 *
	 * @return bool
	 */
	public function is_page_created( $shortcode_name ) {

		$args = [
			'post_type'  => 'page',
			'meta_key'   => static::$option_page_created_meta_key,
			'meta_value' => $shortcode_name,
		];

		$query = new \WP_Query( $args );

		return (bool) $query->post_count;
	}

	/*********************
	 * Deprecated methods
	 *********************/

	// @codeCoverageIgnoreStart
	/**
	 * Returns the settings item for the section menu at the top of the Payments settings tab.
	 *
	 * @since 5.3.0
	 * @deprecated 5.23.0 No longer used as we've moved to WordPress-style parent-child tabs
	 *
	 * @return array[]
	 */
	public function get_section_menu(): array {
		_deprecated_function( __METHOD__, '5.23.0', 'The section menu has been replaced with WordPress-style parent-child tabs' );
		return [];
	}

	/**
	 * Gets an array of all the sections, based on the active Gateways.
	 *
	 * @since 5.3.0
	 * @deprecated 5.23.0 No longer used as we've moved to WordPress-style parent-child tabs
	 *
	 * @return array[]
	 */
	public function get_sections(): array {
		_deprecated_function( __METHOD__, '5.23.0', 'The section navigation has been replaced with WordPress-style parent-child tabs' );
		return [];
	}
	// @codeCoverageIgnoreEnd
}
