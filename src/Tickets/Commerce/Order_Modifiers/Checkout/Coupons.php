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
			'tec_tickets_checkout_should_skip_item',
			[ $this, 'should_skip_item' ],
			10,
			2
		);

		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'create_order_from_cart_items' ],
			10,
			2
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
			20
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
			'tec_tickets_checkout_should_skip_item',
			[ $this, 'should_skip_item' ]
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
	 * @since TBD
	 *
	 * @param array $properties The properties of the order object.
	 *
	 * @return array The updated properties.
	 */
	public function attach_coupons_to_order_object( array $properties ): array {
		// We shouldn't have an order with no items, but let's just be safe.
		$items = $properties['items'] ?? [];
		if ( empty( $items ) ) {
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

	/**
	 * Add coupon item parameters to the cart item.
	 *
	 * @since TBD
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
				'sub_total'    => static function ( float $sub_total ) use ( $coupon ): float {
					return $coupon->get_discount_amount( $sub_total );
				},
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
	 * Filter whether an item should be skipped in the checkout display.
	 *
	 * @since TBD
	 *
	 * @param bool  $should_skip Whether the item should be skipped or not.
	 * @param array $item        The item to be checked.
	 *
	 * @return bool Whether the item should be skipped.
	 */
	public function should_skip_item( bool $should_skip, array $item ): bool {
		return $should_skip || $this->is_coupon( $item );
	}

	/**
	 * Filter the cart items when creating an order to ensure coupons are included.
	 *
	 * @since TBD
	 *
	 * @param array $items    The items in the cart.
	 * @param Value $subtotal The calculated subtotal of the cart items.
	 *
	 * @return array Updated items.
	 */
	public function create_order_from_cart_items( $items, $subtotal ) {
		$cart_page = tribe( Cart::class );
		$cart      = $cart_page->get_repository();
		$coupons   = $cart->update_items_with_subtotal(
			$cart->get_items_in_cart( true, 'coupon' ),
			$subtotal->get_float()
		);

		if ( empty( $coupons ) ) {
			return $items;
		}

		return array_merge( $items, $coupons );
	}
}
