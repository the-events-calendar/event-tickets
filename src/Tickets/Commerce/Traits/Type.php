<?php
/**
 * Type trait.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce\Traits;

/**
 * Class Type
 *
 * @since TBD
 */
class Type {

	use Is_Ticket;

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
