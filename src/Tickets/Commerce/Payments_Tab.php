<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway as Gateway;
use TEC\Tickets\Settings as Tickets_Settings;
use \Tribe__Settings;
use \tad_DI52_ServiceProvider;

/**
 * Class Payments_Tab
 *
 * @since 5.2.0
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
	 * @since 5.2.1
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
			'fields'    => $this->get_top_level_settings(),
			'show_save' => true,
		];

		$tab_settings = apply_filters( 'tec_tickets_commerce_payments_tab_settings', $tab_settings );

		new \Tribe__Settings_Tab( static::$slug, esc_html__( 'Payments', 'event-tickets' ), $tab_settings );
	}
	
	/**
	 * Returns the settings item for the section menu at the top of the Payments settings tab.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_section_menu() {
		
		$gateways = tribe( Manager::class )->get_gateways();
		$selected_section = tribe_get_request_var( self::$key_current_section_get_var );
		$menu_html = '<div class="tec-tickets__admin-settings-tickets-commerce-section-menu">';
		$menu_html .= sprintf(
			'<a class="%s" href="%s">%s</a>',
			empty( $selected_section ) ? 'active' : '',
			Tribe__Settings::instance()->get_url( [ 'tab' => 'payments' ] ),
			esc_html__( 'Tickets Commerce', 'event-tickets' ),
		);
        foreach ($gateways as $gateway_key => $gateway) {
			if ( ! $gateway::should_show() ) {
				continue;
			}
			$menu_html .= sprintf(
				'<a class="%s" href="%s">%s</a>',
				$gateway_key === $selected_section ? 'active' : '',
				Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', self::$key_current_section_get_var => $gateway_key ] ),
				$gateway->get_label()
			);
        }
		if( 'main' !== $selected_section ) {
			$current_section_key = self::$key_current_section;
			$menu_html .= '<input type="hidden" name="' . esc_attr( $current_section_key ) . '" ' . 
				'id="' . esc_attr( $current_section_key ) . '" value="' . esc_attr( $selected_section ) . '" />';
		}
		$menu_html .= '</div>';
		
		return [
			self::$key_section_menu => [
				'type' => 'html',
				'html' => $menu_html,
			]
		];
		
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
		
		// Parse URL to get query string info.
		$url_query = wp_parse_url( $url, PHP_URL_QUERY );
		wp_parse_str( $url_query, $args );
		
		// If not on the TEC Payments tab, bail.
		if ( 
			( empty( $args['page'] ) || Tribe__Settings::$parent_slug !== $args['page'] ) || 
			( empty( $args['tab'] )  || self::$slug !== $args['tab'] ) 
		) {
			return $url;
		}
		
		// If valid section not posted, bail.
		$current_section_key = Payments_Tab::$key_current_section;
		if ( empty( $_POST[$current_section_key] ) || 'main' === $_POST[$current_section_key] ) {
			return $url;
		}
		
		// Add section info to URL before redirecting.
		$current_section = $_POST[$current_section_key];
		return add_query_arg( self::$key_current_section_get_var, esc_attr( $current_section ), $url );
	}
	
	/**
	 * Returns the settings item for the section menu at the top of the Payments settings tab.
	 *
	 * @since TBD
	 *
	 * @return Gateway | null
	 */
	public function get_section_gateway(){
		$selected_section = tribe_get_request_var( self::$key_current_section_get_var );
		return tribe( Manager::class )->get_gateway_by_key( $selected_section );
	}
	
	/**
	 * Get selected section top level menu.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function get_section_top_level_menu() {
		
		$section_gateway = $this->get_section_gateway();
		
		$top_level_settings = [
			'tribe-form-content-start'     => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			]
		];
		
		if( empty( $section_gateway ) ) {
			// If no gateway section is selected, show main settings.
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
			$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();
			$top_level_settings[ 'tickets-commerce-header' ] = [
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
			$top_level_settings[ 'tickets-commerce-description' ] = [
				'type' => 'html',
				'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-description">' . $plus_message . '</div>',
			];
			$top_level_settings[ Tickets_Settings::$tickets_commerce_enabled ] = [
				'type'            => 'hidden',
				'validation_type' => 'boolean',
			];
			
			return $top_level_settings;
		}
		
		// Show the switch to enable/disable gateway at the top.
		$manager = tribe( Manager::class );
		$option_key = $manager->get_enabled_option_by_key( $section_gateway );
		$enable_label = sprintf(
			// Translators: %s: Name of payment gateway
			esc_html__( 'Enable %s', 'event-tickets' ),
			$section_gateway->get_label()
		);
		
		$top_level_settings[ 'tickets-commerce-header' ] = [
			'type' => 'html',
			'html' => '<div class="tec-tickets__admin-settings-tickets-commerce-toggle-wrapper">
							<label class="tec-tickets__admin-settings-tickets-commerce-toggle">
								<input
									type="checkbox"
									name="' . $option_key . '"
									' . checked( $manager->is_gateway_enabled( $section_gateway ), true, false ) . '
									id="tickets-commerce-enable-input"
									class="tec-tickets__admin-settings-tickets-commerce-toggle-checkbox tribe-dependency tribe-dependency-verified">
									<span class="tec-tickets__admin-settings-tickets-commerce-toggle-switch"></span>
									<span class="tec-tickets__admin-settings-tickets-commerce-toggle-label">' . $enable_label . '</span>
							</label>
						</div>',

		];
		$top_level_settings[ $option_key ] = [
			'type'            => 'hidden',
			'validation_type' => 'boolean',
		];
		
		return $top_level_settings;
		
	}


	/**
	 * Gets the top level settings for Tickets Commerce.
	 *
	 * @since 5.2.0
	 *
	 * @return array[]
	 */
	public function get_top_level_settings() {

		$top_level_settings = $this->get_section_top_level_menu();;

		/**
		 * Hook to modify the top level settings for Tickets Commerce.
		 *
		 * @since 5.2.0
		 *
		 * @param array[] $top_level_settings Top level settings.
		 */
		return apply_filters( 'tec_tickets_commerce_settings_top_level', array_merge( $this->get_section_menu(), $top_level_settings ) );
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
	 * @param string $page_slug URL slug of the page.
	 * @param string $page_name Name for page title.
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