<?php
/**
 * Meta_Keys trait.
 *
 * @since 5.18.1
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

/**
 * Trait Meta_Keys
 *
 * @since 5.18.1
 */
trait Meta_Keys {

	/**
	 * Get the key used to store the applied to value in the meta table.
	 *
	 * @since 5.18.1
	 *
	 * @param string $modifier_type The type of the modifier (e.g., 'coupon', 'fee').
	 *
	 * @return string The key used to store the applied to value in the meta table.
	 */
	protected function get_applied_to_key( string $modifier_type ): string {
		$default_key = "{$modifier_type}_applied_to";

		/**
		 * Filters the key used to store the applied to value in the meta table.
		 *
		 * @since 5.18.1
		 *
		 * @param string $result        The key used to store the applied to value in the meta table.
		 * @param string $modifier_type The type of the modifier (e.g., 'coupon', 'fee').
		 */
		$result = (string) apply_filters(
			'tec_tickets_commerce_order_modifier_applied_to_key',
			$default_key,
			$modifier_type
		);

		return ( ! empty( $result ) ) ? $result : $default_key;
	}
}
