<?php
/**
 * The Order Modifier model.
 *
 * @since   5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Models;

use RuntimeException;
use TEC\Common\StellarWP\Models\Contracts\Model as ModelInterface;
use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Contracts\ModelFromQueryBuilderObject;
use TEC\Common\StellarWP\Models\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects\Order_Modifier_DTO;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Positive_Integer_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Value_Interface;

/**
 * Class Order_Modifier.
 *
 * @since   5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Models;
 *
 * @property int    $id              The Order Modifier ID.
 * @property string $modifier_type   The type of modifier (coupon, fee).
 * @property string $sub_type        The sub-type of modifier (percentage, flat).
 * @property float  $raw_amount      Raw fee amount.
 * @property string $slug            The Order Modifier slug (coupon code).
 * @property string $display_name    User-friendly name.
 * @property string $status          The status (active, draft, inactive).
 * @property string $created_at      Creation timestamp.
 * @property string $start_time      When the modifier becomes active.
 * @property string $end_time        When the modifier expires.
 */
class Order_Modifier extends Model implements ModelCrud, ModelFromQueryBuilderObject {

	/**
	 * The model properties assigned to their types.
	 *
	 * @var array<string,string>
	 */
	protected $properties = [
		'id'            => 'int',
		'modifier_type' => 'string',
		'sub_type'      => 'string',
		'raw_amount'    => 'float',
		'slug'          => 'string',
		'display_name'  => 'string',
		'status'        => 'string',
		'created_at'    => 'string',
		'start_time'    => 'string',
		'end_time'      => 'string',
	];

	/**
	 * The order modifier type (e.g., 'coupon', 'fee').
	 *
	 * This will be defined in child classes.
	 *
	 * @since 5.18.0
	 * @var string
	 */
	protected static string $order_modifier_type;

	/**
	 * Finds a model by its ID.
	 *
	 * @since 5.18.0
	 *
	 * @param int $id The model ID.
	 *
	 * @return Order_Modifier|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		if ( empty( static::$order_modifier_type ) ) {
			return null;
		}

		return ( new Repository( static::$order_modifier_type ) )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return ( new Repository( $this->modifier_type ) )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 5.18.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Repository::class )->query();
	}

	/**
	 * Builds a new model from a query builder object.
	 *
	 * @since 5.18.0
	 *
	 * @param object $object The object to build the model from.
	 *
	 * @return static
	 */
	public static function fromQueryBuilderObject( $object ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames
		return Order_Modifier_DTO::fromObject( $object )->toModel();
	}

	/**
	 * Converts the Order_Modifier object to an array.
	 *
	 * @since 5.18.0
	 *
	 * @return array The object properties as an array.
	 */
	public function to_array(): array {
		$attributes = [];

		// Use getAttribute() to ensure value objects are converted to their raw values.
		foreach ( $this->attributes as $key => $type ) {
			$attributes[ $key ] = $this->getAttribute( $key );
		}

		return $attributes;
	}

	/**
	 * Validates an attribute to a PHP type.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return bool
	 */
	public function isPropertyTypeValid( string $key, $value ): bool {
		switch ( $key ) {
			case 'raw_amount':
				return is_float( $value ) || $value instanceof Float_Value || $value instanceof Percent_Value;

			case 'id':
				return is_int( $value ) || $value instanceof Positive_Integer_Value;

			default:
				return parent::isPropertyTypeValid( $key, $value );
		}
	}

	/**
	 * Sets an attribute on the model.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key   Attribute name.
	 * @param mixed  $value Attribute value.
	 *
	 * @return ModelInterface
	 */
	public function setAttribute( string $key, $value ): ModelInterface {
		$this->validatePropertyExists( $key );
		$this->validatePropertyType( $key, $value );
		$this->run_validation_method( $key, $value );

		// Ensure specific attributes are stored as value objects.
		switch ( $key ) {
			case 'raw_amount':
				if ( ! $value instanceof Value_Interface ) {
					$value = Float_Value::from_number( $value );
				}
				break;

			case 'id':
				if ( ! $value instanceof Positive_Integer_Value ) {
					$value = Positive_Integer_Value::from_number( $value );
				}
				break;

			default:
				// No specific action needed.
				break;
		}

		$this->attributes[ $key ] = $value;

		return $this;
	}

	/**
	 * Returns an attribute from the model.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key     Attribute name.
	 * @param mixed  $default Default value. Default is null.
	 *
	 * @return mixed The attribute value.
	 * @throws RuntimeException When the attribute does not exist.
	 */
	public function getAttribute( string $key, $default = null ) { // phpcs:ignore Universal.NamingConventions
		$this->validatePropertyExists( $key );
		if ( ! $this->hasAttribute( $key ) ) {
			return $default;
		}

		$value = $this->attributes[ $key ];

		// Return the value directly if it's not a value object.
		if ( ! $value instanceof Value_Interface ) {
			return $value;
		}

		// When retrieving a value object, return the raw value.
		return $value instanceof Percent_Value
			? $value->get_as_percent()
			: $value->get();
	}

	/**
	 * Validates a property value based on a dedicated method.
	 *
	 * @since 5.18.0
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return void
	 */
	protected function run_validation_method( string $key, $value ) {
		$validation_method = "validate_{$key}";
		if ( method_exists( $this, $validation_method ) ) {
			$this->$validation_method( $value );
		}
	}
}
