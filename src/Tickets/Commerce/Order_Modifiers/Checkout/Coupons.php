<?php
/**
 * Coupon class for the Checkout.
 *
 * @todo - This class is a placeholder, and will need to be refactored/optimized when Coupons are worked on.
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Registerable;
use Tribe__Assets;
use Tribe__Template;
use WP_Post;

/**
 * Class Coupons
 *
 * Handles coupon logic in the checkout process.
 *
 * @since TBD
 */
class Coupons implements Registerable {

	/**
	 * @var Coupon
	 */
	protected Coupon $coupon;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->coupon = new Coupon();
	}

	/**
	 * Registers hooks and AJAX actions.
	 *
	 * @since TBD
	 */
	public function register(): void {
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
		add_action( 'init', fn() => $this->localize_assets() );
	}

	/**
	 * Displays the coupon section in the checkout.
	 *
	 * @since TBD
	 *
	 * @param WP_Post         $post     The current post object.
	 * @param array           $items    The items in the cart.
	 * @param Tribe__Template $template The template object for rendering.
	 */
	public function display_coupon_section( WP_Post $post, array $items, Tribe__Template $template ): void {
		// Display the coupon section template.
		$template->template(
			'checkout/order-modifiers/Coupons',
			[
				// Additional data if needed.
			]
		);
	}

	/**
	 * Localizes the assets for the coupon section.
	 *
	 * @return void
	 */
	protected function localize_assets() {
		/** @var Asset $main */
		$main = Tribe__Assets::instance()->get( 'tribe-tickets-commerce-js' );
		$main->add_localize_script(
			'tecTicketsCommerce',
			[
				'restUrl' => tribe_tickets_rest_url(),
			]
		);
	}
}
