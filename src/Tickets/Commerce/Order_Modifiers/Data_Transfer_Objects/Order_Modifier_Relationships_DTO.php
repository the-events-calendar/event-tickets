<?php
/**
 * The data transfer object for the Order Modifier Relationship model.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Relationships;

/**
 * Class Order_Modifier_Relationships_DTO.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */
class Order_Modifier_Relationships_DTO extends DataTransferObject {

	/**
	 * The primary key of the relationship.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The modifier ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $modifier_id;

	/**
	 * The post ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $post_id;

	/**
	 * The post type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $post_type;

	/**
	 * The post title (optional, for clarity when rendering data).
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	protected ?string $post_title = null;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Order_Modifier_Relationships_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id          = $object->id;
		$self->modifier_id = $object->modifier_id;
		$self->post_id     = $object->post_id;
		$self->post_type   = $object->post_type;
		$self->post_title  = $object->post_title ?? null;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier_Relationships The model instance.
	 */
	public function toModel(): Order_Modifier_Relationships {
		$attributes = get_object_vars( $this );

		return new Order_Modifier_Relationships( $attributes );
	}
}
