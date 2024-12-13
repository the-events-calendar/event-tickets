<?php
/**
 * The data transfer object for the Order Modifier Meta model.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;

/**
 * Class Order_Modifier_Meta_DTO.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */
class Order_Modifier_Meta_DTO extends DataTransferObject {

	/**
	 * The Order Modifier Meta ID.
	 *
	 * @since 5.18.0
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The associated Order Modifier ID.
	 *
	 * @since 5.18.0
	 *
	 * @var int
	 */
	protected int $order_modifier_id;

	/**
	 * The meta key.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $meta_key;

	/**
	 * The meta value.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $meta_value;

	/**
	 * The priority of the meta entry.
	 *
	 * @since 5.18.0
	 *
	 * @var int
	 */
	protected int $priority;

	/**
	 * The creation timestamp.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $created_at;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @return Order_Modifier_Meta The model instance.
	 */
	public function toModel(): Order_Modifier_Meta {
		$attributes = get_object_vars( $this );

		return new Order_Modifier_Meta( $attributes );
	}
}
