<?php
/**
 * The Order Modifier Relationship model.
 *
 * This model represents the relationship between an Order Modifier and a Post.
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
use TEC\Tickets\Order_Modifiers\Data_Transfer_Objects\Order_Modifier_Relationships_DTO;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship as Order_Modifier_Relationships_Repository;

/**
 * Class Order_Modifier_Relationship.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Models;
 *
 * @property int    $object_id     The primary key of the relationship.
 * @property int    $modifier_id   The Order Modifier ID.
 * @property int    $post_id       The related post ID.
 * @property string $post_type     The post type.
 */
class Order_Modifier_Relationships extends Model implements ModelCrud, ModelFromQueryBuilderObject {

	/**
	 * Properties of the Order Modifier Relationships model.
	 *
	 * This defines the types of the properties that belong to the relationship between
	 * an order modifier and a WordPress post.
	 *
	 * @var array<string, string> The array of properties and their corresponding data types.
	 *
	 * @inheritDoc
	 */
	protected $properties = [
		'object_id'   => 'int',
		'modifier_id' => 'int',
		'post_id'     => 'int',
		'post_type'   => 'string',
		'post_title'  => 'string',
	];


	/**
	 * Finds a model by its ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The model ID.
	 *
	 * @return Order_Modifier_Relationships|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		return tribe( Order_Modifier_Relationships_Repository::class )->find_by_id( $id );
	}

	/**
	 * Creates a new relationship and saves it to the database.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return static
	 */
	public static function create( array $attributes ): self {
		$model = new self( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the relationship to the database.
	 *
	 * @since TBD
	 *
	 * @return static
	 */
	public function save(): self {
		if ( $this->object_id ) {
			return tribe( Order_Modifier_Relationships_Repository::class )->update( $this );
		}

		$this->object_id = tribe( Order_Modifier_Relationships_Repository::class )->insert( $this )->object_id;

		return $this;
	}

	/**
	 * Deletes the relationship from the database.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the relationship was deleted.
	 */
	public function delete(): bool {
		return tribe( Order_Modifier_Relationships_Repository::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since TBD
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Order_Modifier_Relationships_Repository::class )->query();
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
		return Order_Modifier_Relationships_DTO::fromObject( $object )->toModel();
	}

	/**
	 * Converts the Order_Modifier_Relationship object to an array.
	 *
	 * @since TBD
	 *
	 * @return array The object properties as an array.
	 */
	public function to_array(): array {
		return $this->attributes;
	}
}
