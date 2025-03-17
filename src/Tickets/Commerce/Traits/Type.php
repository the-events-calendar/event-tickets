<?php
/**
 * Type trait.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce\Traits;

/**
 * Trait Type
 *
 * @since TBD
 */
trait Type {

	use Is_Ticket;

	/**
	 * Determine if a thing is a coupon.
	 *
	 * This looks to see whether the array of data has the "type" key set to
	 * "coupon". If the type key is not set, or if it is set to something other
	 * than "coupon", this will return false.
	 *
	 * @since TBD
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a coupon.
	 */
	protected function is_coupon( array $thing ): bool {
		// Something without a type is not a coupon.
		if ( ! array_key_exists( 'type', $thing ) ) {
			return false;
		}

		return 'coupon' === $thing['type'];
	}

	/**
	 * Get a unique ID for an item using its ID and type.
	 *
	 * @since TBD
	 *
	 * @param int|string $id   The ID of the item.
	 * @param string     $type The type of the item.
	 *
	 * @return string
	 */
	protected function get_unique_type_id( $id, string $type ): string {
		return sprintf( '%s-%s', $type, $id );
	}

	/**
	 * Get the type of an item from its unique ID.
	 *
	 * @since TBD
	 *
	 * @param string $unique_id The unique ID of the item.
	 *
	 * @return string
	 */
	protected function get_type_from_unique_id( $unique_id ): string {
		[ $type, ] = explode( '-', $unique_id, 2 );
		return $type;
	}

	/**
	 * Get the ID of an item from its unique ID.
	 *
	 * @since TBD
	 *
	 * @param string $unique_id The unique ID of the item.
	 *
	 * @return string
	 */
	protected function get_id_from_unique_id( $unique_id ): string {
		[ , $id ] = explode( '-', $unique_id, 2 );
		return $id;
	}
}
