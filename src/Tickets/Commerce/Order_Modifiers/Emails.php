<?php
/**
 * Adds email filters for the order modifiers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_Post;

/**
 * Class Emails.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers;
 */
class Emails extends Controller_Contract {

	/**
	 * Registers the bindings required to use them.
	 *
	 * @since TBD
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_commerce_prepare_order_for_email', [ $this, 'add_fees_to_emails'] , 10, 2 );
	}

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_prepare_order_for_email', [ $this, 'add_fees_to_emails' ] );
	}

	/**
	 * Adds fees to the order object for emails.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order object.
	 *
	 * @return WP_Post
	 */
	public function add_fees_to_emails( WP_Post $order, array $original_items ): WP_Post {
		if ( empty( $order->items ) || ! is_array( $order->items ) ) {
			return $order;
		}

		$order->fees = array_filter( $original_items, fn( $item ) => array_key_exists( 'type', $item ) && 'fee' === $item['type'] );

		return $order;
	}
}
