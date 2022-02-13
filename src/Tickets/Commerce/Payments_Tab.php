<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway as Gateway;
use TEC\Tickets\Settings as Tickets_Settings;
use \Tribe__Settings;
use \tad_DI52_ServiceProvider;
use \Tribe__Template;
use Tribe__Tickets__Main;

/**
 * Class Payments_Tab
 *
 * @since   5.2.0
 *
 * @package TEC\Tickets\Commerce
 */
class Payments_Tab extends tad_DI52_ServiceProvider {

	/**
	 * Slug for the tab.
	 *
	 * @since 5.2.1
	 *
	 * @var string
	 */
	public static $slug = 'payments';

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
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_gateway_enabled_prefix = 'tec_tc_payments_gateway_enabled_';

	/**
	 * Key to determine current section.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $key_current_section = 'tec_tc_payments_current_section';

	/**
	 * Key to use in GET variable for currently selected section.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $key_current_section_get_var = 'tc-section';

	/**
	 * Key to use for section menu.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $key_section_menu = 'tec_tc_section_menu';

	/**
	 * Stores the instance of the template engine that we will use for rendering differentelements.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Template
	 */
	protected $template;


	/**
	 * @inheritdoc
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Create the Tickets Commerce Payments Settings Tab.
	 *
	 * @since 5.2.0
	 */
	public function register_tab() {
		$tab_settings = [
			'priority'  => 25,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_payments_tab_settings', $tab_settings );

		new \Tribe__Settings_Tab( static::$slug, esc_html__( 'Payments', 'event-tickets' ), $tab_settings );
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param array $args Which query args we are adding.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		// Force the payment tab.
		$args['tab'] = 'payments';

		// Use the settings page get_url to build the URL.
		return Tribe__Settings::instance()->get_url( $args );
	}

	/**
	 * Returns the settings item for the section menu at the top of the Payments settings tab.
	 *
	 * @since  TBD
	 *
	 * @return array[]
	 */
	public function get_section_menu(): array {
		$template_vars = [
			'sections'         => $this->get_sections(),
			'selected_section' => tribe_get_request_var( static::$key_current_section_get_var, '' ),
		];

		return [
			static::$key_section_menu => [
				'type' => 'html',
				'html' => $this->get_template()->template( 'section/menu', $template_vars, false ),
			],
		];
	}

	/**
	 * Gets an array of all the sections, based on the active Gateways.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_sections(): array {
		$sections = [
			[
				'slug'    => '',
				'classes' => [],
				'url'     => $this->get_url(),
				'text'    => __( 'Tickets Commerce', 'event-tickets' ),
			],
		];

		$gateways = tribe( Manager::class )->get_gateways();
		$gateways = array_filter( $gateways, static function ( $gateway ) {
			return $gateway::should_show();
		} );

		foreach ( $gateways as $gateway_key => $gateway ) {
			$sections[] = [
				'classes' => [],
				'slug'    => $gateway_key,
				'url'     => $gateway::get_settings_url(),
				'text'    => $gateway::get_label(),
			];
		}

		/**
		 * Filters the sections available on the Payment Tab.
		 *
		 * @since TBD
		 *
		 * @param array[] $sections Current sections.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_payments_tab_sections', $sections );
	}

	/**
	 * Filters the redirect URL to include section, if applicable.
	 *
	 * @since TBD
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

		if ( Tribe__Settings::$parent_slug !== $page ) {
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
	 * @since TBD
	 *
	 * @return Gateway|null
	 */
	public function get_section_gateway() {
		$selected_section = tribe_get_request_var( static::$key_current_section_get_var );

		return tribe( Manager::class )->get_gateway_by_key( $selected_section );
	}

	public function get_tickets_commerce_section_fields() {
		$fields = [];

		// If no gateway section is selected, show main settings.
		$plus_link = sprintf(
			'<a href="https://evnt.is/19zl" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Event Tickets Plus', 'event-tickets' )
		);

		$plus_message = sprintf(
		// Translators: %1$s: The Event Tickets Plus link.
			esc_html_x( 'Tickets Commerce provides a simple and flexible ecommerce checkout for purchasing tickets.  Just choose your payment gateway and configure checkout options and you\'re all set.  If you need more advanced features like custom attendee information, QR code check in, and stock sharing between tickets, take a look at %1$s for these features and more.', 'about Tickets Commerce', 'event-tickets' ),
			$plus_link
		);

		$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

		$fields['tickets-commerce-header'] = [
			'type' => 'html',
			'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-toggle-wrapper">
							<label class="tec-tickets__admin-settings-tickets-commerce-toggle">
								<input
									type="checkbox"
									name="' . Tickets_Settings::$tickets_commerce_enabled . '"
									' . checked( $is_tickets_commerce_enabled, true, false ) . '
									id="tickets-commerce-enable-input"
									class="tec-tickets__admin-settings-tickets-commerce-toggle-checkbox tribe-dependency tribe-dependency-verified">
									<span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span>
									<span class="tec-tickets__admin-settings-tickets-commerce-toggle-label">' . esc_html__( 'Enable Tickets Commerce', 'event-tickets' ) . '</span>
							</label>
						</div>',

		];

		$fields['tickets-commerce-description'] = [
			'type' => 'html',
			'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-description">' . $plus_message . '</div>',
		];

		$fields[ Tickets_Settings::$tickets_commerce_enabled ] = [
			'type'            => 'hidden',
			'validation_type' => 'boolean',
		];

		return $fields;
	}

	/**
	 * Get selected section top level menu.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_gateway_section_fields( $section_gateway ): array {
		$fields = [];

		// Show the switch to enable/disable gateway at the top.
		$option_key   = $section_gateway::get_enabled_option_key();
		$enable_label = sprintf(
		// Translators: %s: Name of payment gateway.
			esc_html__( 'Enable %s', 'event-tickets' ),
			$section_gateway::get_label()
		);

		$attributes = tribe_get_attributes( [
			'type'     => 'checkbox',
			'name'     => $option_key,
			'id'       => 'tickets-commerce-enable-input',
			'class'    => 'tec-tickets__admin-settings-tickets-commerce-toggle-checkbox tribe-dependency tribe-dependency-verified',
			'disabled' => ! $section_gateway::is_active(),
			'checked'  => $section_gateway::is_enabled(),
		] );

		$fields['tickets-commerce-header'] = [
			'type' => 'html',
			'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-toggle-wrapper">
							<label class="tec-tickets__admin-settings-tickets-commerce-toggle">
								<input ' . implode( ' ', $attributes ) . ' />
								<span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span>
								<span class="tec-tickets__admin-settings-tickets-commerce-toggle-label">' . $enable_label . '</span>
							</label>
						</div>',

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
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_fields(): array {
		$section_gateway = $this->get_section_gateway();

		$fields = [
			'tribe-form-content-start' => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
		];

		if ( empty( $section_gateway ) ) {
			$fields = array_merge( $fields, $this->get_tickets_commerce_section_fields() );
		} else {
			$fields = array_merge( $fields, $this->get_gateway_section_fields( $section_gateway ) );
		}

		/**
		 * Hook to modify the top level settings for Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array[] $top_level_settings Top level settings.
		 */
		return apply_filters( 'tec_tickets_commerce_settings_top_level', array_merge( $this->get_section_menu(), $fields ) );
	}

	/**
	 * Maybe Generate Checkout and Success page if not found.
	 *
	 * @since 5.2.1
	 */
	public function maybe_generate_pages() {

		$tc_enabled = tribe_get_request_var( Tickets_Settings::$tickets_commerce_enabled );

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
		};

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
}
