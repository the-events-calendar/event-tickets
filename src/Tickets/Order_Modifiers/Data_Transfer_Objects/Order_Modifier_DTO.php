<?php
/**
 * The data transfer object for the Order Modifier model.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Data_Transfer_Objects;
 */

namespace TEC\Tickets\Order_Modifiers\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Order_Modifiers\Factory;
use TEC\Tickets\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Order_Modifier_DTO.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Data_Transfer_Objects;
 */
class Order_Modifier_DTO extends DataTransferObject {

	use Valid_Types;

	/**
	 * The Order Modifier ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The modifier type (coupon, fee).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * The sub-type (percentage, flat).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $sub_type;

	/**
	 * The fee amount in cents.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $raw_amount;

	/**
	 * The slug (coupon code).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * The user-friendly display name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $display_name;

	/**
	 * The status (active, draft, inactive).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $status;

	/**
	 * The created timestamp.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $created_at;

	/**
	 * The start time when the modifier becomes active (nullable).
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $start_time;

	/**
	 * The end time when the modifier expires (nullable).
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $end_time;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Order_Modifier_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self = new self();

		$self->id            = $object->id;
		$self->modifier_type = $object->modifier_type;
		$self->sub_type      = $object->sub_type;
		$self->raw_amount    = $object->raw_amount ?? 0;
		$self->slug          = $object->slug;
		$self->display_name  = $object->display_name;
		$self->status        = $object->status;
		$self->created_at    = $object->created_at;
		$self->start_time    = $object->start_time ?? null;
		$self->end_time      = $object->end_time ?? null;

		return $self;
	}

	/**
	 * Builds a model instance from the DTO.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier The model instance.
	 */
	public function toModel(): Order_Modifier {
		$attributes = get_object_vars( $this );

		return array_key_exists( 'modifier_type', $attributes )
			? Factory::get_model_for_type( $attributes['modifier_type'], $attributes )
			: new Order_Modifier( $attributes );
	}
}
