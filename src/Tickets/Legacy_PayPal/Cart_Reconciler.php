<?php
/**
 * Checks a PayPal PDT transaction against the server-side cart the checkout stored.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Legacy_PayPal
 */

namespace TEC\Tickets\Legacy_PayPal;

use Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged as Unmanaged_Cart;
use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Utils__Array as Arr;

/**
 * Class Cart_Reconciler
 *
 * The PayPal Standard cart URL carries the amount and quantity through the buyer's browser, so what
 * PayPal reports is only trusted once it matches what the site's own cart says was bought and priced.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Legacy_PayPal
 */
final class Cart_Reconciler {

	/**
	 * Returns why a parsed PDT transaction does not match the cart named by its invoice.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $transaction The transaction as parsed by `Gateway::parse_transaction()`.
	 *
	 * @return string The first mismatch found, an empty string when the transaction matches the cart.
	 */
	public function find_mismatch( array $transaction ): string {
		$custom  = Custom_Argument::decode( Arr::get( $transaction, 'custom', '' ), true );
		$invoice = Arr::get( $custom, 'invoice', '' );

		if ( ! $invoice ) {
			return 'the transaction names no invoice';
		}

		/*
		 * Read by the invoice PayPal returned: the cart reads its invoice from the buyer's cookie,
		 * which a buyer returning in another browser does not have.
		 */
		$cart_items = get_transient( Unmanaged_Cart::get_transient_name( $invoice ) );

		if ( ! $cart_items || ! is_array( $cart_items ) ) {
			return 'no cart is stored for the invoice';
		}

		$receiver = strtolower( trim( Arr::get( $transaction, 'receiver_email', Arr::get( $transaction, 'business', '' ) ) ) );

		$merchant = strtolower( trim( tribe_get_option( 'ticket-paypal-email' ) ) );

		if ( ! $merchant || $merchant !== $receiver ) {
			return 'the payment went to a different merchant';
		}

		$currency = trim( tribe_get_option( 'ticket-commerce-currency-code' ) );

		if ( ( $currency ?: 'USD' ) !== Arr::get( $transaction, 'mc_currency', '' ) ) {
			return 'the payment currency differs';
		}

		$items = [];

		foreach ( Arr::get( $transaction, 'items', [] ) as $item ) {
			$ticket = Arr::get( $item, 'ticket' );

			if ( ! $ticket || isset( $items[ $ticket->ID ] ) ) {
				return 'the transaction names an unknown or repeated ticket';
			}

			$items[ $ticket->ID ] = $item;
		}

		$item_ids = array_keys( $items );
		$cart_ids = array_map( 'absint', array_keys( $cart_items ) );
		sort( $item_ids );
		sort( $cart_ids );

		if ( $item_ids !== $cart_ids ) {
			return 'the tickets differ from the cart';
		}

		$expected_total = 0;

		foreach ( $items as $ticket_id => $item ) {
			$quantity = $this->quantity_of( $cart_items[ $ticket_id ] );

			if ( ! $quantity || absint( Arr::get( $item, 'quantity', 0 ) ) !== $quantity ) {
				return 'the quantity differs from the cart';
			}

			$expected = $this->to_minor_units( $item['ticket']->price ) * $quantity;

			if ( isset( $item['mc_gross'] ) && $this->to_minor_units( $item['mc_gross'] ) !== $expected ) {
				return 'the amount paid for a ticket differs from its price';
			}

			$expected_total += $expected;
		}

		if ( $this->to_minor_units( Arr::get( $transaction, 'mc_gross', '' ) ) !== $expected_total ) {
			return 'the total paid differs from the cart total';
		}

		return '';
	}

	/**
	 * Returns the quantity stored for a cart item, which is a plain number or a `quantity` entry.
	 *
	 * @since TBD
	 *
	 * @param int|array<string,mixed> $item The cart item.
	 *
	 * @return int The quantity.
	 */
	private function quantity_of( $item ): int {
		return absint( is_array( $item ) ? Arr::get( $item, 'quantity', 0 ) : $item );
	}

	/**
	 * Converts a decimal amount to hundredths so amounts compare as integers.
	 *
	 * @since TBD
	 *
	 * @param mixed $amount A numeric amount, such as `10`, `'10.50'`.
	 *
	 * @return int The amount in hundredths, `-1` when it is not a number.
	 */
	private function to_minor_units( $amount ): int {
		return is_numeric( $amount ) ? absint( round( $amount * 100 ) ) : -1;
	}
}
