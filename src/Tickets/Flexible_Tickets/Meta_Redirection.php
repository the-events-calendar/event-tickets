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
use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;

/**
 * Class Meta_Redirection.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Meta_Redirection extends Controller {
	/**
	 * A flag property to know if the redirection is active or not.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $do_redirect = true;

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'get_post_metadata', [ $this, 'redirect_metadata' ], 5, 4 );
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

	/**
	 * Depending on the post ID, meta key and setting, get metadata from the custom tables.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value     The value get_metadata() should return - a single metadata value, or an array of
	 *                          values.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key, might be empty to indicate all meta for the given object.
	 * @param bool   $single    Whether to return only the first value for the specified meta key, or an array of
	 *                          values.
	 *
	 * @return mixed Either the value from the custom tables, or the original value.
	 */
	public function redirect_metadata( $value, $object_id, $meta_key = '', $single = false ) {
		if ( ! $this->do_redirect ) {
			return $value;
		}

		$check_args = $single && is_int( $object_id ) && $object_id > 0
		              && is_string( $meta_key ) && ! empty( $meta_key );

		if ( ! $check_args ) {
			return $value;
		}

		$post_type = get_post_type( $object_id );
		$is_ticket = $post_type === Commerce_Ticket::POSTTYPE
		             || preg_match( '/^tribe_.*ticket/', $post_type );

		if ( ! $is_ticket ) {
			return $value;
		}

		// Unregister this controller to avoid infinite recursion on `get_post_meta` calls.
		$this->unregister();

		$ticket_type = get_post_meta( $object_id, '_type', true );

		if ( $ticket_type === Series_Passes::HANDLED_TICKET_TYPE ) {
			$value = $this->container->get( Series_Passes::class )->get_ticket_metadata( $value, (int) $object_id, (string) $meta_key );
		}

		// Re-register this controller.
		$this->do_register();

		return $value;
	}

	/**
	 * Stop the redirection.
	 *
	 * @since TBD
	 *
	 * @return void Flag the redirection as stopped.
	 */
	public function stop():void{
		$this->do_redirect = false;
	}

	/**
	 * Resume the redirection.
	 *
	 * @since TBD
	 *
	 * @return void Flag the redirection as resumed.
	 */
	public function resume():void{
		$this->do_redirect = true;
	}
}