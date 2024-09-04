<?php
/**
 * The Order Modifier model.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Models;
 */

namespace TEC\Tickets\Order_Modifiers\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers;

/**
 * Class Order_Modifier.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Models;
 *
 * @property int    $id              The Order Modifier ID.
 * @property int    $post_id         Associated post ID.
 * @property string $modifier_type   The type of modifier (coupon, fee).
 * @property string $sub_type        The sub-type of modifier (percentage, flat).
 * @property int    $fee_amount_cents Amount of fee in cents.
 * @property string $slug            The Order Modifier slug (coupon code).
 * @property string $display_name    User-friendly name.
 * @property string $status          The status (active, draft, inactive).
 * @property string $created_at      Creation timestamp.
 * @property string $start_time      When the modifier becomes active.
 * @property string $end_time        When the modifier expires.
 */
class Order_Modifier extends Model implements ModelCrud, ModelFromQueryBuilderObject {

	/**
	 * @inheritDoc
	 */
	protected $properties
		= [
			'id'               => 'int',
			'post_id'          => 'int',
			'modifier_type'    => 'string',
			'sub_type'         => 'string',
			'fee_amount_cents' => 'int',
			'slug'             => 'string',
			'display_name'     => 'string',
			'status'           => 'string',
			'created_at'       => 'string',
			'start_time'       => 'string',
			'end_time'         => 'string',
		];

	/**
	 * Finds a model by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The model ID.
	 *
	 * @return Order_Modifier|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		return tribe( Order_Modifiers::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return Order_Modifier The model instance.
	 */
	public static function create( array $attributes ): self {
		$model = new self( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return Order_Modifier The model instance.
	 */
	public function save(): self {
		if ( $this->id ) {
			return tribe( Order_Modifiers::class )->update( $this );
		}

		$this->id = tribe( Order_Modifiers::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return tribe( Order_Modifiers::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Order_Modifiers::class )->query();
	}

	/**
	 * Builds a new model from a query builder object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the model from.
	 *
	 * @return Order_Modifier The model instance.
	 */
	public static function fromQueryBuilderObject( $object ): self {
		return new self(
			[
				'id'               => $object->id,
				'post_id'          => $object->post_id,
				'modifier_type'    => $object->modifier_type,
				'sub_type'         => $object->sub_type,
				'fee_amount_cents' => $object->fee_amount_cents,
				'slug'             => $object->slug,
				'display_name'     => $object->display_name,
				'status'           => $object->status,
				'created_at'       => $object->created_at,
				'start_time'       => $object->start_time,
				'end_time'         => $object->end_time,
			]
		);
	}
}
