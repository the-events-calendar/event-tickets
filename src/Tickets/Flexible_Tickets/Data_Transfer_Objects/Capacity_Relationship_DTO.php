<?php
/**
 * The data transfer object for the Capacity Relationship model.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Flexible_Tickets\Models\Capacity_Relationship;

/**
 * Class Capacity_Relationship_DTO.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */
class Capacity_Relationship_DTO extends DataTransferObject {
	/**
	 * The capacity relationship ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The capacity ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $capacity_id;

	/**
	 * The parent capacity ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $parent_capacity_id;

	/**
	 * The object ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $object_id;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Capacity_Relationship_DTO The DTO instance.
	 */
	public static function fromObject( $object ): Capacity_Relationship_DTO {
		$self = new static();

		$self->id                 = $object->id;
		$self->capacity_id        = $object->capacity_id;
		$self->parent_capacity_id = $object->parent_capacity_id;
		$self->object_id          = $object->object_id;

		return $self;
	}

	/**
	 * Builds a new model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Capacity_Relationship The Capacity model instance built from the DTO.
	 */
	public function toModel(): Capacity_Relationship {
		$attributes = get_object_vars( $this );

		return new Capacity_Relationship( $attributes );
	}
}