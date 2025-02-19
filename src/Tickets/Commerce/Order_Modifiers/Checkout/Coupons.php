<?php
/**
 * Coupon class for the Checkout.
 *
 * @todo - This class is a placeholder, and will need to be refactored/optimized when Coupons are worked on.
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use Exception;
use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon as Coupon_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Template;
use WP_Post;

/**
 * Class Coupons
 *
 * Handles coupon logic in the checkout process.
 *
 * @since 5.18.0
 */
class Coupons extends Controller_Contract {

	use Type;

	/**
	 * @var Coupon
	 */
	protected Coupon $coupon;

	/**
	 * Constructor
	 *
	 * @since 5.18.0
	 *
	 * @param Container $container The DI container.
	 * @param Coupon    $coupon    The coupon modifier.
	 */
	public function __construct( Container $container, Coupon $coupon ) {
		parent::__construct( $container );
		$this->coupon = $coupon;
	}

	/**
	 * Registers hooks and AJAX actions.
	 *
	 * @since 5.18.0
	 */
	public function do_register(): void {
		// Hook for displaying coupons in the checkout.
		add_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[ $this, 'display_coupon_section' ],
			20,
			3
		);

		// Calculate coupon values in the cart.
		add_filter(
			'tec_tickets_commerce_get_cart_additional_values',
			[ $this, 'calculate_coupons' ],
			20,
			3
		);

		// Attach coupons to the order object.
		add_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_coupons_to_order_object' ]
		);

		// Register our own script on the frontend.
		add_action( 'init', [ $this, 'register_assets' ] );

		// Add asset localization to ensure the script has the necessary data.
		add_action( 'init', [ $this, 'localize_asset' ] );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[ $this, 'display_coupon_section' ],
			20
		);

		remove_filter(
			'tec_tickets_commerce_get_cart_additional_values',
			[ $this, 'calculate_coupons' ],
			20
		);

		remove_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_coupons_to_order_object' ]
		);

		// Remove asset registration.
		remove_action( 'init', [ $this, 'register_assets' ] );

		// Remove asset localization.
		remove_action( 'init', [ $this, 'localize_asset' ] );
	}

	public function register_assets() {}

	/**
	 * Localizes the asset script.
	 *
	 * @since 5.18.0
	 */
	public function localize_asset(): void {
		Assets::init()
			->get( 'tribe-tickets-commerce-js' )
			->add_localize_script(
				'tecTicketsCommerce',
				[
					'restUrl' => tribe_tickets_rest_url(),
				]
			);
	}

	/**
	 * Displays the coupon section in the checkout.
	 *
	 * @since 5.18.0
	 *
	 * @param WP_Post         $post     The current post object.
	 * @param array           $items    The items in the cart.
	 * @param Tribe__Template $template The template object for rendering.
	 */
	public function display_coupon_section( WP_Post $post, array $items, Tribe__Template $template ): void {
		$template->template( 'checkout/order-modifiers/coupons' );
	}

	/**
	 * Calculate the coupons for the cart.
	 *
	 * @since TBD
	 *
	 * @param Value[] $values   An array of `Value` instances representing additional fees or discounts.
	 * @param array   $items    The items currently in the cart.
	 * @param Value   $subtotal The total of the subtotals from the items.
	 *
	 * @return Value[] The updated values.
	 */
	public function calculate_coupons( array $values, array $items, Value $subtotal ): array {
		// If the cart is empty, return the values as is.
		if ( empty( $items ) ) {
			return $values;
		}

		// If the subtotal is already zero, return the values as is.
		if ( 0 === $subtotal->get_decimal() ) {
			return $values;
		}

		// Check the cache, and return the cached value if it exists.
		$cache_key = 'calculate_coupons_' . md5( serialize( $items ) );
		$cache     = tribe_cache();

		if ( ! empty( $cache[ $cache_key ] ) && is_array( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		// Filter out coupon items.
		$coupons = array_filter( $items, fn( $item ) => 'coupon' === $item['type'] );
		if ( empty( $coupons ) ) {
			return $values;
		}

		foreach ( $coupons as $coupon_item ) {
			try {
				$coupon   = Coupon_Model::find( $coupon_item['coupon_id'] );
				$values[] = $coupon->get_discount_amount( $subtotal->get_decimal() );
			} catch ( Exception $e ) {
				continue;
			}
		}

		// Cache the values.
		$cache[ $cache_key ] = $values;

		return $values;
	}

	/**
	 * Filter the properties of the order object to add coupons.
	 *
	 * @since TBD
	 *
	 * @param array $properties The properties of the order object.
	 *
	 * @return array The updated properties.
	 */
	public function attach_coupons_to_order_object( array $properties ): array {
		// We shouldn't have an order with no items, but let's just be safe.
		$items = $properties['items'] ?? [];
		if ( empty ( $items ) ) {
			return $properties;
		}

		// Separate coupons and non-coupons.
		$coupons = array_filter( $items, fn( $item ) => $this->is_coupon( $item ) );
		$items   = array_filter( $items, fn( $item ) => ! $this->is_coupon( $item ) );

		// Store the items and coupons in the properties.
		$properties['coupons'] = $coupons;
		$properties['items']   = $items;

		return $properties;
	}
}
