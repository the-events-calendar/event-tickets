<?php
/**
 * Fee Types trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Traits;

/**
 * Trait Fee_Types
 *
 * @since TBD
 */
trait Fee_Types {

	/**
	 * Get the automatic fees.
	 *
	 * @since TBD
	 *
	 * @param object[] $all_fees
	 *
	 * @return array The automatic fees.
	 */
	protected function get_automatic_fees( array $all_fees ) {
		return array_filter(
			$all_fees,
			fn( $fee ) => empty( $fee->meta_value ) || $fee->meta_value === 'all'
		);
	}

	/**
	 * Get the selectable fees.
	 *
	 * @since TBD
	 *
	 * @param object[] $all_fees
	 *
	 * @return array The selectable fees.
	 */
	protected function get_selectable_fees( array $all_fees ) {
		return array_filter(
			$all_fees,
			fn( $fee ) => ! empty( $fee->meta_value ) && $fee->meta_value !== 'all'
		);
	}
}
