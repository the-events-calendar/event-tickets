<?php
/**
 * The data transfer object for the Order Modifier Meta model.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;

/**
 * Class Order_Modifier_Meta_DTO.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */
class Order_Modifier_Meta_DTO extends DataTransferObject {

	/**
	 * The Order Modifier Meta ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The associated Order Modifier ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $order_modifier_id;

	/**
	 * The meta key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $meta_key;

	/**
	 * The meta value.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $meta_value;

	/**
	 * The priority of the meta entry.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $priority;

	/**
	 * The creation timestamp.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $created_at;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Order_Modifier_Meta_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id                = $object->id;
		$self->order_modifier_id = $object->order_modifier_id;
		$self->meta_key          = $object->meta_key;
		$self->meta_value        = $object->meta_value;
		$self->priority          = $object->priority;
		$self->created_at        = $object->created_at;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier_Meta The model instance.
	 */
	public function toModel(): Order_Modifier_Meta {
		$attributes = get_object_vars( $this );

		return new Order_Modifier_Meta( $attributes );
	}
}
