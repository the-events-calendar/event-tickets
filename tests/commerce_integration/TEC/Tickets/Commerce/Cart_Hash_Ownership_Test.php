<?php
/**
 * Integration tests asserting the cart hash is sourced only from the visitor's server-set cart cookie,
 * and that the `tec-tc-cookie` request param can no longer select or override which cart is read by the
 * REST cart reader. See SVUL-L34.
 */

namespace TEC\Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Cart_Hash_Ownership_Test extends WPTestCase {

	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @after
	 */
	public function reset_request_state(): void {
		unset(
			$_COOKIE[ Cart::get_cart_hash_cookie_name() ],
			$_REQUEST[ Cart::$cookie_query_arg ],
			$_GET[ Cart::$cookie_query_arg ]
		);
	}

	/**
	 * Resets the shared cart repository to a pristine, request-start state so get_cart_hash() resolves
	 * from the cookie alone (in production every request starts with an empty repository).
	 */
	private function reset_active_cart_repository(): void {
		$repository = tribe( Cart::class )->get_repository();

		$this->set_class_property( $repository, 'cart_hash', '' );
		$this->set_class_property( $repository, 'items', [] );
		$this->set_class_property( $repository, 'full_param_items', [] );
		$this->set_class_property( $repository, 'items_have_full_params', false );
		$this->set_class_property( $repository, 'items_calculated', false );
		$this->set_class_property( $repository, 'calculated_items', [] );
	}

	/**
	 * With no cart cookie, the REST reader returns the input unchanged even when a cart hash is supplied
	 * in the query param.
	 */
	public function test_rest_cart_reader_returns_input_without_a_cart_cookie(): void {
		$this->reset_active_cart_repository();
		unset( $_COOKIE[ Cart::get_cart_hash_cookie_name() ] );
		$_REQUEST[ Cart::$cookie_query_arg ] = 'attackrhash2';
		$_GET[ Cart::$cookie_query_arg ]     = 'attackrhash2';

		$passed = [ [ 'ticket_id' => 123, 'quantity' => 1 ] ];

		$this->assertSame(
			$passed,
			tribe( Hooks::class )->filter_rest_get_tickets_in_cart( $passed ),
			'The query param alone must not cause the REST reader to load a cart.'
		);
	}

	/**
	 * End-to-end with a real populated cart: the reader returns the cart to the visitor identified by
	 * the cookie, but the query param cannot load that cart for a visitor without the cookie.
	 */
	public function test_rest_cart_reader_uses_cookie_and_ignores_query_param(): void {
		$post      = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post, 10 );

		// The victim builds a real cart, persisted under their cart hash.
		$repository = tribe( Cart::class )->get_repository();
		$repository->upsert_item( $ticket_id, 2, [ 'optout' => false, 'iac' => 'none' ] );
		$repository->save();
		$victim_hash = $repository->get_hash();

		$this->assertNotEmpty( $victim_hash, 'Precondition: the victim cart was saved with a hash.' );

		$sentinel = [ [ 'ticket_id' => 999, 'quantity' => 1 ] ];

		// A visitor with NO cart cookie, but the victim's hash in the query param, must get nothing:
		// the param cannot select the cart.
		$this->reset_active_cart_repository();
		unset( $_COOKIE[ Cart::get_cart_hash_cookie_name() ] );
		$_REQUEST[ Cart::$cookie_query_arg ] = $victim_hash;
		$_GET[ Cart::$cookie_query_arg ]     = $victim_hash;

		$this->assertSame(
			$sentinel,
			tribe( Hooks::class )->filter_rest_get_tickets_in_cart( $sentinel ),
			'The query param must not be able to load another visitor\'s cart.'
		);

		// The owner, identified by their cart cookie (no query param at all), receives their items.
		$this->reset_active_cart_repository();
		$_COOKIE[ Cart::get_cart_hash_cookie_name() ] = $victim_hash;
		unset( $_REQUEST[ Cart::$cookie_query_arg ], $_GET[ Cart::$cookie_query_arg ] );

		$owner_result = tribe( Hooks::class )->filter_rest_get_tickets_in_cart( $sentinel );

		$this->assertContains(
			$ticket_id,
			wp_list_pluck( $owner_result, 'ticket_id' ),
			'The owner, identified by their cookie, must receive their own cart contents.'
		);
		$this->assertNotContains(
			999,
			wp_list_pluck( $owner_result, 'ticket_id' ),
			'The placeholder is replaced by the real cart contents for the owner.'
		);
	}
}
