<?php

namespace Tribe\Tickets\Test\Commerce\TicketsCommerce;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;

/**
 * Builds Stripe Payment Intent data the way checkout creates it. The host test must use With_Uopz.
 */
trait Stripe_Payment_Intent_Maker {

	/**
	 * Gets the metadata Payment_Intent::create_from_cart() sends to Stripe for the current cart.
	 *
	 * A cart without a hash is given one, as the checkout page gives every buyer's cart.
	 *
	 * @return array<string,string|bool>
	 */
	protected function mint_cart_binding_metadata(): array {
		if ( ! tribe( Cart::class )->get_cart_hash() ) {
			$this->set_class_fn_return( Cart::class, 'get_cart_hash', 'buyercart001' );
		}

		$sent_metadata = [];
		$this->set_class_fn_return(
			Abstract_Requests::class,
			'post',
			static function ( $url, $query_args, $args ) use ( &$sent_metadata ) {
				$sent_metadata = $args['body']['metadata'];

				return [];
			},
			true
		);

		Payment_Intent::create_from_cart( tribe( Cart::class ) );
		uopz_unset_return( Abstract_Requests::class, 'post' );

		return $sent_metadata;
	}
}
