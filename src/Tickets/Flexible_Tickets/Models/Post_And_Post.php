<?php
/**
 * The CRUD model for the Post And Post.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */

namespace TEC\Tickets\Flexible_Tickets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelCrud;
use TEC\Common\StellarWP\Models\Model;

/**
 * Class Post_And_Post.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Models;
 */
class Post_And_Post extends Model implements ModelCrud {
	/**
	 * @inheritDoc
	 */
	protected $properties = [
		'id'        => 'int',
		'post_id_1' => 'int',
		'post_id_2' => 'int',
		'type'      => 'string',
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