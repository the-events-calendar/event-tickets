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
	 * The length of each string column. WordPress turns off MySQL's strict mode, so a longer value would be cut
	 * silently on insert; cutting it here instead lets the full value round-trip through `raw`.
	 *
	 * @since TBD
	 *
	 * @var array<string,int>
	 */
	private const LENGTHS = [
		'type'        => 50,
		'event_title' => 255,
		'name'        => 255,
		'sku'         => 255,
		'ticket_type' => 50,
	];

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
	public function to_row( $key, array $item, int $order_id, string $currency ): array {
		$precision = $this->get_decimals( $currency );
		$columns   = [];
		$extra     = [
			'keys'   => array_keys( $item ),
			'values' => [],
			'raw'    => [],
		];

		foreach ( $item as $name => $value ) {
			if ( isset( static::FIELDS[ $name ] ) ) {
				$column             = static::COLUMNS[ $name ] ?? $name;
				$columns[ $column ] = $this->fit( $column, $this->to_column( static::FIELDS[ $name ], $value, $precision ) );
			} else {
				$this->keep( $extra, 'values', $name, $value );
			}
		}

		$event_id = $columns['event_id'] ?? null;
		$details  = $this->get_details( $columns );

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
			'event_title'          => $this->fit( 'event_title', $event_id ? ( get_post_field( 'post_title', $event_id ) ?: null ) : null ),
			'event_start_date'     => $event_id ? ( get_post_meta( $event_id, '_EventStartDate', true ) ?: null ) : null,
			'event_start_date_utc' => $event_id ? ( get_post_meta( $event_id, '_EventStartDateUTC', true ) ?: null ) : null,
			'name'                 => $this->fit( 'name', $details['name'] ),
			'currency'             => $currency,
			'sku'                  => $this->fit( 'sku', $details['sku'] ),
			'ticket_type'          => $this->fit( 'ticket_type', $details['ticket_type'] ),
			'quantity'             => $columns['quantity'] ?? 0,
			'price'                => $columns['price'] ?? 0,
			'regular_price'        => $columns['regular_price'] ?? null,
			'sub_total'            => $columns['sub_total'] ?? 0,
			'regular_sub_total'    => $columns['regular_sub_total'] ?? null,
		];

		// Values the row cannot hold exactly (a '0' string, an unrounded float, a null ID stored as 0) keep their original.
		foreach ( array_intersect_key( $item, static::FIELDS ) as $name => $value ) {
			if ( $this->from_column( static::FIELDS[ $name ], $row[ static::COLUMNS[ $name ] ?? $name ], $precision ) !== $value ) {
				$this->keep( $extra, 'raw', $name, $value );
			}
		}

		// Encoded here because the schema library's encoder would turn 10.0 into 10.
		$row['extra'] = wp_json_encode( $extra, JSON_PRESERVE_ZERO_FRACTION );

		return $row;
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
			} elseif ( isset( $extra['serialized'][ $name ] ) ) {
				$item[ $name ] = unserialize( $extra['serialized'][ $name ], [ 'allowed_classes' => self::SERIALIZABLE_CLASSES ] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			} elseif ( array_key_exists( $name, $extra['values'] ) ) {
				$item[ $name ] = $extra['values'][ $name ];
			} else {
				$item[ $name ] = $this->from_column( static::FIELDS[ $name ], $row[ static::COLUMNS[ $name ] ?? $name ], $precision );
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
	protected function get_details( array $columns ): array {
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
	private function get_decimals( string $currency ): int {
		return $this->decimals[ $currency ] ??= (int) ( Currency::get_default_currency_map()[ $currency ]['decimal_precision'] ?? 2 );
	}

	/**
	 * Cuts a string to the length of its column.
	 *
	 * @since TBD
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The column value.
	 *
	 * @return mixed The value, cut to fit when it is a string longer than the column.
	 */
	private function fit( string $column, $value ) {
		return is_string( $value ) && isset( self::LENGTHS[ $column ] ) ? mb_substr( $value, 0, self::LENGTHS[ $column ] ) : $value;
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
	private function keep( array &$extra, string $bucket, $name, $value ): void {
		if ( json_decode( wp_json_encode( $value, JSON_PRESERVE_ZERO_FRACTION ), true ) === $value ) {
			$extra[ $bucket ][ $name ] = $value;

			return;
		}

		$this->assert_serializable( $name, $value );
		$extra['serialized'][ $name ] = serialize( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
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
	 *
	 * @throws InvalidArgumentException When the value holds an object of any other class.
	 */
	private function assert_serializable( $name, $value ): void {
		if ( is_object( $value ) && ! in_array( get_class( $value ), self::SERIALIZABLE_CLASSES, true ) ) {
			throw new InvalidArgumentException( sprintf( 'Item field "%s" holds a %s, which cannot be stored exactly.', $name, get_class( $value ) ) );
		}

		if ( is_array( $value ) || $value instanceof stdClass ) {
			foreach ( (array) $value as $nested ) {
				$this->assert_serializable( $name, $nested );
			}
		}
	}
}
