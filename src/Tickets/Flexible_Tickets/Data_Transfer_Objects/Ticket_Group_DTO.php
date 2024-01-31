<?php
/**
 * The data transfer object for the Ticket Group model.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Flexible_Tickets\Models\Ticket_Group;

/**
 * Class Ticket_Group_DTO.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */
class Ticket_Group_DTO extends DataTransferObject {
	/**
	 * The Ticket Group ID.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The Ticket Group slug.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * The Ticket Group data in JSON format.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public string $data;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since 5.8.0
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Ticket_Group_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id   = $object->id;
		$self->slug = $object->slug;
		$self->data = $object->data;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since 5.8.0
	 *
	 * @return Ticket_Group The model instance.
	 */
	public function toModel(): Ticket_Group {
		$attributes = get_object_vars( $this );

		return new Ticket_Group( $attributes );
	}
}