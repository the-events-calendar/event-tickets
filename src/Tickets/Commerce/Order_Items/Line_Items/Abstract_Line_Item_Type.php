<?php
/**
 * The conversion rules every order line item type shares.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use stdClass;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
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
	 * Item keys stored in a column, and how their value is stored: `int`, `money` or `string`.
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
	 * Item keys whose column has another name; the others use a column of the same name.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const COLUMNS = [
		'display_name' => 'name',
	];

	/**
	 * The object classes a stored item may hold. None of them runs code when unserialized.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const SERIALIZABLE_CLASSES = [ DateTime::class, DateTimeImmutable::class, stdClass::class ];

	/**
	 * How deeply a stored value may nest. Real item data is a few levels deep; this also stops a self-referencing
	 * object from recursing forever.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const MAX_DEPTH = 32;

	/**
	 * The decimals of each currency read so far, keyed by currency code.
	 *
	 * @since TBD
	 *
	 * @var array<string,int>
	 */
	private static array $decimals = [];

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
	 *
	 * @throws InvalidArgumentException When the item holds an object that cannot be stored exactly.
	 */
	public static function to_row( $key, array $item, int $order_id, string $currency ): array {
		$precision = static::get_decimals( $currency );
		$columns   = [];
		$extra     = [
			'keys'   => array_keys( $item ),
			'values' => [],
			'raw'    => [],
		];

		foreach ( $item as $name => $value ) {
			if ( isset( static::FIELDS[ $name ] ) ) {
				$column             = static::COLUMNS[ $name ] ?? $name;
				$columns[ $column ] = static::fit( $column, static::to_column( static::FIELDS[ $name ], $value, $precision ) );
			} else {
				static::keep( $extra, 'values', $name, $value );
			}
		}

		$event_id = $columns['event_id'] ?? null;
		$details  = static::get_details( $columns );

		$row = [
			'order_id'             => $order_id,
			'type'                 => $columns['type'] ?? '',
			'item_key'             => (string) $key,
			'ticket_id'            => $columns['ticket_id'] ?? 0,
			'modifier_id'          => $columns['modifier_id'] ?? 0,
			'purchase_rule_id'     => $columns['purchase_rule_id'] ?? 0,
			'event_id'             => $event_id,
			'post_id'              => $event_id,
			'occurrence_id'        => null,
			'event_title'          => static::fit( 'event_title', $event_id ? ( get_post_field( 'post_title', $event_id ) ?: null ) : null ),
			'event_start_date'     => $event_id ? ( get_post_meta( $event_id, '_EventStartDate', true ) ?: null ) : null,
			'event_start_date_utc' => $event_id ? ( get_post_meta( $event_id, '_EventStartDateUTC', true ) ?: null ) : null,
			'name'                 => static::fit( 'name', $details['name'] ),
			'currency'             => $currency,
			'sku'                  => static::fit( 'sku', $details['sku'] ),
			'ticket_type'          => static::fit( 'ticket_type', $details['ticket_type'] ),
			'quantity'             => $columns['quantity'] ?? 0,
			'price'                => $columns['price'] ?? 0,
			'regular_price'        => $columns['regular_price'] ?? null,
			'sub_total'            => $columns['sub_total'] ?? 0,
			'regular_sub_total'    => $columns['regular_sub_total'] ?? null,
		];

		// Values the row cannot hold exactly (a '0' string, an unrounded float, a null ID stored as 0) keep their original.
		foreach ( array_intersect_key( $item, static::FIELDS ) as $name => $value ) {
			if ( static::from_column( static::FIELDS[ $name ], $row[ static::COLUMNS[ $name ] ?? $name ], $precision ) !== $value ) {
				static::keep( $extra, 'raw', $name, $value );
			}
		}

		// Serialized together, so an object shared by two fields is still one object once read back.
		if ( isset( $extra['serialized'] ) ) {
			$extra['serialized'] = maybe_serialize( $extra['serialized'] );
		}

		// Encoded here because the schema library's encoder would turn 10.0 into 10.
		$row['extra'] = wp_json_encode( $extra, JSON_PRESERVE_ZERO_FRACTION );

		return $row;
	}

	/**
	 * Converts a table row back to the order item it was written from.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|array|null> $row The row, as built by to_row() or read from the database.
	 *
	 * @return array{0: string, 1: array} The item's key in the order's item list, and the item.
	 */
	public static function from_row( array $row ): array {
		$extra     = is_string( $row['extra'] ) ? json_decode( $row['extra'], true ) : $row['extra'];
		$precision = static::get_decimals( $row['currency'] );
		$item      = [];
		$objects   = isset( $extra['serialized'] )
			? unserialize( $extra['serialized'], [ 'allowed_classes' => self::SERIALIZABLE_CLASSES ] ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			: [];

		foreach ( $extra['keys'] as $name ) {
			if ( array_key_exists( $name, $extra['raw'] ) ) {
				$item[ $name ] = $extra['raw'][ $name ];
			} elseif ( array_key_exists( $name, $objects ) ) {
				$item[ $name ] = $objects[ $name ];
			} elseif ( array_key_exists( $name, $extra['values'] ) ) {
				$item[ $name ] = $extra['values'][ $name ];
			} else {
				$item[ $name ] = static::from_column( static::FIELDS[ $name ], $row[ static::COLUMNS[ $name ] ?? $name ], $precision );
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
	protected static function get_details( array $columns ): array {
		return [
			'name'        => $columns['name'] ?? '',
			'sku'         => null,
			'ticket_type' => null,
		];
	}

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
	private static function get_decimals( string $currency ): int {
		return self::$decimals[ $currency ] ??= (int) ( Currency::get_default_currency_map()[ $currency ]['decimal_precision'] ?? 2 );
	}

	/**
	 * Cuts a string to the length of its column.
	 *
	 * WordPress turns off MySQL's strict mode, so a longer value would be cut silently on insert; cutting it here
	 * instead lets the full value round-trip through `raw`.
	 *
	 * @since TBD
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The column value.
	 *
	 * @return mixed The value, cut to fit when it is a string longer than the column.
	 */
	private static function fit( string $column, $value ) {
		return is_string( $value ) && isset( Order_Items_Table::STRING_LENGTHS[ $column ] ) ? mb_substr( $value, 0, Order_Items_Table::STRING_LENGTHS[ $column ] ) : $value;
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
	private static function to_column( string $kind, $value, int $precision ) {
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
	private static function from_column( string $kind, $value, int $precision ) {
		if ( null === $value ) {
			return null;
		}

		if ( 'string' === $kind ) {
			return (string) $value;
		}

		return 'money' === $kind ? (float) ( (int) $value / ( 10 ** $precision ) ) : (int) $value;
	}

	/**
	 * Keeps an item value in `extra`: under `$bucket` when JSON gives it back unchanged, serialized otherwise.
	 *
	 * JSON would turn an object, such as the DateTime in a purchase rule's data, into an array.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $extra  The row's extra data.
	 * @param string              $bucket The reserved key for JSON-safe values: `values` or `raw`.
	 * @param int|string          $name   The item key the value belongs to.
	 * @param mixed               $value  The value.
	 *
	 * @throws InvalidArgumentException When the value holds an object that cannot be stored exactly.
	 */
	private static function keep( array &$extra, string $bucket, $name, $value ): void {
		if ( json_decode( wp_json_encode( $value, JSON_PRESERVE_ZERO_FRACTION ), true ) === $value ) {
			$extra[ $bucket ][ $name ] = $value;

			return;
		}

		static::assert_serializable( $name, $value );
		$extra['serialized'][ $name ] = $value;
	}

	/**
	 * Rejects a value holding an object that would not come back from storage as it went in.
	 *
	 * The writer treats the exception as a failed write, so the order stays on the old items meta.
	 *
	 * @since TBD
	 *
	 * @param int|string $name  The item key the value belongs to.
	 * @param mixed      $value The value.
	 * @param int        $depth How many levels deep the value sits.
	 *
	 * @throws InvalidArgumentException When the value holds a resource, an object of any other class, or nests too deep.
	 */
	private static function assert_serializable( $name, $value, int $depth = 0 ): void {
		if ( $depth > self::MAX_DEPTH ) {
			throw new InvalidArgumentException( sprintf( 'Item field "%s" is nested more than %d levels deep, which cannot be stored exactly.', $name, self::MAX_DEPTH ) );
		}

		if ( is_resource( $value ) ) {
			throw new InvalidArgumentException( sprintf( 'Item field "%s" holds a resource, which cannot be stored exactly.', $name ) );
		}

		if ( is_object( $value ) && ! in_array( get_class( $value ), self::SERIALIZABLE_CLASSES, true ) ) {
			throw new InvalidArgumentException( sprintf( 'Item field "%s" holds a %s, which cannot be stored exactly.', $name, get_class( $value ) ) );
		}

		if ( is_array( $value ) || $value instanceof stdClass ) {
			foreach ( (array) $value as $nested ) {
				static::assert_serializable( $name, $nested, $depth + 1 );
			}
		}
	}
}
