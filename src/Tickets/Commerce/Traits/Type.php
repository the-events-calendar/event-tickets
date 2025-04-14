<?php
/**
 * Type trait.
 *
 * @since 5.21.0
 */

namespace TEC\Tickets\Commerce\Traits;

/**
 * Trait Type
 *
 * @since 5.21.0
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
	 * @since 5.21.0
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a coupon.
	 */
	protected function is_coupon( array $thing ): bool {
		return $this->is_type( $thing, 'coupon' );
	}

	/**
	 * Determine if a thing is a fee.
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a fee.
	 */
	protected function is_fee( array $thing ): bool {
		return $this->is_type( $thing, 'fee' );
	}

	/**
	 * Determine if a thing is the given type.
	 *
	 * @since 5.21.0
	 *
	 * @param array  $thing The thing to check.
	 * @param string $type The type to check for.
	 *
	 * @return bool
	 */
	protected function is_type( array $thing, string $type ): bool {
		if ( ! array_key_exists( 'type', $thing ) ) {
			return false;
		}

		return $type === $thing['type'];
	}

	/**
	 * Get a unique ID for an item using its ID and type.
	 *
	 * @since 5.21.0
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
	 * @since 5.21.0
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
	 * @since 5.21.0
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
