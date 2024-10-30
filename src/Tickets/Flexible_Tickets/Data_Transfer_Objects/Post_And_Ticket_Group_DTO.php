<?php
/**
 * The data transfer object for the Post and Ticket Group relationship model.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Ticket_Group;

/**
 * Class Post_And_Ticket_Group_DTO.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */
class Post_And_Ticket_Group_DTO extends DataTransferObject {
	/**
	 * The relationship ID.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The Post, or Provisional Post, ID part of the relationship.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	public int $post_id;

	/**
	 * The Ticket Group ID part of the relationship.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	public int $group_id;

	/**
	 * The type of the relationship.
	 *
	 * @since 5.8.0
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since 5.8.0
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Post_And_Ticket_Group_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id       = $object->id;
		$self->post_id  = $object->post_id;
		$self->group_id = $object->group_id;
		$self->type     = $object->type;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since 5.8.0
	 *
	 * @return Post_And_Ticket_Group The model instance.
	 */
	public function toModel(): Post_And_Ticket_Group {
		$attributes = get_object_vars( $this );

		return new Post_And_Ticket_Group( $attributes );
	}
}