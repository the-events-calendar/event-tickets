<?php
/**
 * The CRUD model for the Capacity Relationship.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Model;

/**
 * Class Capacity_Relationship.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */
class Capacity_Relationship extends Model implements ModelCrud {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'                 => 'int',
		'capacity_id'        => 'int',
		'parent_capacity_id' => 'int',
		'object_id'          => 'int',
	];

	public static function find( $id ) {
		// TODO: Implement find() method.
	}

	public static function create( array $attributes ) {
		// TODO: Implement create() method.
	}

	public function save() {
		// TODO: Implement save() method.
	}

	public function delete(): bool {
		// TODO: Implement delete() method.
	}

	public static function query() {
		// TODO: Implement query() method.
	}
}