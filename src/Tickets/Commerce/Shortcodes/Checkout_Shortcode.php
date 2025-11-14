<?php
/**
 * Shortcode [tec_tickets_checkout].
 *
 * @since 5.1.9
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\Shortcodes;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Utils\Value;

use TEC\Tickets\Commerce\Gateways\Manager;

/**
 * Class for Shortcode Tribe_Tickets_Checkout.
 *
 * @since 5.1.9
 * @package Tribe\Tickets\Shortcodes
 */
class Checkout_Shortcode extends Shortcode_Abstract {

	/**
	 * Id of the current shortcode for filtering purposes.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	public static $shortcode_id = 'checkout';

	/**
	 * Method used to save the template vars for this instance of shortcode.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Updated the $items variable to retrieve all item types from the cart.
	 * @since 5.24.0 Updated the $gateways variable to retrieve only the available gateways.
	 *
	 * @return void
	 */
	public function setup_template_vars() {
		$cart          = tribe( Cart::class );
		$cart_subtotal = Value::create( $cart->get_cart_subtotal() ?? 0 );
		$cart_total    = Value::create( $cart->get_cart_total() ?? 0 );
		$items         = $cart->get_repository()->get_calculated_items( 'all' );
		$sections      = array_unique( array_filter( wp_list_pluck( array_filter( $items, fn( $item ) => isset( $item['event_id'] ) ), 'event_id' ) ) );
		$gateways      = tribe( Manager::class )->get_available_gateways();

		// Pass each item through a filter to determine if it should be skipped.
		$items = array_filter(
			$items,
			function ( $item ) {
				/**
				 * Filters whether the current item should be skipped in the checkout items.
				 *
				 * @since 5.21.0
				 *
				 * @param bool  $should_skip Whether the item should be skipped or not.
				 * @param array $item        The item to be checked.
				 */
				return ! (bool) apply_filters( 'tec_tickets_checkout_should_skip_item', false, $item );
			}
		);

		$args = [
			'provider_id'        => Module::class,
			'provider'           => tribe( Module::class ),
			'items'              => $items,
			'sections'           => $sections,
			'total_value'        => $cart_total,
			'subtotal'           => $cart_subtotal,
			'must_login'         => ! is_user_logged_in() && tribe( Module::class )->login_required(),
			'login_url'          => tribe( Checkout::class )->get_login_url(),
			'registration_url'   => tribe( Checkout::class )->get_registration_url(),
			'is_tec_active'      => defined( 'TRIBE_EVENTS_FILE' ) && class_exists( 'Tribe__Events__Main' ),
			'gateways'           => $gateways,
			'gateways_active'    => count( $gateways ),
			'gateways_connected' => count( $gateways ),
			'billing_fields'     => $this->get_billing_fields(),
			'has_error'          => false,
			'error'              => [
				'title'   => null,
				'message' => null,
			],
		];

		$this->template_vars = $args;
	}

	/**
	 * Gets a list of billing fields.
	 *
	 * @since 5.19.3
	 *
	 * @return array|array[]
	 */
	public function get_billing_fields(): array {
		$fields = [
			'name'    => [
				'label' => __( 'Person purchasing tickets:', 'event-tickets' ),
				'type'  => 'text',
				'value' => '',
			],
			'email'   => [
				'label' => __( 'Email address', 'event-tickets' ),
				'type'  => 'email',
				'value' => '',
			],
			'address' => [
				'type'  => 'text',
				'value' => [
					'line1' => '',
					'line2' => '',
				],
			],
			'city'    => [
				'label' => __( 'City', 'event-tickets' ),
				'type'  => 'text',
				'value' => '',
			],
			'state'   => [
				'label' => __( 'State', 'event-tickets' ),
				'type'  => 'text',
				'value' => '',
			],
			'zip'     => [
				'label' => __( 'Zip/Postal code', 'event-tickets' ),
				'type'  => 'text',
				'value' => '',
			],
			'country' => [
				'label' => __( 'Country or region', 'event-tickets' ),
				'type'  => 'select',
				'value' => '',
			],
		];

		if ( $this->should_display_billing_info() ) {
			$fields['name']['label'] = __( 'Full name', 'event-tickets' );
		}

		return $fields;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$context = tribe_context();

		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}

		$args = $this->get_template_vars();

		// Add the rendering attributes into global context.
		$this->get_template()->add_template_globals( $args );

		return $this->get_template()->template( 'checkout', $args, false );
	}

	/**
	 * Get the number of active gateways.
	 *
	 * @since 5.1.10
	 *
	 * @deprecated 5.24.0
	 *
	 * @return int The number of active gateways.
	 */
	public function get_gateways_active() {
		$gateways        = tribe( Manager::class )->get_gateways();
		$gateways_active = array_filter( array_map( static function ( $gateway ) {
			return $gateway::is_active() && $gateway::is_enabled() && $gateway::should_show() ? $gateway : null;
		}, $gateways ) );

		return count( $gateways_active );
	}

	/**
	 * Get the number of connected gateways.
	 *
	 * @since 5.2.0
	 *
	 * @deprecated 5.24.0
	 *
	 * @return int The number of connected gateways.
	 */
	public function get_gateways_connected() {
		$gateways = tribe( Manager::class )->get_gateways();

		$gateways_connected = array_filter( array_map( static function ( $gateway ) {
			return $gateway::is_connected() && $gateway::should_show() ? $gateway : null;
		}, $gateways ) );

		return count( $gateways_connected );
	}

	/**
	 * Enqueue the assets related to this shortcode, static method to avoid having to generate a new instance.
	 *
	 * @since 5.2.0
	 */
	public static function enqueue_assets() {
		// Enqueue assets.
		do_action( 'tec-tickets-commerce-checkout-shortcode-assets' );
		tribe_asset_enqueue_group( 'tribe-tickets-commerce-checkout' );
		tribe_asset_enqueue( 'tribe-tickets-forms-style' );
	}

	/**
	 * Gets the purchaser info title for the checkout page.
	 *
	 * @return string
	 */
	public function get_purchaser_info_title(): string {
		$title = __( 'Purchaser info', 'event-tickets' );

		if ( $this->should_display_billing_info() ) {
			$title = __( 'Billing info', 'event-tickets' );
		}

		/**
		 * Filters the purchaser info title for the checkout page.
		 * This title is used to describe the section where the purchaser info is displayed.
		 *
		 * @since 5.19.3
		 *
		 * @param string $title     The title of the purchaser info section.
		 * @param static $shortcode The instance of the shortcode.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_success_page_get_purchaser_info_title', $title, $this );
	}


	/**
	 * Filters whether the billing fields info should be included in the checkout page.
	 *
	 * @since 5.19.3
	 *
	 * @param bool $value Whether the billing fields info should be included in the checkout page.
	 *
	 * @return bool
	 */
	protected function filter_display_billing_fields( bool $value ): bool {
		/**
		 * Filter whether the billing fields info should be included in the checkout page.
		 *
		 * @since 5.19.3
		 *
		 * @param bool   $value     Whether the purchaser info should be included in the checkout page.
		 * @param static $shortcode The instance of the shortcode.
		 */
		return apply_filters( 'tec_tickets_commerce_success_page_should_display_billing_fields', $value, $this );
	}

	/**
	 * Whether the billing info should be included in the checkout page.
	 *
	 * @since 5.19.3
	 *
	 * @return bool
	 */
	public function should_display_billing_info(): bool {
		if ( ! $this->should_display_purchaser_info() ) {
			return $this->filter_display_billing_fields( false );
		}

		return $this->filter_display_billing_fields( false );
	}

	/**
	 * Filters the purchaser info should be included in the checkout page.
	 *
	 * @since 5.19.3
	 *
	 * @param bool $value Whether the purchaser info should be included in the checkout page.
	 *
	 * @return bool
	 */
	protected function filter_should_display_purchaser_info( bool $value ): bool {
		/**
		 * Filter whether the purchaser info should be included in the checkout page.
		 *
		 * @since 5.19.3
		 *
		 * @param bool   $value    Whether the purchaser info should be included in the checkout page.
		 * @param static $instance The instance of the shortcode.
		 */
		return apply_filters( 'tec_tickets_commerce_success_page_should_display_purchaser_info', $value, $this );
	}

	/**
	 * Whether the purchaser info should be included in the checkout page.
	 *
	 * @since 5.19.3
	 *
	 * @return bool
	 */
	public function should_display_purchaser_info(): bool {
		$template = $this->get_template();

		$items = $template->get( 'items' );

		// Bail if the cart is empty.
		if ( empty( $items ) ) {
			return $this->filter_should_display_purchaser_info( false );
		}

		$is_user_logged_in = is_user_logged_in();

		$must_login = $template->get( 'must_login', false );
		if ( $must_login && ! $is_user_logged_in ) {
			return $this->filter_should_display_purchaser_info( false );
		}

		return $this->filter_should_display_purchaser_info( ! $is_user_logged_in );
	}
}
