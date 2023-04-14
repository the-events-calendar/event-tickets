<?php
/**
 * ${CARET}
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Post;

/**
 * Class Post_And_Post_DTO.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects;
 */
class Post_And_Post_DTO extends DataTransferObject {

	/**
	 * The post and post ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * The first post ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $post_id_1;

	/**
	 * The second post ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $post_id_2;

	/**
	 * The type of the relationship.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Post_And_Post_DTO The DTO instance.
	 */
	public static function fromObject( $object ): Post_And_Post_DTO {
		$self = new static();

		$self->id        = $object->id;
		$self->post_id_1 = $object->post_id_1;
		$self->post_id_2 = $object->post_id_2;
		$self->type      = $object->type;

		return $self;
	}

	/**
	 * Builds a new model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Post_And_Post The Post_And_Post model instance built from the DTO.
	 */
	public function toModel(): Post_And_Post {
		$attributes = get_object_vars( $this );

		return new Post_And_Post( $attributes );
	}
}