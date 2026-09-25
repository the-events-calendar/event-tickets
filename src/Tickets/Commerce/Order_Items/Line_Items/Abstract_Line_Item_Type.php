<?php
/**
 * The conversion rules every order line item type shares.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Class Abstract_Line_Item_Type.
 *
 * Everything the columns cannot hold exactly lives in the row's `extra` JSON.
 *
 * Money is stored in the minor units of the row's currency: the currency's own decimals, never the site's
 * decimals setting, which only changes how prices are displayed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
abstract class Abstract_Line_Item_Type implements Line_Item_Type {
	/**
	 * Item keys stored in a column of the same name, and how their value is stored: `int`, `money` or `string`.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = [
		'type'      => 'string',
		'ticket_id' => 'int',
		'event_id'  => 'int',
		'quantity'  => 'int',
		'price'     => 'money',
		'sub_total' => 'money',
	];

	/**
	 * The decimals of each currency read so far, keyed by currency code.
	 *
	 * Kept per instance, not statically, so a currency map filter added later in the request still applies to
	 * currencies not read yet.
	 *
	 * @since TBD
	 *
	 * @var array<string,int>
	 */
	private array $decimals = [];

	/**
	 * Converts an order item to a table row.
	 *
	 * @since TBD
	 *
	 * @param int|string $key      The item's key in the order's item list.
	 * @param array      $item     The order item.
	 * @param int        $order_id The order ID.
	 * @param string     $currency The order's currency code.
	 *
	 * @return array<string,int|string|null> The row.
	 */
	public function to_row( $key, array $item, int $order_id, string $currency ): array {
		$precision = $this->get_decimals( $currency );
		$columns   = [];
		$extra     = [
			'keys'   => array_keys( $item ),
			'values' => [],
			'raw'    => [],
		];

		foreach ( $item as $name => $value ) {
			if ( ! isset( static::FIELDS[ $name ] ) ) {
				$extra['values'][ $name ] = $value;
				continue;
			}

			$kind             = static::FIELDS[ $name ];
			$columns[ $name ] = $this->to_column( $kind, $value, $precision );

			// Values the column cannot hold exactly (a '0' string, an unrounded float) keep their original.
			if ( $this->from_column( $kind, $columns[ $name ], $precision ) !== $value ) {
				$extra['raw'][ $name ] = $value;
			}
		}

		$event_id = $columns['event_id'] ?? null;
		$details  = $this->get_details( $columns );

		return [
			'order_id'             => $order_id,
			'type'                 => $columns['type'] ?? '',
			'item_key'             => (string) $key,
			'ticket_id'            => $columns['ticket_id'] ?? 0,
			'modifier_id'          => 0,
			'purchase_rule_id'     => 0,
			'event_id'             => $event_id,
			'post_id'              => $event_id,
			'occurrence_id'        => null,
			'event_title'          => $event_id ? ( get_post_field( 'post_title', $event_id ) ?: null ) : null,
			'event_start_date'     => $event_id ? ( get_post_meta( $event_id, '_EventStartDate', true ) ?: null ) : null,
			'event_start_date_utc' => $event_id ? ( get_post_meta( $event_id, '_EventStartDateUTC', true ) ?: null ) : null,
			'name'                 => $details['name'],
			'currency'             => $currency,
			'sku'                  => $details['sku'],
			'ticket_type'          => $details['ticket_type'],
			'quantity'             => $columns['quantity'] ?? 0,
			'price'                => $columns['price'] ?? 0,
			'regular_price'        => $columns['regular_price'] ?? null,
			'sub_total'            => $columns['sub_total'] ?? 0,
			'regular_sub_total'    => $columns['regular_sub_total'] ?? null,
			// Encoded here because the schema library's encoder would turn 10.0 into 10.
			'extra'                => wp_json_encode( $extra, JSON_PRESERVE_ZERO_FRACTION ),
		];
	}

	/**
	 * Converts a table row back to the order item it was written from.
	 *
	 * Values kept in `extra` are read first, so a row reads back whole even when the type that wrote it maps
	 * other fields to columns, as after the plugin registering that type is deactivated.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|array|null> $row The row, as built by to_row() or read from the database.
	 *
	 * @return array{0: string, 1: array} The item's key in the order's item list, and the item.
	 */
	public function from_row( array $row ): array {
		$extra     = is_string( $row['extra'] ) ? json_decode( $row['extra'], true ) : $row['extra'];
		$precision = $this->get_decimals( $row['currency'] );
		$item      = [];

		foreach ( $extra['keys'] as $name ) {
			if ( array_key_exists( $name, $extra['raw'] ) ) {
				$item[ $name ] = $extra['raw'][ $name ];
			} elseif ( array_key_exists( $name, $extra['values'] ) ) {
				$item[ $name ] = $extra['values'][ $name ];
			} else {
				$item[ $name ] = $this->from_column( static::FIELDS[ $name ], $row[ $name ], $precision );
			}
		}

		return [ $row['item_key'], $item ];
	}

	/**
	 * Returns the purchase-time details of the line: its name, SKU and ticket type.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|null> $columns The item's column values, keyed by column name.
	 *
	 * @return array{name: string, sku: ?string, ticket_type: ?string} The line's details.
	 */
	abstract protected function get_details( array $columns ): array;

	/**
	 * Returns the number of decimals a currency defines, such as 2 for USD and 0 for JPY.
	 *
	 * Read from the currency map rather than Currency::get_currency_precision(), which applies the site's decimals
	 * setting: a stored amount must mean the same thing whatever that setting is later changed to.
	 *
	 * @since TBD
	 *
	 * @param string $currency The currency code.
	 *
	 * @return int The currency's decimals; 2, the most common, for a code the map does not define.
	 */
	private function get_decimals( string $currency ): int {
		return $this->decimals[ $currency ] ??= (int) ( Currency::get_default_currency_map()[ $currency ]['decimal_precision'] ?? 2 );
	}

	/**
	 * Converts an item value to its column value.
	 *
	 * @since TBD
	 *
	 * @param string $kind      One of `int`, `money` or `string`.
	 * @param mixed  $value     The item value.
	 * @param int    $precision The currency's decimals.
	 *
	 * @return int|string|null The column value.
	 */
	private function to_column( string $kind, $value, int $precision ) {
		if ( null === $value ) {
			return null;
		}

		if ( 'string' === $kind ) {
			return is_scalar( $value ) ? (string) $value : '';
		}

		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		return 'money' === $kind ? (int) round( $value * ( 10 ** $precision ) ) : (int) $value;
	}

	/**
	 * Converts a column value to its natural item value: a float for money, an int or a string otherwise.
	 *
	 * @since TBD
	 *
	 * @param string $kind      One of `int`, `money` or `string`.
	 * @param mixed  $value     The column value, as stored or as read from the database.
	 * @param int    $precision The currency's decimals.
	 *
	 * @return float|int|string|null The item value.
	 */
	private function from_column( string $kind, $value, int $precision ) {
		if ( null === $value ) {
			return null;
		}

		if ( 'string' === $kind ) {
			return (string) $value;
		}

		return 'money' === $kind ? (float) ( (int) $value / ( 10 ** $precision ) ) : (int) $value;
	}
}
