<?php
/**
 * The Order Modifier Relationship model.
 *
 * This model represents the relationship between an Order Modifier and a Post.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship as Order_Modifier_Relationships_Repository;

/**
 * Class Order_Modifier_Relationship.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 *
 * @property int    $id            The primary key of the relationship.
 * @property int    $modifier_id   The Order Modifier ID.
 * @property int    $post_id       The related post ID.
 * @property string $post_type     The post type.
 */
class Order_Modifier_Relationships extends Model implements ModelPersistable {

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
	protected static array $properties = [
		'id'          => 'int',
		'modifier_id' => 'int',
		'post_id'     => 'int',
		'post_type'   => 'string',
		'post_title'  => 'string',
	];


	/**
	 * Finds a model by its ID.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @return static
	 */
	public function save(): self {
		if ( $this->id ) {
			return tribe( Order_Modifier_Relationships_Repository::class )->update( $this );
		}

		$this->id = tribe( Order_Modifier_Relationships_Repository::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the relationship from the database.
	 *
	 * @since 5.18.0
	 *
	 * @return bool Whether the relationship was deleted.
	 */
	public function delete(): bool {
		return tribe( Order_Modifier_Relationships_Repository::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Order_Modifier_Relationships_Repository::class )->query();
	}

	/**
	 * Converts the Order_Modifier_Relationship object to an array.
	 *
	 * @since 5.18.0
	 *
	 * @deprecated 5.27.0 Use toArray() instead.
	 *
	 * @return array The object properties as an array.
	 */
	public function to_array(): array {
		_deprecated_function( __METHOD__, '5.27.0', 'toArray()' );
		return $this->toArray();
	}
}
