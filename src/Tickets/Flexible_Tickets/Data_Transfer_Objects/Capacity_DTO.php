<?php
/**
 * The data transfer object for the Capacity model.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Flexible_Tickets\Models\Capacity;

/**
 * Class Capacity_DTO.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */
class Capacity_DTO extends DataTransferObject {
	/**
	 * The capacity ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $id;
	/**
	 * The maximum value of the capacity.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $max_value;
	/**
	 * The current value of the capacity.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $current_value;
	/**
	 * The capacity mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $mode;
	/**
	 * The capacity name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The capacity description.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $description;


	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Capacity_DTO The DTO instance.
	 */
	public static function fromObject( $object ): Capacity_DTO {
		$self = new static();

		$self->id            = $object->id;
		$self->max_value     = $object->max_value;
		$self->current_value = $object->current_value;
		$self->mode          = $object->mode;
		$self->name          = $object->name;
		$self->description   = $object->description;

		return $self;
	}

	/**
	 * Builds a new model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Capacity The Capacity model instance built from the DTO.
	 */
	public function toModel(): Capacity {
		$attributes = get_object_vars( $this );

		return new Capacity( $attributes );
	}
}