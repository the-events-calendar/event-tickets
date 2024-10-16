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
use TEC\Tickets\Order_Modifiers\Data_Transfer_Objects\Order_Modifier_DTO;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as Repository;

/**
 * Class Order_Modifier.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Models;
 *
 * @property int    $id              The Order Modifier ID.
 * @property string $modifier_type   The type of modifier (coupon, fee).
 * @property string $sub_type        The sub-type of modifier (percentage, flat).
 * @property int    $raw_amount      Amount of fee in cents.
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
	protected $properties = [
		'id'            => 'int',
		'modifier_type' => 'string',
		'sub_type'      => 'string',
		'raw_amount'    => 'int',
		'slug'          => 'string',
		'display_name'  => 'string',
		'status'        => 'string',
		'created_at'    => 'string',
		'start_time'    => 'string',
		'end_time'      => 'string',
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
		return tribe( Repository::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return static
	 */
	public static function create( array $attributes ): self {
		// Maybe override the modifier type based on the final class.
		if ( property_exists( static::class, 'order_modifier_type' ) ) {
			$attributes['modifier_type'] = static::$order_modifier_type;
		}

		$model = new static( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since TBD
	 *
	 * @return static
	 */
	public function save(): self {
		$repository = new Repository( $this->modifier_type );
		if ( $this->id ) {
			$repository->update( $this );
			return $this;
		}

		$this->id = $repository->insert( $this )->id;

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
		return ( new Repository( $this->modifier_type ) )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Repository::class )->query();
	}

	/**
	 * Builds a new model from a query builder object.
	 *
	 * @since TBD
	 *
	 * @param object $object The object to build the model from.
	 *
	 * @return static
	 */
	public static function fromQueryBuilderObject( $object ) {
		return Order_Modifier_DTO::fromObject( $object )->toModel();
	}

	/**
	 * Converts the Order_Modifier object to an array.
	 *
	 * @since TBD
	 *
	 * @return array The object properties as an array.
	 */
	public function to_array(): array {
		return $this->attributes;
	}
}
