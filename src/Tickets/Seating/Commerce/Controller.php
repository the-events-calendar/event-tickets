<?php
/**
 * Handles the integration with the Tickets Commerce module.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Commerce;
 */

namespace TEC\Tickets\Seating\Commerce;

use Closure;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Module;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Commerce;
 */
class Controller extends Controller_Contract {
	/**
	 * Subscribes to the WordPress hooks and actions required by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
	}

	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_seating_timer_token_object_id_entries',
			[ $this, 'filter_timer_token_object_id_entries' ],
		);
	}

	/**
	 * Filters the handler used to get the token and object ID from the cookie.
	 *
	 * @since TBD
	 *
	 * @parma array<string,string> $session_entries The entries from the cookie. A map from object ID to token.
	 *
	 * @return array<string,string> The entries from the cookie. A map from object ID to token.
	 */
	public function filter_timer_token_object_id_entries( $session_entries ):array {
		$tickets_commerce = tribe( Module::class );

		if ( empty( $session_entries ) || ! $tickets_commerce->is_checkout_page() ) {
			// Not a Tickets Commerce checkout page: return the original entries.
			return $session_entries;
		}

		// Get the post IDs in the cart.
		global $wpdb;
		/** @var Cart $cart */
		$cart          = tribe( Cart::class );
		$cart_items    = array_keys( $cart->get_items_in_cart() );

		if( empty( $cart_items ) ) {
			return [];
		}

		$ticket_ids_interval = DB::prepare(
			implode( ',', array_fill( 0, count( $cart_items ), '%d' ) ),
			...$cart_items
		);
		$cart_post_ids = DB::get_col(
			DB::prepare(
				"SELECT DISTINCT( meta_value ) FROM %i WHERE post_id IN ({$ticket_ids_interval}) AND meta_key = %s ",
				$wpdb->postmeta,
				Module::ATTENDEE_EVENT_KEY
			)
		);

		// Get the post IDs in the session.
		$session_post_ids = array_keys( $session_entries );

		// Find out the post IDs part of both the cart and the seat selection session.
		$cart_and_session_ids = array_intersect( $cart_post_ids, $session_post_ids );

		if ( empty( $cart_and_session_ids ) ) {
			// There are no Tickets for posts using Seat Assignment in the cart.
			return [];
		}

		return array_combine(
			$cart_and_session_ids,
			array_map( static function ( $item ) use ( $session_entries ) {
				return $session_entries[ $item ];
			}, $cart_and_session_ids )
		);
	}
}