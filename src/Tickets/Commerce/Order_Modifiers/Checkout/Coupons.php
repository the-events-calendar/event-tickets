<?php
/**
 * Coupon class for the Checkout.
 *
 * @todo - This class is a placeholder, and will need to be refactored/optimized when Coupons are worked on.
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use Tribe__Template;
use WP_Post;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;

/**
 * Class Coupons
 *
 * Handles coupon logic in the checkout process.
 *
 * @since 5.18.0
 */
class Coupons extends Controller_Contract {

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
			[
				$this,
				'display_coupon_section',
			],
			40,
			3
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

		// Remove asset localization.
		remove_action( 'init', [ $this, 'localize_asset' ] );
	}

	/**
	 * Localizes the asset script.
	 *
	 * @since 5.18.0
	 */
	public function localize_asset(): void {
		Assets::init()->get( 'tribe-tickets-commerce-js' )->add_localize_script( 'tecTicketsCommerce', [ 'restUrl' => tribe_tickets_rest_url() ] );
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
}
