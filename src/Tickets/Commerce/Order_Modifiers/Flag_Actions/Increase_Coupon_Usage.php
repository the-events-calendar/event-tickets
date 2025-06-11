<?php
/**
 * Increase Coupon Usage flag action.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Flag_Actions;

use TEC\Tickets\Commerce\Status\Status_Interface;
use WP_Post;

/**
 * Class Increase_Coupon_Usage
 *
 * @since 5.21.0
 */
class Increase_Coupon_Usage extends Abstract_Coupon_Usage {

	/**
	 * Which flags are associated and will trigger this action.
	 *
	 * @since 5.21.0
	 *
	 * @var string[]
	 */
	protected $flags = [
		'decrease_stock',
	];

	/**
	 * Determines if a transition of status will trigger this flag action.
	 *
	 * @since 5.21.0
	 *
	 * @param Status_Interface  $new_status New post status.
	 * @param ?Status_Interface $old_status Old post status.
	 * @param WP_Post           $post       Post object.
	 *
	 * @return bool
	 */
	public function should_trigger( Status_Interface $new_status, $old_status, $post ) {
		if ( ! parent::should_trigger( $new_status, $old_status, $post ) ) {
			return false;
		}

		return ! $this->has_usage_been_calculated( $post );
	}

	/**
	 * Handles the usage of a coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param array   $coupon Array of coupon data.
	 * @param WP_Post $order  Order object as a WP_Post object.
	 */
	protected function handle_coupon_usage( $coupon, $order ) {
		$this->add_coupon_use( $coupon['id'], $coupon['quantity'] );
		$this->mark_usage_calculated( $order );
	}
}
