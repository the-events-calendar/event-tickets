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

/**
 * Class Coupons
 *
 * Handles coupon logic in the checkout process.
 *
 * @since TBD
 */
class Coupons extends Controller_Contract {

	/**
	 * @var Coupon
	 */
	protected Coupon $coupon;

	/**
	 * Constructor
	 *
	 * @since TBD
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
	 * @since TBD
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
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[ $this, 'display_coupon_section' ],
			40
		);
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
			'checkout/order-modifiers/coupons',
			[
				// Additional data if needed.
			]
		);
	}
}
