<?php
/**
 * A Controller to redirect postmeta CRUD operations to the custom tables, if required.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Provider\Controller;

/**
 * Class Meta_Redirection.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Meta_Redirection extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'get_post_metadata', [ $this, 'redirect_metadata' ], 5, 3 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'get_post_metadata', [ $this, 'redirect_metadata' ], 5 );
	}

	public function redirect_metadata( $value, $object_id, $meta_key, $single ) {
		$check_args = is_int( $object_id ) && $object_id > 0
		              && is_string( $meta_key ) && ! empty( $meta_key );

		if ( ! $check_args ) {
			return $value;
		}

		$post_type = get_post_type( $object_id );

		// Single we'll just cast to bool.
		$single = (bool) $single;
	}
}