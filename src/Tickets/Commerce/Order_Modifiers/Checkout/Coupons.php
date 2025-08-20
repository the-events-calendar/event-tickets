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
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon as Coupon_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Values\Value_Interface;
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
			40,
			3
		);

		// Attach coupons to the order object.
		add_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_coupons_to_order_object' ]
		);

		add_filter(
			'tec_tickets_commerce_cart_add_full_item_params',
			[ $this, 'add_coupon_item_params' ],
			10,
			3
		);

		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'create_order_from_cart_items' ]
		);

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
			40
		);

		remove_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_coupons_to_order_object' ]
		);

		remove_filter(
			'tec_tickets_commerce_cart_add_full_item_params',
			[ $this, 'add_coupon_item_params' ]
		);

		remove_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'create_order_from_cart_items' ]
		);

		// Remove asset localization.
		remove_action( 'init', [ $this, 'localize_asset' ] );
	}

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
					'i18n'    => [
						'cantDetermineCoupon' => esc_html__( 'Unable to determine the coupon to remove.', 'event-tickets' ),
						'couponApplyError'    => esc_html__( 'There was an error applying the coupon. Please try again.', 'event-tickets' ),
						'couponCodeEmpty'     => esc_html__( 'Coupon code cannot be empty.', 'event-tickets' ),
						'couponRemoveError'   => esc_html__( 'There was an error removing the coupon. Please try again.', 'event-tickets' ),
						'couponRemoveFail'    => esc_html__( 'Failed to remove the coupon. Please try again.', 'event-tickets' ),
						'invalidCoupon'       => esc_html__( 'Invalid coupon code.', 'event-tickets' ),
					],
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
	 * Filter the properties of the order object to add coupons.
	 *
	 * @since 5.21.0
	 * @since 5.25.0 Added check that the items are an array.
	 *
	 * @param array $properties The properties of the order object.
	 *
	 * @return array The updated properties.
	 */
	public function attach_coupons_to_order_object( array $properties ): array {
		// We shouldn't have an order with no items, but let's just be safe.
		$items = $properties['items'] ?? [];
		if ( empty( $items ) || ! is_array( $items ) ) {
			return $properties;
		}

		// Separate coupons and non-coupons.
		$coupons = array_filter( $items, fn( $item ) => $this->is_coupon( $item ) );
		$items   = array_filter( $items, fn( $item ) => ! $this->is_coupon( $item ) );

		// Store the regular items without the coupons.
		$properties['items'] = $items;

		// Store the coupons in the properties after normalizing them.
		$properties['coupons'] = array_map(
			static function ( array $coupon ) {
				if ( ! $coupon['sub_total'] instanceof Value ) {
					$coupon['sub_total'] = Value::create( $coupon['sub_total'] );
				}

				return $coupon;
			},
			$coupons
		);

		return $properties;
	}

	/**
	 * Add coupon item parameters to the cart item.
	 *
	 * @since 5.21.0
	 *
	 * @param ?array $full_item The full item parameters, or null.
	 * @param array  $item      The cart item details.
	 * @param string $type      The item type.
	 *
	 * @return array
	 */
	public function add_coupon_item_params( $full_item, array $item, string $type ) {
		if ( 'coupon' !== $type ) {
			return $full_item;
		}

		try {
			if ( is_int( $item['coupon_id'] ) ) {
				$coupon_id = $item['coupon_id'];
			} else {
				$coupon_id = (int) $this->get_id_from_unique_id( $item['coupon_id'] );
			}

			/** @var Coupon_Model $coupon */
			$coupon = Coupon_Model::find( $coupon_id );

			$full_item = [
				'id'           => $coupon_id,
				'type'         => 'coupon',
				'coupon_id'    => $coupon->id,
				'price'        => $coupon->raw_amount,
				'sub_total'    => static fn( float $sub_total ) => $coupon->get_discount_amount( $sub_total ),
				'display_name' => $coupon->display_name,
				'slug'         => $coupon->slug,
				'quantity'     => 1,
				'event_id'     => 0,
				'ticket_id'    => 0,
			];
		} catch ( Exception $e ) {
			return $full_item;
		}

		return $full_item;
	}

	/**
	 * Filter the cart items when creating an order to ensure coupons are included.
	 *
	 * @since 5.21.0
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array Updated items.
	 */
	public function create_order_from_cart_items( array $items ): array {
		$cart_page = tribe( Cart::class );
		$cart      = $cart_page->get_repository();

		// If we don't have any coupons, we have nothing to do.
		$coupons = $cart->get_calculated_items( 'coupon' );
		if ( empty( $coupons ) ) {
			return $items;
		}

		// Ensure the coupons have floats instead of Value objects for the sub_total.
		$coupons = array_map(
			static function ( array $coupon ) {
				if ( is_float( $coupon['sub_total'] ) ) {
					return $coupon;
				}

				// Convert to a float in the ways we know how, falling back to just casting to float.
				if ( $coupon['sub_total'] instanceof Value ) {
					$coupon['sub_total'] = $coupon['sub_total']->get_float();
				} elseif ( $coupon['sub_total'] instanceof Value_Interface ) {
					$coupon['sub_total'] = (float) $coupon['sub_total']->get();
				} else {
					$coupon['sub_total'] = (float) $coupon['sub_total'];
				}

				return $coupon;
			},
			$coupons
		);

		return array_merge( $items, $coupons );
	}
}
