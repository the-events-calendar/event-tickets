<?php
/**
 * The data transfer object for the Order Modifier model.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;

use TEC\Common\StellarWP\Models\DataTransferObject;
use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Commerce\Values\Float_Value;
use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Positive_Integer_Value;
use TEC\Tickets\Commerce\Values\Value_Interface;

/**
 * Class Order_Modifier_DTO.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects;
 */
class Order_Modifier_DTO extends DataTransferObject {

	use Valid_Types;

	/**
	 * The Order Modifier ID.
	 *
	 * @since 5.18.0
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The modifier type (coupon, fee).
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $modifier_type;

	/**
	 * The sub-type (percentage, flat).
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $sub_type;

	/**
	 * The fee amount in cents.
	 *
	 * @since 5.18.0
	 *
	 * @var float|Value_Interface
	 */
	protected $raw_amount;

	/**
	 * The slug (coupon code).
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * The user-friendly display name.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $display_name;

	/**
	 * The status (active, draft, inactive).
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $status;

	/**
	 * The created timestamp.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected string $created_at;

	/**
	 * The start time when the modifier becomes active (nullable).
	 *
	 * @since 5.18.0
	 *
	 * @var string|null
	 */
	public ?string $start_time;

	/**
	 * The end time when the modifier expires (nullable).
	 *
	 * @since 5.18.0
	 *
	 * @var string|null
	 */
	public ?string $end_time;

	/**
	 * Builds a new DTO from an object.
	 *
	 * @since 5.18.0
	 *
	 * @param object $object The object to build the DTO from.
	 *
	 * @return Order_Modifier_DTO The DTO instance.
	 */
	public static function fromObject( $object ): self {
		// Set the raw amount based on the sub-type.
		$raw_amount = 'percent' === $object->sub_type
			? ( new Percent_Value( $object->raw_amount ?? 0 ) )
			: Float_Value::from_number( $object->raw_amount ?? 0 );

		$self = new self();

		$self->id            = Positive_Integer_Value::from_number( $object->id )->get();
		$self->modifier_type = $object->modifier_type;
		$self->sub_type      = $object->sub_type;
		$self->raw_amount    = $raw_amount;
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
	 * @since 5.18.0
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
