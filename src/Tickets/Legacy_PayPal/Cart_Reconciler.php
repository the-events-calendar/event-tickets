<?php
/**
 * Checks a PayPal Standard payment against what the site's checkout sent to PayPal.
 *
 * @since 5.30.3
 *
 * @package TEC\Tickets\Legacy_PayPal
 */

namespace TEC\Tickets\Legacy_PayPal;

use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Utils__Array as Arr;

/**
 * Class Cart_Reconciler
 *
 * The PayPal Standard cart URL carries the merchant, currency, amounts and quantities through the buyer's browser, so
 * the checkout records what it sent under the invoice, and a payment is only fulfilled when it matches that record.
 * Paying more than the record is accepted: PayPal adds the tax, shipping and handling of the merchant's profile.
 *
 * @since 5.30.3
 *
 * @package TEC\Tickets\Legacy_PayPal
 */
final class Cart_Reconciler {

	/**
	 * The prefix of the transient that holds what a checkout sent to PayPal.
	 *
	 * @since 5.30.3
	 *
	 * @var string
	 */
	private const CHECKOUT_TRANSIENT_PREFIX = 'tec_tickets_paypal_checkout_';

	/**
	 * Records what the checkout sends to PayPal for an invoice.
	 *
	 * @since 5.30.3
	 *
	 * @param string                         $invoice The invoice the cart is stored under.
	 * @param array<string,string|int|float> $args    The PayPal cart URL arguments, as filtered.
	 *
	 * @return void
	 */
	public function remember_checkout( string $invoice, array $args ): void {
		$items = [];

		foreach ( $args as $key => $item_number ) {
			if ( ! preg_match( '/^item_number(_\d+)?$/', $key, $matches ) ) {
				continue;
			}

			$suffix = $matches[1] ?? '';

			$items[ $item_number ] = [
				'quantity' => absint( Arr::get( $args, 'quantity' . $suffix, 0 ) ),
				'amount'   => $this->in_hundredths( Arr::get( $args, 'amount' . $suffix, '' ) ),
			];
		}

		$checkout = [
			'business' => strtolower( trim( urldecode( Arr::get( $args, 'business', '' ) ) ) ),
			'currency' => Arr::get( $args, 'currency_code', '' ),
			'items'    => $items,
		];

		set_transient( $this->transient_name( $invoice ), $checkout, DAY_IN_SECONDS );
	}

	/**
	 * Deletes the checkout record of an invoice, so no other payment can be matched against it.
	 *
	 * @since 5.30.3
	 *
	 * @param string $invoice The invoice.
	 *
	 * @return void
	 */
	public function forget_checkout( string $invoice ): void {
		delete_transient( $this->transient_name( $invoice ) );
	}

	/**
	 * Returns why a parsed PayPal transaction does not match the checkout named by its invoice.
	 *
	 * @since 5.30.3
	 *
	 * @param array<string,mixed> $transaction The transaction as parsed by `Gateway::parse_transaction()`.
	 *
	 * @return string The first mismatch found, an empty string when the transaction matches its checkout.
	 */
	public function find_mismatch( array $transaction ): string {
		// A later notification for the order, a refund or a reload of the return page: the first one was checked.
		$order_id = Arr::get( $transaction, 'parent_txn_id', Arr::get( $transaction, 'txn_id', '' ) );

		if ( '' !== $order_id && tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $order_id ) ) {
			return '';
		}

		$custom   = Custom_Argument::decode( Arr::get( $transaction, 'custom', '' ), true );
		$checkout = get_transient( $this->transient_name( Arr::get( $custom, 'invoice', '' ) ) );

		if ( ! is_array( $checkout ) ) {
			return 'no checkout is recorded for the invoice';
		}

		$receivers = [
			strtolower( trim( Arr::get( $transaction, 'receiver_email', '' ) ) ),
			strtolower( trim( Arr::get( $transaction, 'business', '' ) ) ),
		];

		if ( '' === $checkout['business'] || ! in_array( $checkout['business'], $receivers, true ) ) {
			return 'the payment went to another merchant';
		}

		if ( $checkout['currency'] !== Arr::get( $transaction, 'mc_currency', '' ) ) {
			return 'the payment currency differs';
		}

		$expected_total = 0;
		$paid_items     = [];

		foreach ( Arr::get( $transaction, 'items', [] ) as $item ) {
			$item_number = Arr::get( $item, 'item_number', '' );
			$sent        = $checkout['items'][ $item_number ] ?? null;

			if ( null === $sent || isset( $paid_items[ $item_number ] ) ) {
				return 'the transaction names an item the checkout did not send';
			}

			$paid_items[ $item_number ] = true;

			if ( filter_var( Arr::get( $item, 'quantity', '' ), FILTER_VALIDATE_INT ) !== $sent['quantity'] ) {
				return 'the quantity differs from the checkout';
			}

			$expected = $sent['amount'] * $sent['quantity'];

			if ( isset( $item['mc_gross'] ) && ! $this->covers( $item['mc_gross'], $expected ) ) {
				return 'the amount paid for an item is less than the checkout amount';
			}

			$expected_total += $expected;
		}

		if ( count( $paid_items ) !== count( $checkout['items'] ) ) {
			return 'the transaction misses an item the checkout sent';
		}

		if ( ! $this->covers( Arr::get( $transaction, 'mc_gross', '' ), $expected_total ) ) {
			return 'the total paid is less than the checkout total';
		}

		return '';
	}

	/**
	 * Returns whether an amount PayPal reports covers the expected amount.
	 *
	 * @since 5.30.3
	 *
	 * @param mixed $paid     The amount PayPal reports, such as `'10.50'`.
	 * @param float $expected The expected amount in hundredths.
	 *
	 * @return bool Whether the amount is a number at least as large as the expected one.
	 */
	private function covers( $paid, float $expected ): bool {
		$paid = $this->in_hundredths( $paid );

		return null !== $paid && $paid >= $expected;
	}

	/**
	 * Converts a decimal amount to hundredths, so amounts compare without float rounding.
	 *
	 * @since 5.30.3
	 *
	 * @param mixed $amount A numeric amount, such as `10`, `'10.50'`.
	 *
	 * @return float|null The amount in hundredths, `null` when it is not a number.
	 */
	private function in_hundredths( $amount ): ?float {
		return is_numeric( $amount ) ? round( $amount * 100 ) : null;
	}

	/**
	 * Returns the name of the transient that holds what a checkout sent to PayPal.
	 *
	 * @since 5.30.3
	 *
	 * @param string $invoice The invoice.
	 *
	 * @return string The transient name.
	 */
	private function transient_name( string $invoice ): string {
		return self::CHECKOUT_TRANSIENT_PREFIX . md5( $invoice );
	}
}
